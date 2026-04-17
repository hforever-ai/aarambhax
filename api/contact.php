<?php
/**
 * Aarambha contact API — Hostinger: public_html/api/contact.php
 * Secrets: api/config.local.properties (copy from config.example.properties; not in git).
 */
header('Content-Type: application/json; charset=utf-8');

$allowed_origins = [
    'https://www.aarambhax.com',
    'https://aarambhax.com',
    'https://www.aarambhax.ai',
    'https://aarambhax.ai',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

/**
 * @return array<string, string>
 */
function contact_load_config(): array
{
    $path = __DIR__ . '/config.local.properties';
    if (!is_readable($path)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Missing api/config.local.properties. Copy config.example.properties.',
        ]);
        exit();
    }
    $raw = parse_ini_file($path, false, INI_SCANNER_RAW);
    if ($raw === false || !is_array($raw)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid config.local.properties']);
        exit();
    }
    foreach ($raw as $k => $v) {
        if (is_string($v)) {
            $raw[$k] = trim($v);
        }
    }

    $required = ['db_host', 'db_name', 'db_user', 'db_pass', 'email_to', 'email_from'];
    foreach ($required as $key) {
        if (($raw[$key] ?? '') === '') {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Config missing: {$key}"]);
            exit();
        }
    }

    if (!contact_recaptcha_usable($raw)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Config: set recaptcha_secret (classic v3) or Enterprise keys + API key',
        ]);
        exit();
    }

    if (($raw['rate_limit_max'] ?? '') === '') {
        $raw['rate_limit_max'] = '5';
    }
    if (($raw['recaptcha_expected_action'] ?? '') === '') {
        $raw['recaptcha_expected_action'] = 'contact_form';
    }

    return $raw;
}

function contact_recaptcha_usable(array $cfg): bool
{
    if (contact_recaptcha_enterprise_ready($cfg)) {
        return true;
    }
    $secret = $cfg['recaptcha_secret'] ?? '';
    return $secret !== '' && $secret !== 'YOUR_RECAPTCHA_SECRET_KEY';
}

function contact_recaptcha_enterprise_ready(array $cfg): bool
{
    $s = $cfg['recaptcha_site_key'] ?? '';
    $p = $cfg['recaptcha_enterprise_project_id'] ?? '';
    $k = $cfg['recaptcha_enterprise_api_key'] ?? '';
    if ($s === '' || $s === 'YOUR_RECAPTCHA_SITE_KEY') {
        return false;
    }
    if ($p === '' || $p === 'YOUR_GCP_PROJECT_ID') {
        return false;
    }
    if ($k === '' || $k === 'YOUR_RECAPTCHA_ENTERPRISE_API_KEY') {
        return false;
    }
    return true;
}

function verify_recaptcha_enterprise(string $token, array $cfg): bool
{
    $url = sprintf(
        'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s',
        rawurlencode($cfg['recaptcha_enterprise_project_id']),
        rawurlencode($cfg['recaptcha_enterprise_api_key'])
    );
    $action = $cfg['recaptcha_expected_action'];
    $body = json_encode([
        'event' => [
            'token'          => $token,
            'siteKey'        => $cfg['recaptcha_site_key'],
            'expectedAction' => $action,
        ],
    ]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 8,
        ],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return false;
    }
    $valid = !empty($data['tokenProperties']['valid']);
    if (!$valid) {
        return false;
    }
    $gotAction = $data['tokenProperties']['action'] ?? '';
    if ($gotAction !== '' && strcasecmp((string) $gotAction, $action) !== 0) {
        return false;
    }
    if (!isset($data['riskAnalysis']['score'])) {
        return false;
    }
    return (float) $data['riskAnalysis']['score'] >= 0.4;
}

function verify_recaptcha_classic(string $token, string $secret): bool
{
    if ($secret === '' || $secret === 'YOUR_RECAPTCHA_SECRET_KEY') {
        return false;
    }
    $payload = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ],
    ]);
    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    return isset($data['success']) && $data['success']
        && isset($data['score']) && (float) $data['score'] >= 0.4;
}

