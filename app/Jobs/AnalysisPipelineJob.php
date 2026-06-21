<?php

namespace App\Jobs;

use App\Models\CaseAnalysis;
use App\Models\Document;
use App\Services\AarambhAi\AarambhAiClient;
use App\Services\AarambhAi\AnalysisOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pattern C — async background job that runs the full analysis pipeline.
 *
 * Stages (writes pipeline_stage + pipeline_progress on the CaseAnalysis row
 * after each step so the polling UI shows live progress):
 *
 *   1. ingesting (1/N)..(N/N)  → /v1/ingest per Document w/o ocr_text
 *   2. building case profile   → /v1/architect (refresh state_json)
 *   3. analysing               → /v1/analyse → fill analysis body
 *   4. complete                → flip pipeline_status='done' + status='editing'
 *
 * On failure: pipeline_status='failed' + pipeline_error populated.
 */
class AnalysisPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Bigger than typical wall-clock; per-call timeouts handle individual steps. */
    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public int $analysisId,
        public ?string $userSummary = null,
    ) {
    }

    public function handle(): void
    {
        // Build via factory methods — these classes use static ::make() with
        // explicit env reads and aren't registered in the container.
        $ai = AarambhAiClient::make();
        $orchestrator = AnalysisOrchestrator::make();

        $analysis = CaseAnalysis::with('case')->find($this->analysisId);
        if (! $analysis) {
            Log::warning('AnalysisPipelineJob: analysis not found', ['id' => $this->analysisId]);
            return;
        }

        $analysis->update([
            'pipeline_status' => 'running',
            'pipeline_stage' => 'starting',
            'pipeline_progress' => 5,
            'pipeline_started_at' => now(),
        ]);

        try {
            $case = $analysis->case;

            // Stage 1: ingest each Document that has no OCR text yet
            $documents = $case->documents()->orderBy('created_at')->get();
            $needIngest = $documents->filter(fn (Document $d) => empty($d->ocr_text));
            $totalIngest = $needIngest->count();

            if ($documents->isEmpty()) {
                throw new \RuntimeException('No documents on this case to analyse.');
            }

            // Parallel ingest in chunks of 3. Caps memory + Gemini concurrency
            // while still giving ~3x speedup vs sequential. Per-doc failures
            // don't kill the whole batch — successes are persisted, failures
            // are logged and surfaced via pipeline_error.
            $ingestErrors = [];
            $chunks = $needIngest->values()->chunk(3);
            $processed = 0;

            foreach ($chunks as $chunkIdx => $chunk) {
                $analysis->update([
                    'pipeline_stage' => sprintf('Reading %d documents in parallel (%d/%d done)', $chunk->count(), $processed, $totalIngest),
                    'pipeline_progress' => 10 + (int) (60 * ($processed / max($totalIngest, 1))),
                ]);

                // Build files array; skip docs whose file is missing on disk
                $files = [];
                $docsForChunk = [];
                foreach ($chunk as $doc) {
                    // Use Storage facade so the 'local' disk's 'private/' root
                    // is applied — bare storage_path('app/...') skips it.
                    $absPath = Storage::disk('local')->path($doc->stored_path);
                    if (! is_file($absPath)) {
                        Log::warning('Document file missing on disk', ['doc' => $doc->id, 'path' => $absPath]);
                        continue;
                    }
                    $files[] = ['path' => $absPath, 'name' => $doc->original_filename];
                    $docsForChunk[] = $doc;
                }

                if (empty($files)) {
                    continue;
                }

                $results = $ai->ingestBatch($files);

                foreach ($results as $idx => $result) {
                    $doc = $docsForChunk[$idx] ?? null;
                    if (! $doc) {
                        continue;
                    }
                    if ($result instanceof \Throwable) {
                        Log::error('parallel-ingest doc failed', [
                            'doc' => $doc->id,
                            'name' => $doc->original_filename,
                            'err' => $result->getMessage(),
                        ]);
                        $ingestErrors[] = "{$doc->original_filename}: ".substr($result->getMessage(), 0, 100);
                        continue;
                    }
                    $doc->update([
                        'detected_doc_type' => $result['detected_doc_type'] ?? null,
                        'language' => $result['language'] ?? null,
                        'ocr_text' => $result['cleaned_markdown'] ?? null,
                        'structured_fields' => $result['structured_fields'] ?? [],
                        'ingest_model_used' => $result['model_used'] ?? null,
                        'ingest_tokens_in' => $result['tokens_in'] ?? null,
                        'ingest_tokens_out' => $result['tokens_out'] ?? null,
                        'ingest_confidence' => $result['confidence'] ?? null,
                        'ingested_at' => now(),
                    ]);
                }

                $processed += $chunk->count();
            }

            if (! empty($ingestErrors)) {
                // Don't fail the whole pipeline — analyse can still run on the
                // docs that succeeded. Surface the partial failure so user knows.
                $analysis->update([
                    'pipeline_error' => 'Some documents failed to read: '.implode('; ', $ingestErrors),
                ]);
            }

            // Stage 2 + 3: architect + analyse — already encapsulated in
            // AnalysisOrchestrator::generateInitial(). But we already have an
            // empty CaseAnalysis row; fill it in-place rather than creating a
            // new one. Build the same pieces inline.
            $analysis->update([
                'pipeline_stage' => 'Building case profile',
                'pipeline_progress' => 75,
            ]);

            // Re-load case + docs
            $case->refresh();
            $documents = $case->documents()->orderBy('created_at')->get();

            $docPack = $documents->map(fn (Document $d) => [
                'source_filename' => $d->original_filename,
                'detected_doc_type' => $d->detected_doc_type,
                'language' => $d->language,
                'cleaned_markdown' => $d->ocr_text,
                'structured_fields' => $d->structured_fields ?? [],
            ])->toArray();

            // architect — refresh state_json
            try {
                $arch = $ai->architect($docPack, $this->userSummary);
                if (! empty($arch['case_profile'])) {
                    $stateJson = $case->state_json ?? [];
                    $stateJson['extracted_facts'] = $arch['case_profile'];
                    $case->update(['state_json' => $stateJson]);
                }
            } catch (\Throwable $e) {
                Log::warning('Architect failed in pipeline', ['err' => $e->getMessage()]);
                // continue — analyse can still run with whatever state we have
            }

            $analysis->update([
                'pipeline_stage' => 'Strategic analysis (Pro 2.5)',
                'pipeline_progress' => 85,
            ]);

            // analyse — call orchestrator's slice but write into THIS analysis row
            $generated = $orchestrator->generateForExisting(
                analysis: $analysis,
                userSummary: $this->userSummary,
            );

            $analysis->update([
                'pipeline_status' => 'done',
                'pipeline_stage' => 'complete',
                'pipeline_progress' => 100,
                'pipeline_finished_at' => now(),
                'status' => 'editing',
            ]);
        } catch (Throwable $e) {
            Log::error('AnalysisPipelineJob failed', [
                'analysis' => $this->analysisId,
                'err' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $analysis->update([
                'pipeline_status' => 'failed',
                'pipeline_stage' => 'failed',
                'pipeline_finished_at' => now(),
                'pipeline_error' => substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }
}
