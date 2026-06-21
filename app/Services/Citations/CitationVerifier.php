<?php

namespace App\Services\Citations;

use App\Models\PostCitation;
use Illuminate\Support\Facades\Log;

/**
 * Verifies extracted citations.
 *
 * V1 stub: marks known statute codes (BNS, BNSS, BSA, CG-LRC, NI-ACT, etc.) as
 * `verified` if they look syntactically valid. Marks judgments as `pending`
 * because we don't have Indian Kanoon API integration yet (Phase 4).
 *
 * Phase 4 will add real verification against:
 *   - Internal bare_acts table seeded from India Code
 *   - Indian Kanoon API (₹0.50/req, ₹0 if non-commercial tier approved)
 */
class CitationVerifier
{
    private const KNOWN_STATUTES = [
        'BNS', 'BNSS', 'BSA',
        'CrPC', 'IPC', 'IEA',
        'CG-LRC', 'CG-RENT',
        'NI-ACT', 'HMA', 'DV-ACT',
        'POCSO', 'NDPS', 'RERA', 'MV-ACT',
        'CPC',
    ];

    public function verify(PostCitation $citation): PostCitation
    {
        $citation->verified_at = now();
        $citation->verified_by = 'auto';

        if ($citation->citation_type === 'statute_section') {
            $citation->verification_status = $this->verifyStatute($citation);
        } elseif ($citation->citation_type === 'judgment') {
            // Pending until Indian Kanoon API is wired up in Phase 4
            $citation->verification_status = 'pending';
            $citation->verifier_notes = 'Judgment verification needs Indian Kanoon API (Phase 4).';
        } else {
            $citation->verification_status = 'pending';
        }

        $citation->save();
        return $citation;
    }

    public function verifyAll(int $postId): array
    {
        $stats = ['verified' => 0, 'suspect' => 0, 'hallucinated' => 0, 'pending' => 0];
        foreach (PostCitation::where('post_id', $postId)->get() as $cit) {
            $this->verify($cit);
            $stats[$cit->verification_status]++;
        }
        Log::info('CitationVerifier completed for post '.$postId, $stats);
        return $stats;
    }

    private function verifyStatute(PostCitation $citation): string
    {
        if (str_contains((string) $citation->raw_text, '[VERIFY')) {
            return 'pending';
        }

        $code = strtoupper((string) $citation->statute_code);
        if (! in_array($code, self::KNOWN_STATUTES, true)) {
            return 'suspect';
        }

        $section = (string) $citation->section_no;
        if ($section === '' || ! preg_match('/^[0-9A-Z()\-]+$/i', $section)) {
            return 'suspect';
        }

        return 'verified';
    }
}
