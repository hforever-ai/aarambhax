<?php

namespace App\Services\AarambhAi;

use App\Models\DraftCatalogueEntry;
use App\Services\Routing\DraftRouter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * End-to-end orchestrator: takes N uploaded files (PDFs + images), runs them
 * through the VPS pipeline (ingest → architect → route → draft), returns the
 * generated draft to the controller.
 *
 * Single round-trip from the user's perspective. Multiple LLM calls behind
 * the scenes (~60-90s end-to-end).
 */
class PipelineOrchestrator
{
    public function __construct(
        private readonly AarambhAiClient $ai,
        private readonly DraftRouter $router,
    ) {
    }

    public static function make(): self
    {
        return new self(AarambhAiClient::make(), DraftRouter::make());
    }

    /**
     * @param  UploadedFile[]  $files
     * @param  array{forum?:string,language?:string,user_summary?:string,desired_draft_type?:string}  $hints
     * @return array  full pipeline result with stages logged
     */
    public function generateFromFiles(array $files, array $hints = []): array
    {
        if (! $this->ai->isConfigured()) {
            throw new RuntimeException('Aarambh AI client not configured. Check AARAMBH_API_URL / AARAMBH_API_TOKEN.');
        }
        if (empty($files)) {
            throw new RuntimeException('No files uploaded');
        }

        $started = microtime(true);
        $stages = [];

        // ─── Stage 1: Ingest each file in turn ─────────────────────────────
        $docPack = [];
        $stage1Start = microtime(true);
        foreach ($files as $i => $file) {
            $tmpPath = $file->getRealPath();
            $name = $file->getClientOriginalName();
            try {
                $ingest = $this->ai->ingest($tmpPath, $name);
                $docPack[] = array_merge($ingest, ['source_filename' => $name]);
            } catch (\Throwable $e) {
                Log::warning('aarambh-ingest failed', ['file' => $name, 'err' => $e->getMessage()]);
                $docPack[] = [
                    'source_filename' => $name,
                    'detected_doc_type' => 'unknown',
                    'cleaned_markdown' => '',
                    'structured_fields' => [],
                    'error' => $e->getMessage(),
                ];
            }
        }
        $stages['ingest'] = [
            'files' => count($files),
            'successful' => count(array_filter($docPack, fn ($d) => empty($d['error']))),
            'doc_types_detected' => array_values(array_unique(array_column($docPack, 'detected_doc_type'))),
            'elapsed_ms' => (int) ((microtime(true) - $stage1Start) * 1000),
        ];

        // ─── Stage 2: Fact Architect ───────────────────────────────────────
        $stage2Start = microtime(true);
        $architectResult = $this->ai->architect($docPack, $hints['user_summary'] ?? null);
        $caseProfile = $architectResult['case_profile'] ?? [];
        $stages['architect'] = [
            'parties_extracted' => count($caseProfile['parties'] ?? []),
            'chronology_events' => count($caseProfile['chronology'] ?? []),
            'sections_invoked' => $caseProfile['sections_invoked'] ?? [],
            'gaps_detected' => $caseProfile['gaps_detected'] ?? [],
            'elapsed_ms' => (int) ((microtime(true) - $stage2Start) * 1000),
        ];

        // ─── Stage 3: Router (pick a catalogue template) ───────────────────
        $detected = array_values(array_unique(array_filter(
            array_column($docPack, 'detected_doc_type'),
            fn ($t) => $t && $t !== 'unknown'
        )));
        $filters = array_filter([
            'forum' => $hints['forum'] ?? null,
            'language' => $hints['language'] ?? null,
        ]);
        $routing = $this->router->recommend($detected, $filters);
        $catalogueEntry = null;
        if ($routing['mode'] === 'auto' && ! empty($routing['recommendation']['id'])) {
            $catalogueEntry = DraftCatalogueEntry::find($routing['recommendation']['id']);
        } elseif ($routing['mode'] === 'disambiguate' && ! empty($routing['candidates'])) {
            // Take LLM's pick; fall back to top-scored candidate
            $idx = $routing['llm_advice']['primary_recommendation_index'] ?? 0;
            $pick = $routing['candidates'][$idx] ?? $routing['candidates'][0];
            $catalogueEntry = DraftCatalogueEntry::find($pick['id']);
        }
        // If a desired draft type was hinted, prefer it over the router's pick
        if (! empty($hints['desired_draft_type'])) {
            $hinted = DraftCatalogueEntry::where('draft_type', $hints['desired_draft_type'])
                ->when(! empty($filters['language']), fn ($q) => $q->where('language', $filters['language']))
                ->first();
            if ($hinted) {
                $catalogueEntry = $hinted;
            }
        }
        $stages['routing'] = [
            'mode' => $routing['mode'],
            'detected_doc_types' => $detected,
            'picked_template' => $catalogueEntry?->draft_type,
            'picked_template_id' => $catalogueEntry?->id,
            'picked_template_filename' => $catalogueEntry?->source_filename,
        ];

        // ─── Stage 4: Draft (the user-facing output) ───────────────────────
        $stage4Start = microtime(true);
        $forum = $hints['forum'] ?? $catalogueEntry?->forum ?? 'cg_hc';
        $draftType = $catalogueEntry?->draft_type ?? ($hints['desired_draft_type'] ?? 'Application');
        $language = $hints['language'] ?? $catalogueEntry?->language ?? 'en';
        $draftResp = $this->ai->draft(
            forum: $forum,
            draftType: $draftType,
            language: $language,
            caseProfile: $caseProfile,
            fewShotTemplate: $catalogueEntry?->cleaned_text,
            researchBlock: null,
        );
        $stages['draft'] = [
            'forum' => $forum,
            'draft_type' => $draftType,
            'language' => $language,
            'flagged_blanks' => $draftResp['flagged_blanks'] ?? [],
            'tokens_in' => $draftResp['tokens_in'] ?? 0,
            'tokens_out' => $draftResp['tokens_out'] ?? 0,
            'elapsed_ms' => (int) ((microtime(true) - $stage4Start) * 1000),
        ];

        return [
            'draft_markdown' => $draftResp['draft_markdown'] ?? '',
            'sections' => $draftResp['sections'] ?? [],
            'flagged_blanks' => $draftResp['flagged_blanks'] ?? [],
            'case_profile' => $caseProfile,
            'doc_pack' => $docPack,
            'catalogue_entry' => $catalogueEntry ? [
                'id' => $catalogueEntry->id,
                'draft_type' => $catalogueEntry->draft_type,
                'forum' => $catalogueEntry->forum,
                'court' => $catalogueEntry->court,
                'source_filename' => $catalogueEntry->source_filename,
            ] : null,
            'stages' => $stages,
            'total_elapsed_ms' => (int) ((microtime(true) - $started) * 1000),
        ];
    }
}
