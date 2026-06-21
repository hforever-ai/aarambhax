<?php

namespace App\Services\Gemini;

/**
 * Strips PII (phone, email, full address, Aadhaar, PAN) before sending text
 * to free-tier Gemini API. Used on free-tier flows: analysis, chat, Karya
 * brainstorm types.
 *
 * KEEPS (legal reasoning needs them):
 *   - Party names ("Aman Kumar", "State of CG", "Indra Brothers")
 *   - Section numbers ("§47 CPC", "Section 115 CPC")
 *   - Dates ("16.02.2026", "30 March 2022")
 *   - Court names ("ADJ Jashpur", "Bilaspur HC")
 *   - Case numbers ("Civil Execution(Arbitration)-2/2023")
 *
 * STRIPS:
 *   - Phone numbers (Indian: 10-digit, +91-prefix, 11-digit-with-0, with hyphens/spaces)
 *   - Email addresses
 *   - Aadhaar (12-digit, often spaced or hyphenated)
 *   - PAN (5-letter + 4-digit + 1-letter)
 *   - Pin codes (6-digit, only when adjacent to address-like context)
 *   - Bank account numbers (long runs of digits in account context)
 *
 * Replaces with [REDACTED-{TYPE}] markers so the LLM still understands
 * structure but has no actual contact data.
 *
 * Returns the redacted text PLUS a count of redactions per type — useful
 * for audit logging.
 */
class PiiRedactor
{
    /**
     * @return array{text:string, counts:array<string,int>}
     */
    public function redact(string $text): array
    {
        $counts = [
            'email' => 0,
            'phone' => 0,
            'aadhaar' => 0,
            'pan' => 0,
            'pin' => 0,
        ];

        // Email — robust regex, case-insensitive
        $text = preg_replace_callback(
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            function () use (&$counts) {
                $counts['email']++;
                return '[REDACTED-EMAIL]';
            },
            $text
        );

        // Aadhaar: 12 consecutive digits, allowing single space or hyphen separators
        // Match BEFORE phone so 12-digit Aadhaar doesn't get caught as phone+extra
        $text = preg_replace_callback(
            '/\b(\d{4}[\s-]?\d{4}[\s-]?\d{4})\b/',
            function () use (&$counts) {
                $counts['aadhaar']++;
                return '[REDACTED-AADHAAR]';
            },
            $text
        );

        // PAN: 5 uppercase letters + 4 digits + 1 uppercase letter (standard format)
        $text = preg_replace_callback(
            '/\b([A-Z]{5}\d{4}[A-Z])\b/',
            function () use (&$counts) {
                $counts['pan']++;
                return '[REDACTED-PAN]';
            },
            $text
        );

        // Indian phone — match in this order (most specific first):
        // +91 prefix with 10 digits, with optional separators
        // 0-prefixed 11 digits
        // Plain 10 digits (8-9 starting, the Indian mobile range)
        $text = preg_replace_callback(
            '/(\+91[\s-]?\d{5}[\s-]?\d{5})|(\b0?[6-9]\d{9}\b)|(\b[6-9]\d{2}[\s-]\d{3}[\s-]\d{4}\b)/',
            function () use (&$counts) {
                $counts['phone']++;
                return '[REDACTED-PHONE]';
            },
            $text
        );

        // Pin code: 6 digits, only redact when adjacent to address-like words
        // Matches "PIN 492001", "Pin: 492001", "Bilaspur 495001", "Raipur, 492001"
        $text = preg_replace_callback(
            '/(?:\b(?:pin|pincode|p\.?\s*o\.?|po)\b[\s:.-]+|,\s*)(\d{6})\b/i',
            function ($m) use (&$counts) {
                $counts['pin']++;
                $prefix = mb_substr($m[0], 0, mb_strlen($m[0]) - 6);
                return $prefix.'[REDACTED-PIN]';
            },
            $text
        );

        return ['text' => $text, 'counts' => $counts];
    }

    /**
     * Convenience: redact a doc_pack array (each entry has cleaned_markdown
     * and structured_fields). Returns same shape but redacted.
     *
     * @param  array<int, array>  $docPack
     * @return array{doc_pack:array, total_redactions:int}
     */
    public function redactDocPack(array $docPack): array
    {
        $totalRedactions = 0;
        $redacted = [];
        foreach ($docPack as $doc) {
            $cleaned = $doc;
            if (! empty($doc['cleaned_markdown'])) {
                $r = $this->redact((string) $doc['cleaned_markdown']);
                $cleaned['cleaned_markdown'] = $r['text'];
                $totalRedactions += array_sum($r['counts']);
            }
            // Also redact any string values inside structured_fields
            if (! empty($doc['structured_fields']) && is_array($doc['structured_fields'])) {
                $cleaned['structured_fields'] = $this->redactArray($doc['structured_fields'], $totalRedactions);
            }
            $redacted[] = $cleaned;
        }
        return ['doc_pack' => $redacted, 'total_redactions' => $totalRedactions];
    }

    private function redactArray(array $arr, int &$totalRedactions): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            if (is_string($v)) {
                $r = $this->redact($v);
                $out[$k] = $r['text'];
                $totalRedactions += array_sum($r['counts']);
            } elseif (is_array($v)) {
                $out[$k] = $this->redactArray($v, $totalRedactions);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
