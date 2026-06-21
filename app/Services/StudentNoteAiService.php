<?php

namespace App\Services;

use App\Models\StudentNote;
use App\Services\PYQMatchingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class StudentNoteAiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model  = config('services.gemini.model_free_lite', 'gemini-2.5-flash-lite');
    }

    /**
     * OCR the photo + organise the extracted text into clean structured markdown.
     * Single Gemini Vision call does both jobs.
     */
    public function scan(StudentNote $note): void
    {
        $note->update(['ai_status' => 'scanning']);

        try {
            $parts = [];

            if ($note->image_path) {
                $fileData = base64_encode(Storage::disk('public')->get($note->image_path));
                $mime     = $this->mimeFromPath($note->image_path);
                $parts[]  = ['inline_data' => ['mime_type' => $mime, 'data' => $fileData]];
            }

            $context = '';
            if ($note->subject)    $context .= " Subject: {$note->subject}.";
            if ($note->class_name) $context .= " Class: {$note->class_name}.";
            if ($note->note_text)  $context .= "\n\nStudent's own notes:\n{$note->note_text}";

            $parts[] = ['text' => "You are a smart IIT/JEE study assistant.{$context}

TASK 1 — Extract:
Extract ALL text from this file (photo or PDF) exactly as written, preserving all equations, formulas, diagram descriptions, and structure. Output under heading: ## Raw Extracted Text

TASK 2 — Organise:
Rewrite the extracted content as clean, structured study notes:
- Use clear headings (##, ###)
- Bullet points for concepts and definitions
- Bold key terms, formulas, and constants
- Preserve all equations exactly (use LaTeX: \$...\$ for inline, \$\$...\$\$ for display)
- Add brief [brackets] to clarify abbreviations
- Fix spelling errors but preserve all content
- Output under heading: ## Organised Notes

TASK 3 — Title:
Write a short descriptive title for these notes (5-8 words, no quotes). Output under heading: ## Title

Respond in the same language as the notes (Hindi or English)."];

            $response = $this->call($parts);

            // Split raw OCR from organised section
            $ocr       = $this->extractSection($response, 'Raw Extracted Text');
            $organised = $this->extractSection($response, 'Organised Notes');
            $autoTitle = trim($this->extractSection($response, 'Title'));

            $update = [
                'ocr_text'     => $ocr ?: $response,
                'organised_md' => $organised ?: $response,
                'ai_status'    => 'done',
            ];
            if ($autoTitle && !$note->title) {
                $update['title'] = \Illuminate\Support\Str::limit($autoTitle, 200);
            }
            $note->update($update);

            // Auto-match PYQ topics (best-effort — don't fail scan if this errors)
            try {
                (new PYQMatchingService())->matchForNote($note->fresh());
            } catch (\Throwable) {}

        } catch (\Throwable $e) {
            $note->update(['ai_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Generate practice questions from the organised notes.
     */
    public function generateQuestions(StudentNote $note): void
    {
        $source = $note->organised_md ?: $note->ocr_text ?: $note->note_text;
        if (! $source) return;

        $response = $this->call([[
            'text' => "You are an IIT/JEE exam coach. Based on these study notes, generate 8 practice questions:

NOTES:
{$source}

Generate:
- 3 concept-based MCQs with 4 options (A/B/C/D), mark the correct answer
- 2 numerical problems with step-by-step solution
- 2 short-answer theory questions
- 1 tricky application question (JEE-Advanced style)

Format as JSON array:
[
  {\"type\": \"mcq\", \"question\": \"...\", \"options\": [\"A. ...\", \"B. ...\", \"C. ...\", \"D. ...\"], \"answer\": \"A\"},
  {\"type\": \"numerical\", \"question\": \"...\", \"answer\": \"...\"},
  {\"type\": \"short\", \"question\": \"...\", \"answer\": \"...\"},
  {\"type\": \"application\", \"question\": \"...\", \"answer\": \"...\"}
]

Return ONLY the JSON array, no markdown, no explanation.",
        ]]);

        // Strip markdown code fences if present
        $json = preg_replace('/^```(?:json)?\s*/m', '', trim($response));
        $json = preg_replace('/\s*```$/m', '', $json);

        // Validate it parses
        json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json = json_encode([['type' => 'short', 'question' => 'Could not parse questions. Try again.', 'answer' => '']]);
        }

        $note->update(['questions_json' => $json]);
    }

    /**
     * Polish the organised notes — improve language, add legal structure.
     */
    public function polish(StudentNote $note): void
    {
        $source = $note->organised_md ?: $note->ocr_text ?: $note->note_text;
        if (! $source) return;

        $context = $note->subject ? "Subject: {$note->subject}." : '';

        $response = $this->call([[
            'text' => "You are a senior IIT/JEE tutor. {$context}

Polish these student notes:
1. Improve clarity and precision of language
2. Add standard formula references and unit conventions where relevant
3. Add a brief \"Key Takeaway\" box at the top (2–3 bullet points)
4. Highlight common JEE mistakes or traps related to this topic
5. Keep all original content — only improve, never remove
6. Maintain the same heading structure

NOTES:
{$source}

Return polished notes in markdown only.",
        ]]);

        $note->update(['polished_md' => $response]);
    }

    /**
     * Extract all formulas into a clean reference sheet.
     */
    public function formulaSheet(StudentNote $note): void
    {
        $source = $note->organised_md ?: $note->ocr_text ?: $note->note_text;
        if (! $source) return;

        $subject = $note->subject ? "Subject: {$note->subject}." : '';

        $response = $this->call([[
            'text' => "You are an IIT/JEE expert. {$subject}

Extract EVERY formula, equation, and mathematical expression from these notes and create a clean Formula Reference Sheet.

Format as markdown:
- Group formulas under clear headings (e.g., ## Kinematics, ## Forces)
- Each formula on its own line in LaTeX: \$F = ma\$ (inline) or \$\$E = mc^2\$\$ (display)
- After each formula: one-line description of what it represents
- Include SI units where relevant
- Add a ### Key Constants section at the end if any constants appear
- If no formulas found, write: \"No formulas found in these notes.\"

NOTES:
{$source}

Return the formula sheet in markdown only. Be thorough — include every single equation.",
        ]]);

        $note->update(['formula_sheet_md' => $response]);
    }

    /**
     * Generate flip flashcards from the note content.
     */
    public function flashcards(StudentNote $note): void
    {
        $source = $note->organised_md ?: $note->ocr_text ?: $note->note_text;
        if (! $source) return;

        $subject = $note->subject ? "Subject: {$note->subject}." : '';

        $response = $this->call([[
            'text' => "You are an IIT/JEE coach. {$subject}

Create 12-15 flashcards from these notes for active recall practice.

Mix these types:
- Definition cards: concept on front, full definition on back
- Formula cards: \"Formula for [X]?\" on front, formula + meaning on back
- Application cards: scenario/question on front, answer + explanation on back
- True/False cards: statement on front, answer + reason on back

Return ONLY a JSON array:
[
  {\"type\": \"definition\", \"front\": \"What is Newton's First Law?\", \"back\": \"An object at rest stays at rest unless acted upon by an external force.\"},
  {\"type\": \"formula\", \"front\": \"Formula for kinetic energy?\", \"back\": \"KE = ½mv² where m = mass, v = velocity\"},
  {\"type\": \"application\", \"front\": \"A 2kg ball moves at 3m/s. What is its momentum?\", \"back\": \"p = mv = 2 × 3 = 6 kg·m/s\"}
]

NOTES:
{$source}

Return ONLY the JSON array. No markdown fences. No explanation.",
        ]]);

        $json = preg_replace('/^```(?:json)?\s*/m', '', trim($response));
        $json = preg_replace('/\s*```$/m', '', $json);
        json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json = json_encode([['type' => 'definition', 'front' => 'Could not generate flashcards. Try again.', 'back' => '']]);
        }

        $note->update(['flashcards_json' => $json]);
    }

    /**
     * Ask AI a question grounded in this specific note.
     */
    public function askAi(StudentNote $note, string $question): string
    {
        $source = $note->organised_md ?: $note->ocr_text ?: $note->note_text;
        $context = '';
        if ($note->subject)    $context .= " Subject: {$note->subject}.";
        if ($note->class_name) $context .= " Class: {$note->class_name}.";

        return $this->call([[
            'text' => "You are an expert IIT/JEE tutor.{$context}

A student has shared their personal study notes with you. Answer their question USING THEIR NOTES as the primary reference. If their notes cover the topic, explain it in terms of what they have written. If the notes don't cover something, you can supplement with your knowledge but mention it.

STUDENT'S NOTES:
{$source}

STUDENT'S QUESTION: {$question}

Give a clear, concise answer. Use LaTeX for equations (\$...\$ inline, \$\$...\$\$ display). Be encouraging and specific. Keep it under 300 words unless a derivation is needed.",
        ]]);
    }

    private function call(array $parts): string
    {
        $data = \App\Services\Gemini\GeminiClient::callWithRotation($this->model, [
            'contents' => [['parts' => $parts]],
            'generationConfig' => ['temperature' => 0.3],
        ], $this->apiKey, 60);

        return data_get($data, 'candidates.0.content.parts.0.text', '');
    }

    /**
     * Fetch plain-text transcript from a public YouTube video (no API key needed).
     * Tries Hindi captions first, falls back to English, then any available track.
     */
    public function fetchYoutubeTranscript(string $url): string
    {
        preg_match('/(?:v=|youtu\.be\/|\/shorts\/)([a-zA-Z0-9_-]{11})/', $url, $m);
        $videoId = $m[1] ?? null;
        if (! $videoId) throw new \InvalidArgumentException('Invalid YouTube URL — could not extract video ID.');

        // proc_open with args array — no shell interpolation, no injection risk
        $script = base_path('scripts/fetch_transcript.py');
        if (file_exists($script)) {
            $proc = proc_open(
                ['python3', $script, $videoId, 'hi,en'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            if (is_resource($proc)) {
                $out = trim((string) stream_get_contents($pipes[1]));
                fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
                $result = json_decode($out, true);
                if (! empty($result['text']) && strlen($result['text']) >= 50) {
                    return $result['text'];
                }
                if (! empty($result['error'])) {
                    throw new \RuntimeException('Transcript error: ' . $result['error']);
                }
            }
        }

        $page = Http::withHeaders([
            'Accept-Language' => 'en-US,en;q=0.9',
            'User-Agent'      => 'Mozilla/5.0 (compatible; StudyBot/1.0)',
        ])->get("https://www.youtube.com/watch?v={$videoId}");

        if (! $page->successful()) throw new \RuntimeException('Could not fetch YouTube page.');

        // Extract ytInitialPlayerResponse — try multiple patterns (YouTube changes its format)
        $body  = $page->body();
        $data  = null;
        foreach ([
            '/ytInitialPlayerResponse\s*=\s*(\{.+?\});\s*(?:var|const|let|window)/s',
            '/ytInitialPlayerResponse\s*=\s*(\{.+?\});/s',
        ] as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $data = json_decode($m[1], true);
                if ($data) break;
            }
        }

        // Last-resort: look inside a <script> tag for the JSON
        if (! $data) {
            preg_match_all('/<script[^>]*>(.+?)<\/script>/s', $body, $scripts);
            foreach ($scripts[1] as $script) {
                if (str_contains($script, 'ytInitialPlayerResponse')) {
                    if (preg_match('/ytInitialPlayerResponse\s*=\s*(\{.+)/s', $script, $m)) {
                        // Trim to valid JSON by finding the matching closing brace
                        $json = $this->extractBalancedJson($m[1]);
                        $data = json_decode($json, true) ?: null;
                        if ($data) break;
                    }
                }
            }
        }

        if (! $data) throw new \RuntimeException('Could not parse YouTube player data. Video may be private or age-restricted.');

        $tracks = $data['captions']['playerCaptionsTracklistRenderer']['captionTracks'] ?? [];

        if (empty($tracks)) throw new \RuntimeException('This video has no captions. Try a video with subtitles enabled.');

        // Pick language: prefer Hindi → English → first available
        $chosenLang = null;
        $chosenTrack = null;
        foreach (['hi', 'en'] as $lang) {
            foreach ($tracks as $track) {
                if (($track['languageCode'] ?? '') === $lang) {
                    $chosenLang  = $lang;
                    $chosenTrack = $track;
                    break 2;
                }
            }
        }
        $chosenTrack ??= $tracks[0];
        $chosenLang  ??= $chosenTrack['languageCode'] ?? 'en';

        // Rebuild a clean timedtext URL using the video ID + lang (baseUrl can be incomplete)
        $captionUrl = "https://www.youtube.com/api/timedtext?v={$videoId}&lang={$chosenLang}&fmt=json3";
        $raw = Http::timeout(15)->get($captionUrl);
        $text = '';

        if ($raw->successful() && is_array($raw->json('events'))) {
            foreach ($raw->json('events', []) as $event) {
                foreach ($event['segs'] ?? [] as $seg) {
                    $text .= $seg['utf8'] ?? '';
                }
                $text .= ' ';
            }
        }

        // XML fallback — fetch without fmt=json3
        if (strlen(trim($text)) < 50) {
            $xmlUrl = "https://www.youtube.com/api/timedtext?v={$videoId}&lang={$chosenLang}";
            $xml    = Http::timeout(15)->get($xmlUrl);
            if ($xml->successful() && str_contains($xml->body(), '<text')) {
                preg_match_all('/<text[^>]*>([^<]+)<\/text>/', html_entity_decode($xml->body()), $matches);
                $text = implode(' ', $matches[1]);
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if (strlen($text) < 50) throw new \RuntimeException('Transcript too short — captions may be auto-generated in an unsupported format.');

        return $text;
    }

    /**
     * Organise a raw text transcript (from YouTube) into structured study notes.
     * No image — text-only Gemini call.
     */
    public function organiseTranscript(StudentNote $note): void
    {
        $note->update(['ai_status' => 'scanning']);

        try {
            $context = '';
            if ($note->subject)    $context .= " Subject: {$note->subject}.";
            if ($note->class_name) $context .= " Class: {$note->class_name}.";

            $response = $this->call([[
                'text' => "You are a smart IIT/JEE study assistant.{$context}

A student has shared the transcript of a lecture video. Convert it into clean, structured study notes.

TRANSCRIPT:
{$note->note_text}

TASK 1 — Organise:
Rewrite as clean structured notes:
- Clear headings (##, ###)
- Bullet points for concepts and definitions
- Bold key terms and formulas
- LaTeX for all equations: \$...\$ inline, \$\$...\$\$ display
- Remove filler words, repetition, and off-topic conversation
- Output under heading: ## Organised Notes

TASK 2 — Title:
Write a short descriptive title (5-8 words, no quotes). Output under heading: ## Title

Respond in English. If the transcript is in Hindi, translate key concepts to English but keep examples in Hindi.",
            ]]);

            $organised = $this->extractSection($response, 'Organised Notes');
            $autoTitle = trim($this->extractSection($response, 'Title'));

            $update = [
                'ocr_text'     => $note->note_text,
                'organised_md' => $organised ?: $response,
                'ai_status'    => 'done',
            ];
            if ($autoTitle && ! $note->title) {
                $update['title'] = \Illuminate\Support\Str::limit($autoTitle, 200);
            }
            $note->update($update);

            try {
                (new PYQMatchingService())->matchForNote($note->fresh());
            } catch (\Throwable) {}

        } catch (\Throwable $e) {
            $note->update(['ai_status' => 'failed']);
            throw $e;
        }
    }

    private function extractBalancedJson(string $str): string
    {
        $depth = 0; $i = 0; $len = strlen($str);
        while ($i < $len) {
            $c = $str[$i];
            if ($c === '{') $depth++;
            elseif ($c === '}') { $depth--; if ($depth === 0) return substr($str, 0, $i + 1); }
            $i++;
        }
        return $str;
    }

    private function extractSection(string $text, string $heading): string
    {
        if (preg_match('/##\s+'.$heading.'\s*\n([\s\S]*?)(?=\n##\s|\z)/i', $text, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function mimeFromPath(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
            default => 'image/jpeg',
        };
    }
}