function verify_recaptcha_token(string $token, array $cfg): bool
{
    if (contact_recaptcha_enterprise_ready($cfg)) {
        return verify_recaptcha_enterprise($token, $cfg);
    }
    return verify_recaptcha_classic($token, $cfg['recaptcha_secret'] ?? '');
}

$cfg = contact_load_config();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$phone   = trim($input['phone'] ?? '');
$type    = trim($input['type'] ?? 'other');
$subject = trim($input['subject'] ?? 'General inquiry');
$message = trim($input['message'] ?? '');
$source  = trim($input['source'] ?? 'contact_page');
$token   = trim($input['recaptcha_token'] ?? '');

$errors = [];
if (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = 'Valid naam daalo';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email daalo';
}
if (strlen($message) < 5 || strlen($message) > 2000) {
    $errors[] = 'Message 5–2000 characters';
}
if ($token === '') {
    $errors[] = 'reCAPTCHA token missing';
}

$allowed_types = ['student', 'parent', 'teacher', 'school', 'investor', 'ngo', 'press', 'other'];
if (!in_array($type, $allowed_types, true)) {
    $type = 'other';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

if (!verify_recaptcha_token($token, $cfg)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed']);
    exit();
}

try {
    $pdo = new PDO(
        'mysql:host=' . $cfg['db_host'] . ';dbname=' . $cfg['db_name'] . ';charset=utf8mb4',
        $cfg['db_user'],
        $cfg['db_pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Contact DB: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) DEFAULT 'contact_page',
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        type VARCHAR(50) DEFAULT 'other',
        subject VARCHAR(255),
        message TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(500),
        status ENUM('new','read','replied','spam') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$rateMax = (int) ($cfg['rate_limit_max'] ?? 5);
if ($rateMax < 1) {
    $rateMax = 5;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM contacts WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
$stmt->execute([$ip]);
if ((int) $stmt->fetchColumn() >= $rateMax) {
    echo json_encode([
        'success' => false,
        'message' => 'Too many submissions. Try again in an hour.',
    ]);
    exit();
}

$spam_keywords = ['viagra', 'casino', 'loan', 'bitcoin', 'crypto', 'seo service'];
$message_lower = strtolower($message);
foreach ($spam_keywords as $kw) {
    if (strpos($message_lower, $kw) !== false) {
        echo json_encode(['success' => true, 'message' => 'Message sent!']);
        exit();
    }
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO contacts (source, name, email, phone, type, subject, message, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $source,
        $name,
        $email,
        $phone,
        $type,
        $subject,
        $message,
        $ip,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
    $contact_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('Contact insert: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save message']);
    exit();
}

$email_to = $cfg['email_to'];
$email_from = $cfg['email_from'];

$email_subject = "New Contact: [{$type}] {$subject} — #{$contact_id}";
$email_body = "New contact from website\n\n"
    . "ID: {$contact_id}\nSource: {$source}\nType: {$type}\n"
    . "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nSubject: {$subject}\n\n"
    . "Message:\n{$message}\n\nIP: {$ip}\nTime: " . date('Y-m-d H:i:s');

$headers = [
    'From: ' . $email_from,
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
];
@mail($email_to, $email_subject, $email_body, implode("\r\n", $headers));

$reply_subject = 'Aapka message mila — Aarambha';
$reply_body = "Namaste {$name},\n\n"
    . "Aapka message humein mil gaya. Hum jald reply karenge.\n\n"
    . "Aapne likha:\n\"{$message}\"\n\n"
    . "Waitlist: https://www.aarambhax.com/waitlist/\n\n"
    . "Shukriya,\nAarambha Team\nhello@aarambhax.com\n";

$reply_headers = [
    'From: Aarambha <' . $email_from . '>',
    'Content-Type: text/plain; charset=UTF-8',
];
@mail($email, $reply_subject, $reply_body, implode("\r\n", $reply_headers));

echo json_encode([
    'success' => true,
    'message' => 'Message sent!',
    'id'      => (int) $contact_id,
]);
