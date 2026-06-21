<?php

namespace App\Services\AarambhAi;

/**
 * Output-side POCSO §23 / JJ Act §74 redaction.
 *
 * The system prompt instructs Gemini not to print victim/minor names. This
 * is a belt-and-suspenders pass on top of any LLM output before it lands in
 * the database or in front of the user. Redaction never replaces the
 * prompt-side rule; it only catches model lapses.
 *
 * Strategy:
 *   1. If the input doesn't trigger any POCSO/sexual-offence/JJ signal,
 *      skip entirely (most legal output is not POCSO).
 *   2. If triggered, scan for likely-victim-name patterns near the trigger
 *      sections and replace with `[REDACTED — POCSO §23]` / `[REDACTED — JJ Act §74]`.
 *
 * False-positive bias: when in doubt, redact. A redacted name is fixable;
 * a leaked name is a criminal offence.
 */
class PocsoRedactor
{
    private const POCSO_TRIGGERS = [
        // English / IPC + BNS
        '/\b(?:section|sec\.?|s\.?|§)\s*(?:6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23)\s+POCSO\b/iu',
        '/\bPOCSO\s+Act\b/iu',
        '/\b(?:rape|sexual\s+(?:assault|abuse)|molestation|aggravated\s+penetrative)\b/iu',
        '/\b(?:section|sec\.?|s\.?|§)\s*(?:354|354A|354B|354C|354D|375|376|377|509)\b/iu',
        '/\b(?:section|sec\.?|s\.?|§)\s*(?:6[3-9]|7[0-9]|80|81)\s+(?:of\s+)?BNS\b/iu',
        // Hindi
        '/बलात्कार/u',
        '/यौन(?:\s+(?:उत्पीड़न|शोषण|हिंसा))/u',
        '/पॉक्सो/iu',
    ];

    private const JJ_TRIGGERS = [
        '/\bJJ\s+Act\b/iu',
        '/\bJuvenile\s+Justice\b/iu',
        '/\b(?:CCL|child\s+in\s+conflict\s+with\s+law)\b/iu',
        '/\bJJB\b/u',
        '/\bChild(?:\s+Welfare)\s+Committee\b/iu',
        '/किशोर\s+न्याय/u',
    ];

    private const MINOR_AGE_PATTERNS = [
        // English: "aged 14", "14-year-old", "age 13 years"
        '/\baged?\s+(?:0?[0-9]|1[0-7])\s*(?:year|yr|y\.?o\.?)\b/iu',
        '/\b(?:0?[0-9]|1[0-7])[-\s]year[-\s]old\b/iu',
        '/\bage\s*(?:of)?\s*(?:0?[0-9]|1[0-7])\s+years?\b/iu',
        // Hindi
        '/\b(?:[०-९1-9]|१[०-७]|1[0-7])\s*(?:वर्ष|साल)/u',
    ];

    public function redact(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $pocsoTriggered = $this->matchesAny($text, self::POCSO_TRIGGERS);
        $jjTriggered = $this->matchesAny($text, self::JJ_TRIGGERS);
        $minorTriggered = $this->matchesAny($text, self::MINOR_AGE_PATTERNS);

        if (! $pocsoTriggered && ! $jjTriggered && ! $minorTriggered) {
            return $text;
        }

        $marker = $pocsoTriggered
            ? '[REDACTED — POCSO §23]'
            : ($jjTriggered ? '[REDACTED — JJ Act §74]' : '[REDACTED — minor identity]');

        // Pattern 1: "victim/prosecutrix/minor named X" → keep role label, redact name
        $text = preg_replace_callback(
            '/\b(?:victim|prosecutrix|complainant|child(?:\s+in\s+conflict\s+with\s+law)?|minor|juvenile|survivor|CCL)(?:\s+(?:named|is|namely|i\.e\.?))?\s+([A-Z][a-zA-Z\.]+(?:\s+[A-Z][a-zA-Z\.]+){0,3})/u',
            fn ($m) => str_replace($m[1], $marker, $m[0]),
            $text,
        );

        // Pattern 1b: standalone "named X" / "by name X" anywhere in text once
        // POCSO/JJ trigger is in scope (we're already in redact mode here).
        $text = preg_replace_callback(
            '/\b(?:named|by\s+name)\s+([A-Z][a-zA-Z\.]+(?:\s+[A-Z][a-zA-Z\.]+){0,3})/u',
            fn ($m) => str_replace($m[1], $marker, $m[0]),
            $text,
        );

        // Pattern 2: Hindi role + name (पीड़िता / प्रसूता / बालिका)
        $text = preg_replace_callback(
            '/(पीड़िता|पीड़ित|प्रसूता|प्रसूति|बालिका|नाबालिग|सर्वाइवर|शिकायतकर्ता)\s+([\p{Devanagari}]+(?:\s+[\p{Devanagari}]+){0,3})/u',
            fn ($m) => $m[1].' '.$marker,
            $text,
        );

        // Pattern 3: explicit "name: X" within 80 chars of a POCSO trigger
        // (detect "her name is X" / "called X" near abuse references)
        $text = preg_replace_callback(
            '/\b(?:name|her\s+name|his\s+name|called|known\s+as)\s+is\s+([A-Z][a-zA-Z\.]+(?:\s+[A-Z][a-zA-Z\.]+){0,2})/u',
            fn ($m) => str_replace($m[1], $marker, $m[0]),
            $text,
        );

        return $text;
    }

    private function matchesAny(string $text, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if (preg_match($p, $text)) {
                return true;
            }
        }
        return false;
    }
}
