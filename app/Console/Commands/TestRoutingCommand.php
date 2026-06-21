<?php

namespace App\Console\Commands;

use App\Services\Routing\DraftRouter;
use Illuminate\Console\Command;

class TestRoutingCommand extends Command
{
    protected $signature = 'aarambhax:test-routing';
    protected $description = 'Test DraftRouter against the 5 connected case-bundle scenarios';

    public function handle(): int
    {
        $router = DraftRouter::make();

        $bundles = [
            [
                'name' => 'Firoz Khan POCSO',
                'expected_draft' => 'Criminal Appeal',
                'uploaded' => ['lower_court_judgment', 'charge_sheet', 'medical_report', 'fsl_report', 'school_record', 'section_164_statement'],
                'filters' => ['forum' => 'cg_hc'],
            ],
            [
                'name' => 'Aman Civil Revision',
                'expected_draft' => 'Civil Revision',
                'uploaded' => ['lower_court_order', 'vakalatnama', 'cheque'],
                'filters' => ['forum' => 'cg_hc'],
            ],
            [
                'name' => 'Amit Agrawal SLP',
                'expected_draft' => 'SLP (Civil)',
                'uploaded' => ['lower_court_order', 'lower_court_judgment'],
                'filters' => ['forum' => 'sc'],
            ],
            [
                'name' => 'Shreya Banking Writ',
                'expected_draft' => 'Writ Petition (Civil) Article 226',
                'uploaded' => ['legal_notice', 'bank_statement', 'lower_court_order', 'aadhaar'],
                'filters' => ['forum' => 'cg_hc'],
            ],
            [
                'name' => 'NDPS standalone (FIR only)',
                'expected_draft' => 'Anticipatory Bail OR §482 Quashing — disambiguation expected',
                'uploaded' => ['FIR'],
                'filters' => [],
            ],
            [
                'name' => 'Revenue land matter (Pathalgaon Khasra 132)',
                'expected_draft' => 'Application u/s 115 CGLRC',
                'uploaded' => ['sale_deed', 'khasra', 'khatauni', 'mutation_entry'],
                'filters' => ['forum' => 'cg_revenue'],
            ],
            [
                'name' => 'DV Act matter (Hindi)',
                'expected_draft' => 'DV Act §12 Application',
                'uploaded' => ['aadhaar', 'legal_notice', 'prior_complaint', 'school_record'],
                'filters' => ['forum' => 'cg_district', 'language' => 'hi'],
            ],
        ];

        foreach ($bundles as $b) {
            $this->newLine();
            $this->line('═══════════════════════════════════════════════════════════════════');
            $this->line('  TEST: '.$b['name']);
            $this->line('  Uploaded: '.implode(', ', $b['uploaded']));
            $this->line('  Expected: '.$b['expected_draft']);
            $this->line('═══════════════════════════════════════════════════════════════════');

            $r = $router->recommend($b['uploaded'], $b['filters']);

            $this->line('  MODE: '.strtoupper($r['mode']));

            if ($r['mode'] === 'auto') {
                $rec = $r['recommendation'];
                $this->info('  → AUTO PICK: '.$rec['draft_type']);
                $this->line('    Forum: '.$rec['forum'].'  |  Court: '.$rec['court'].'  |  Lang: '.$rec['language']);
                $this->line('    Score: '.$rec['score'].'  |  Required match: '.($rec['required_match_ratio'] * 100).'%');
                $this->line('    Sample file: '.$rec['source_filename']);
                if (! empty($r['alternatives'])) {
                    $this->line('    Alternatives:');
                    foreach ($r['alternatives'] as $alt) {
                        $this->line('      - '.$alt['draft_type'].' (score '.$alt['score'].')');
                    }
                }
            } elseif ($r['mode'] === 'disambiguate') {
                $this->warn('  → NEEDS DISAMBIGUATION ('.count($r['candidates']).' candidates)');
                foreach ($r['candidates'] as $i => $c) {
                    $this->line(sprintf('    [%d] %s (score %d, %s, %s)', $i, $c['draft_type'], $c['score'], $c['forum'], $c['language']));
                }
                if (isset($r['llm_advice']['primary_recommendation_index'])) {
                    $advice = $r['llm_advice'];
                    $idx = $advice['primary_recommendation_index'];
                    $picked = $r['candidates'][$idx]['draft_type'] ?? '?';
                    $this->info('    → LLM recommends [#'.$idx.']: '.$picked.' (confidence: '.($advice['confidence'] ?? '?').')');
                    $this->line('      Reasoning: '.($advice['reasoning'] ?? ''));
                    if (! empty($advice['questions_for_advocate'])) {
                        $this->line('      Questions for Vikash bhai:');
                        foreach ($advice['questions_for_advocate'] as $q) {
                            $this->line('        • '.$q);
                        }
                    }
                }
            } else {
                $this->error('  → GAP: '.($r['message'] ?? 'no match'));
            }
        }

        return self::SUCCESS;
    }
}
