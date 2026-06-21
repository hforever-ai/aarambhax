<?php

namespace App\Http\Controllers;

use App\Services\Citations\CitationExtractor;
use App\Services\Citations\CitationVerifier;
use Illuminate\Http\Request;

/**
 * Public standalone Verifier — paste any draft, get citation badges.
 * No auth, no DB writes. Lead-gen tool that demonstrates Aarambhax's value.
 *
 * Lawttorney has this as a separate top-nav product. We match the affordance.
 */
class VerifierController extends Controller
{
    public function show()
    {
        return view('pages.verifier', [
            'draft_text' => '',
            'citations' => null,
            'stats' => null,
        ]);
    }

    public function check(Request $request, CitationExtractor $extractor, CitationVerifier $verifier)
    {
        $data = $request->validate([
            'draft_text' => ['required', 'string', 'min:50', 'max:50000'],
        ]);

        $extracted = $extractor->extract($data['draft_text']);
        $citations = collect($extracted)->map(function ($c) {
            $status = $this->classifyForPublic($c);
            return [
                'type' => $c['type'],
                'raw_text' => $c['raw_text'],
                'statute_code' => $c['statute_code'],
                'section_no' => $c['section_no'],
                'verification_status' => $status,
                'reason' => $this->reasonFor($status, $c),
            ];
        });

        $stats = [
            'total' => $citations->count(),
            'verified' => $citations->where('verification_status', 'verified')->count(),
            'suspect'  => $citations->where('verification_status', 'suspect')->count(),
            'pending'  => $citations->where('verification_status', 'pending')->count(),
        ];

        return view('pages.verifier', [
            'draft_text' => $data['draft_text'],
            'citations' => $citations,
            'stats' => $stats,
        ]);
    }

    /** Replicates CitationVerifier logic without touching the DB. */
    private function classifyForPublic(array $c): string
    {
        if (str_contains($c['raw_text'], '[VERIFY')) return 'pending';

        if ($c['type'] === 'judgment') return 'pending'; // needs Indian Kanoon

        $known = ['BNS', 'BNSS', 'BSA', 'CRPC', 'IPC', 'IEA', 'CG-LRC', 'CG-RENT',
                  'NI-ACT', 'HMA', 'DV-ACT', 'POCSO', 'NDPS', 'RERA', 'MV-ACT', 'CPC'];
        if (! in_array(strtoupper((string) $c['statute_code']), $known, true)) {
            return 'suspect';
        }
        if (! preg_match('/^[0-9A-Z()\-]+$/i', (string) $c['section_no'])) {
            return 'suspect';
        }
        return 'verified';
    }

    private function reasonFor(string $status, array $c): string
    {
        return match ($status) {
            'verified'  => 'Statute and section number look syntactically valid.',
            'suspect'   => $c['type'] === 'statute_section'
                ? 'Unknown statute code or non-standard section format.'
                : 'Format looks off.',
            'pending'   => $c['type'] === 'judgment'
                ? 'Judgment citations need Indian Kanoon API to verify (Phase 4).'
                : 'Marked [VERIFY] by author — needs manual check.',
            default     => '',
        };
    }
}
