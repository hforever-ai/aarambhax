<?php

namespace App\Jobs;

use App\Models\Karya;
use App\Services\AarambhAi\KaryaOrchestrator;
use App\Services\Quota\UserApiQuota;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Async runner for a single Karya. Reuses the same cron-based queue worker
 * (queue:work --stop-when-empty) that AnalysisPipelineJob uses. No new infra.
 *
 * Stages written live to the Karya row so the show-page polling UI animates:
 *   queued → running → done | failed
 *
 * Ingest is NOT done here — Karya assumes the selected Documents already have
 * ocr_text from earlier upload-time ingestion. If a doc is unprocessed it's
 * silently dropped from the doc_pack. (UI should not let user pick raw docs.)
 */
class KaryaPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public int $karyaId,
        public ?string $userInstruction = null,
    ) {
    }

    public function handle(): void
    {
        $karya = Karya::with('case')->find($this->karyaId);
        if (! $karya) {
            Log::warning('KaryaPipelineJob: karya not found', ['id' => $this->karyaId]);
            return;
        }

        $karya->update([
            'pipeline_status' => 'running',
            'pipeline_stage' => 'starting',
            'pipeline_progress' => 10,
            'pipeline_started_at' => now(),
            'pipeline_error' => null,
        ]);

        try {
            $karya->update([
                'pipeline_stage' => 'Composing prompt + calling Gemini',
                'pipeline_progress' => 35,
            ]);

            $orchestrator = KaryaOrchestrator::make();
            $result = $orchestrator->generate($karya, $this->userInstruction);

            $karya->update([
                'pipeline_status' => 'done',
                'pipeline_stage' => 'complete',
                'pipeline_progress' => 100,
                'pipeline_finished_at' => now(),
                'output_markdown' => $result['output_markdown'],
                'output_json' => $result['output_json'] ?? [],
                'model_used' => $result['model_used'],
                'tier' => $result['tier'] ?? null,
                'tokens_in' => $result['tokens_in'],
                'tokens_out' => $result['tokens_out'],
                'cost_inr_paise' => (int) ($result['cost_inr_paise'] ?? 0),
                'paid_equivalent_paise' => (int) ($result['paid_equivalent_paise'] ?? 0),
                'pii_redactions' => (int) ($result['pii_redactions'] ?? 0),
            ]);

            // Update the matching api_usage_logs row with post-call cost details
            // so the admin cost dashboard can aggregate accurately.
            try {
                UserApiQuota::fromConfig()->updateLastLog(
                    $karya->user,
                    $karya,
                    [
                        'tier' => $result['tier'] ?? null,
                        'model_used' => $result['model_used'] ?? null,
                        'cost_inr_paise' => (int) ($result['cost_inr_paise'] ?? 0),
                        'paid_equivalent_paise' => (int) ($result['paid_equivalent_paise'] ?? 0),
                        'tokens_in' => (int) ($result['tokens_in'] ?? 0),
                        'tokens_out' => (int) ($result['tokens_out'] ?? 0),
                    ]
                );
            } catch (Throwable $e) {
                Log::warning('KaryaPipelineJob: usage log update failed', ['err' => $e->getMessage()]);
            }

            Log::info('KaryaPipelineJob: complete', [
                'karya' => $karya->id,
                'type' => $karya->type,
                'model' => $result['model_used'],
                'tier' => $result['tier'] ?? '?',
                'tokens_in' => $result['tokens_in'],
                'tokens_out' => $result['tokens_out'],
                'cost_paise' => $result['cost_inr_paise'] ?? 0,
                'paid_equiv_paise' => $result['paid_equivalent_paise'] ?? 0,
                'pii_redactions' => $result['pii_redactions'] ?? 0,
                'elapsed_ms' => $result['elapsed_ms'],
            ]);
        } catch (Throwable $e) {
            Log::error('KaryaPipelineJob: failed', [
                'karya' => $this->karyaId,
                'err' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $karya->update([
                'pipeline_status' => 'failed',
                'pipeline_stage' => 'failed',
                'pipeline_finished_at' => now(),
                'pipeline_error' => substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }
}
