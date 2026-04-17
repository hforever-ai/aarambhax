-- Aarambha contact form — optional manual install (contact.php also runs this via CREATE TABLE IF NOT EXISTS).
-- Run in phpMyAdmin against your Hostinger database, or rely on first successful POST to api/contact.php.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
