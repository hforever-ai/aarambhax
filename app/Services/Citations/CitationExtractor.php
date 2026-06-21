<?php

namespace App\Services\Citations;

/**
 * Extracts statute and judgment citations from markdown.
 *
 * Regex-based for v1 (no LLM call). Catches the patterns we use in our
 * own prompt templates. Marks each as `pending` — verifier will check.
 */
class CitationExtractor
{
    /**
     * @return array<int, array{type: string, raw_text: string, statute_code: ?string, section_no: ?string, position: int}>
     */
    public function extract(string $markdown): array
    {
        $citations = [];

        // Statute sections: "BNSS §482", "BNS §103", "CG LRC §109", "CrPC §438"
        $statutePattern = '/\b(BNSS|BNS|BSA|CrPC|IPC|CG\s*LRC|CG\s*Rent\s*Control\s*Act|NI\s*Act|HMA|DV\s*Act|POCSO|NDPS|RERA|MV\s*Act|CPC|IEA|BSA)\s*[§Ss]ection\.?\s*([0-9A-Z()\-]+(?:\([0-9a-zA-Z]+\))?)/i';
        if (preg_match_all($statutePattern, $markdown, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $idx => $match) {
                $citations[] = [
                    'type' => 'statute_section',
                    'raw_text' => $match[0],
                    'statute_code' => $this->normaliseStatute($m[1][$idx][0]),
                    'section_no' => $m[2][$idx][0],
                    'position' => $match[1],
                ];
            }
        }

        // Statute sections shorter form: "BNSS §482" or "§528 BNSS"
        $shortPattern = '/\b(BNSS|BNS|BSA|CrPC|IPC|CG\s*LRC|NI\s*Act|HMA|CPC|IEA|DV\s*Act)\s*§\s*([0-9A-Z()\-]+(?:\([0-9a-zA-Z]+\))?)/i';
        if (preg_match_all($shortPattern, $markdown, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $idx => $match) {
                if (! $this->alreadyCaptured($citations, $match[1])) {
                    $citations[] = [
                        'type' => 'statute_section',
                        'raw_text' => $match[0],
                        'statute_code' => $this->normaliseStatute($m[1][$idx][0]),
                        'section_no' => $m[2][$idx][0],
                        'position' => $match[1],
                    ];
                }
            }
        }

        // Judgments: "Party v. Other, (Year) Vol Reporter Page" — common Indian style
        $judgmentPattern = '/[A-Z][a-zA-Z\s&\.]+v(?:s|\.)?\s+[A-Z][a-zA-Z\s&\.\(\)]+,\s*\(\d{4}\)\s*\d+\s*[A-Z]+\s*\d+/';
        if (preg_match_all($judgmentPattern, $markdown, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $match) {
                $citations[] = [
                    'type' => 'judgment',
                    'raw_text' => trim($match[0]),
                    'statute_code' => null,
                    'section_no' => null,
                    'position' => $match[1],
                ];
            }
        }

        // [VERIFY: ...] markers — explicitly flagged by writer
        if (preg_match_all('/\[VERIFY:\s*(.+?)\]/i', $markdown, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $idx => $match) {
                $citations[] = [
                    'type' => 'statute_section',
                    'raw_text' => $match[0],
                    'statute_code' => null,
                    'section_no' => null,
                    'position' => $match[1],
                ];
            }
        }

        // Sort by position so they render in document order
        usort($citations, fn ($a, $b) => $a['position'] <=> $b['position']);

        return $citations;
    }

    private function normaliseStatute(string $raw): string
    {
        $up = strtoupper(preg_replace('/\s+/', ' ', trim($raw)));
        return match ($up) {
            'CG LRC' => 'CG-LRC',
            'CG RENT CONTROL ACT' => 'CG-RENT',
            'NI ACT' => 'NI-ACT',
            'DV ACT' => 'DV-ACT',
            'MV ACT' => 'MV-ACT',
            default => $up,
        };
    }

    private function alreadyCaptured(array $citations, int $position): bool
    {
        foreach ($citations as $c) {
            if (abs($c['position'] - $position) < 10) {
                return true;
            }
        }
        return false;
    }
}
