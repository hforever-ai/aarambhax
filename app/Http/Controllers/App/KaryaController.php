<?php

namespace App\Http\Controllers\App;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Jobs\KaryaPipelineJob;
use App\Models\CaseRecord;
use App\Models\Karya;
use App\Services\AarambhAi\KaryaCatalogue;
use App\Services\Quota\UserApiQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * KaryaController — separate from CaseAnalysisController / DraftController per
 * the explicit separation rule. Karya is its own action surface.
 *
 * Flow mirrors Pattern C (sync POST → background job → poll status):
 *   create  → render Karya picker (5 types) + Document picker
 *   store   → validate, create Karya row, dispatch KaryaPipelineJob, redirect
 *   show    → render artifact + live progress poller
 *   status  → JSON for the poller
 *   destroy → permanent delete
 */
class KaryaController extends Controller
{
    /**
     * GET /app/cases/{case}/karyas/new — picker form (Karya type + documents).
     */
    public function create(CaseRecord $case)
    {
        $this->authorise($case);
        return view('app.karyas.new', [
            'case' => $case,
            'documents' => $case->documents()->latest()->get(),
            'catalogue' => KaryaCatalogue::grouped(),
        ]);
    }

    /**
     * POST /app/cases/{case}/karyas — create + dispatch.
     *
     *   1. Validate type + chosen document IDs are scoped to this case.
     *   2. Create Karya row with pipeline_status='queued'.
     *   3. Dispatch KaryaPipelineJob (cron worker picks it up within ~60s).
     *   4. Redirect to show page (lands instantly; UI polls).
     */
    public function store(Request $request, CaseRecord $case)
    {
        $this->authorise($case);

        $validTypes = array_keys(KaryaCatalogue::all());

        $data = $request->validate([
            'type' => ['required', Rule::in($validTypes)],
            'language' => ['nullable', Rule::in(['en', 'hi', 'hinglish', 'bilingual'])],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
            'user_instruction' => ['nullable', 'string', 'max:2000'],
        ]);

        // Defense in depth: every chosen doc must belong to this case AND must
        // be ingested. Drop unrelated/raw docs silently to keep the orchestrator
        // honest if someone bypasses the UI.
        $allowedIds = $case->documents()
            ->whereIn('id', $data['document_ids'])
            ->whereNotNull('ocr_text')
            ->pluck('id')
            ->all();

        if (empty($allowedIds)) {
            return back()
                ->withErrors(['document_ids' => 'Pick at least one ingested document. Newly uploaded docs may still be processing — wait a moment and refresh.'])
                ->withInput();
        }

        $entry = KaryaCatalogue::get($data['type']);
        $language = $data['language'] ?? $entry['default_lang'];

        // Quota check — throws QuotaExceededException if user is over limit.
        // We check BEFORE creating the Karya row so a quota-blocked attempt
        // doesn't leave half-state in the DB.
        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'karya', 1);
        } catch (QuotaExceededException $e) {
            return back()->withErrors(['quota' => $e->getMessage()])->withInput();
        }

        try {
            $karya = Karya::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'type' => $data['type'],
                'title' => $entry['label_en'].' · '.now()->format('d M Y H:i'),
                'language' => $language,
                'input_document_ids' => array_values($allowedIds),
                'parameters' => [],
                'pipeline_status' => 'queued',
                'pipeline_stage' => 'queued',
                'pipeline_progress' => 0,
            ]);

            KaryaPipelineJob::dispatch(
                $karya->id,
                $data['user_instruction'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('karya.store failed', ['err' => $e->getMessage()]);
            return back()->withErrors(['type' => 'Could not start Karya: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('app.cases.karyas.show', [$case, $karya]);
    }

    /**
     * GET /app/cases/{case}/karyas/{karya} — artifact + poller.
     */
    public function show(CaseRecord $case, Karya $karya)
    {
        $this->authorise($case);
        $this->scopeOrAbort($case, $karya);
        $karya->load('messages');

        $entry = KaryaCatalogue::get($karya->type);

        return view('app.karyas.show', [
            'case' => $case,
            'karya' => $karya,
            'catalogueEntry' => $entry,
            'documents' => $karya->inputDocuments(),
        ]);
    }

    /**
     * GET /app/cases/{case}/karyas/{karya}/status — JSON for live polling.
     */
    public function status(CaseRecord $case, Karya $karya)
    {
        $this->authorise($case);
        $this->scopeOrAbort($case, $karya);

        return response()->json([
            'status' => $karya->pipeline_status,
            'stage' => $karya->pipeline_stage,
            'progress' => (int) $karya->pipeline_progress,
            'started_at' => $karya->pipeline_started_at?->toIso8601String(),
            'finished_at' => $karya->pipeline_finished_at?->toIso8601String(),
            'error' => $karya->pipeline_error,
            'ready' => $karya->pipeline_status === 'done',
            'output_md_len' => $karya->output_markdown ? strlen($karya->output_markdown) : 0,
        ]);
    }

    /**
     * DELETE /app/cases/{case}/karyas/{karya} — permanent delete.
     */
    public function destroy(CaseRecord $case, Karya $karya)
    {
        $this->authorise($case);
        $this->scopeOrAbort($case, $karya);
        $karya->delete();
        return redirect()->route('app.cases.show', $case)->with('flash', 'Karya deleted.');
    }

    private function authorise(CaseRecord $case): void
    {
        abort_if((int) $case->user_id !== (int) auth()->id(), 403);
    }

    private function scopeOrAbort(CaseRecord $case, Karya $karya): void
    {
        if ((int) $karya->case_id !== (int) $case->id) {
            abort(404);
        }
    }
}
