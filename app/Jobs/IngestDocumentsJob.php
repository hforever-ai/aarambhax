<?php

namespace App\Jobs;

use App\Models\CaseRecord;
use App\Models\Document;
use App\Services\AarambhAi\AarambhAiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Lightweight ingest-only job for the Documents tab. Decoupled from
 * AnalysisPipelineJob so uploading is purely "save + extract text," with
 * no analysis triggered until the user explicitly runs one from the
 * Analysis / Draft / Brainstorm tab.
 *
 * Parallel ingest in chunks of 3 (same pattern as AnalysisPipelineJob).
 * Per-doc failures don't kill the batch.
 */
class IngestDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(public int $caseId)
    {
    }

    public function handle(): void
    {
        $case = CaseRecord::find($this->caseId);
        if (! $case) {
            Log::warning('IngestDocumentsJob: case not found', ['id' => $this->caseId]);
            return;
        }

        $needIngest = $case->documents()
            ->whereNull('ocr_text')
            ->orderBy('created_at')
            ->get();

        if ($needIngest->isEmpty()) {
            Log::info('IngestDocumentsJob: nothing to ingest', ['case' => $this->caseId]);
            return;
        }

        $ai = AarambhAiClient::make();
        $errors = [];
        $chunks = $needIngest->chunk(3);

        foreach ($chunks as $chunk) {
            $files = [];
            $docsForChunk = [];
            foreach ($chunk as $doc) {
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
                if ($result instanceof Throwable) {
                    Log::error('ingest doc failed', [
                        'doc' => $doc->id,
                        'name' => $doc->original_filename,
                        'err' => $result->getMessage(),
                    ]);
                    // Mark as failed in DB so the UI shows "✗ Failed" instead of
                    // looping "Reading…" forever. The view checks "tokens_in
                    // populated AND ocr_text empty" → failed state.
                    $doc->update([
                        'ingest_tokens_in' => 0,
                        'ingest_tokens_out' => 0,
                        'ingested_at' => now(),
                    ]);
                    $errors[] = "{$doc->original_filename}: ".substr($result->getMessage(), 0, 100);
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
        }

        Log::info('IngestDocumentsJob: complete', [
            'case' => $this->caseId,
            'ingested' => $needIngest->count() - count($errors),
            'failed' => count($errors),
        ]);
    }
}
