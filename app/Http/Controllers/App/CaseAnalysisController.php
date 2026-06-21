<?php

namespace App\Http\Controllers\App;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Jobs\AnalysisPipelineJob;
use App\Models\CaseAnalysis;
use App\Models\CaseRecord;
use App\Models\Document;
use App\Services\AarambhAi\AarambhAiClient;
use App\Services\AarambhAi\AnalysisOrchestrator;
use App\Services\AarambhAi\ConvertChatToDraft;
use App\Services\Quota\UserApiQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CaseAnalysisController extends Controller
{
    public function create(CaseRecord $case)
    {
        $this->authorise($case);
        return view('app.case_analyses.new', [
            'case' => $case,
            'existingDocs' => $case->documents()->latest()->get(),
        ]);
    }

    /**
     * Pattern C: sync upload (3-5s) + async pipeline (60-180s in background).
     *
     *   1. Save files to disk + create Document rows (no AI yet, ~2s).
     *   2. Create CaseAnalysis row with pipeline_status='queued'.
     *   3. Dispatch AnalysisPipelineJob.
     *   4. Redirect to show page (lands instantly; UI polls for progress).
     */
    public function store(Request $request, CaseRecord $case)
    {
        $this->authorise($case);

        $data = $request->validate([
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:25600', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif'],
            'analysis_type' => ['nullable', 'in:strategic,document_review,theory_exploration'],
            'user_summary' => ['nullable', 'string', 'max:2000'],
        ]);

        // Step 1: persist uploaded files (idempotent by sha256). FAST — no AI.
        if (! empty($data['files'])) {
            try {
                $this->persistUploads($case, $data['files']);
            } catch (Throwable $e) {
                Log::error('case-analysis-persist', ['err' => $e->getMessage()]);
                return back()->withErrors(['files' => 'File save failed: '.$e->getMessage()])->withInput();
            }
        }

        if ($case->documents()->count() === 0) {
            return back()->withErrors(['files' => 'Add at least one document to this case before running analysis.'])->withInput();
        }

        // Quota check — throws if user is over limit. 1 unit per analysis run.
        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'analysis', 1);
        } catch (QuotaExceededException $e) {
            return back()->withErrors(['quota' => $e->getMessage()])->withInput();
        }

        // Step 2: create analysis row in queued state
        $analysis = CaseAnalysis::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'title' => 'Analysis · '.now()->format('d M Y H:i'),
            'language' => $case->state_json['language'] ?? 'en',
            'analysis_type' => $data['analysis_type'] ?? 'strategic',
            'context_facts' => [],
            'context_legal' => [],
            'context_user_prefs' => ['user_summary' => $data['user_summary'] ?? null],
            'current_content_md' => '',
            'status' => 'drafting',
            'pipeline_status' => 'queued',
            'pipeline_stage' => 'queued',
            'pipeline_progress' => 0,
        ]);

        // Step 3: dispatch background job
        AnalysisPipelineJob::dispatch(
            $analysis->id,
            $data['user_summary'] ?? null,
        );

        // Step 4: redirect to show page (fast — ~1-2s total)
        return redirect()->route('app.cases.analyses.show', [$case, $analysis]);
    }

    /**
     * GET /app/cases/{case}/analyses/{analysis}/status — JSON for polling.
     */
    public function status(CaseRecord $case, CaseAnalysis $analysis)
    {
        $this->authorise($case);
        if ($analysis->case_id !== $case->id) {
            abort(404);
        }
        return response()->json([
            'status' => $analysis->pipeline_status,
            'stage' => $analysis->pipeline_stage,
            'progress' => $analysis->pipeline_progress,
            'started_at' => $analysis->pipeline_started_at?->toIso8601String(),
            'finished_at' => $analysis->pipeline_finished_at?->toIso8601String(),
            'error' => $analysis->pipeline_error,
            'ready' => $analysis->pipeline_status === 'done',
        ]);
    }

    public function show(CaseRecord $case, CaseAnalysis $analysis)
    {
        $this->authorise($case);
        if ($analysis->case_id !== $case->id) {
            abort(404);
        }
        $analysis->load(['messages']);
        return view('app.case_analyses.show', compact('case', 'analysis'));
    }

    public function applyEdit(Request $request, CaseRecord $case, CaseAnalysis $analysis)
    {
        $this->authorise($case);
        if ($analysis->case_id !== $case->id) {
            abort(404);
        }

        $data = $request->validate([
            'intent' => ['required', 'in:rewrite_section,tighten,add_risk,suggest_precedent,free_form'],
            'instruction' => ['nullable', 'string', 'max:2000'],
            'selection_text' => ['nullable', 'string'],
            'selection_start' => ['nullable', 'integer', 'min:0'],
            'selection_end' => ['nullable', 'integer', 'min:0'],
            'target_section_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'analysis_edit', 1, $analysis);
        } catch (QuotaExceededException $e) {
            return back()->withErrors(['quota' => $e->getMessage()]);
        }

        try {
            AnalysisOrchestrator::make()->applyEdit($analysis, $data);
        } catch (Throwable $e) {
            return back()->withErrors(['edit' => $e->getMessage()]);
        }

        return redirect()->route('app.cases.analyses.show', [$case, $analysis])
            ->with('edit_success', 'Refinement applied.');
    }

    public function convertToDraft(Request $request, CaseRecord $case, CaseAnalysis $analysis)
    {
        $this->authorise($case);
        if ($analysis->case_id !== $case->id) {
            abort(404);
        }

        $data = $request->validate([
            'forum' => ['nullable', 'string', 'max:50'],
            'draft_type' => ['nullable', 'string', 'max:200'],
            'language' => ['nullable', 'in:en,hi,bilingual'],
        ]);

        try {
            $draft = ConvertChatToDraft::make()->fromAnalysis(
                analysis: $analysis,
                user: $request->user(),
                forumOverride: $data['forum'] ?? null,
                draftTypeOverride: $data['draft_type'] ?? null,
                languageOverride: $data['language'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('analysis-convert-to-draft', ['err' => $e->getMessage()]);
            return back()->withErrors(['convert' => 'Convert failed: '.$e->getMessage()]);
        }

        return redirect()->route('app.drafts.show', $draft->id)
            ->with('edit_success', 'Draft generated from analysis. Review the [TO BE FILLED] markers before filing.');
    }

    public function destroy(CaseRecord $case, CaseAnalysis $analysis)
    {
        $this->authorise($case);
        if ($analysis->case_id !== $case->id) {
            abort(404);
        }
        $analysis->delete();
        return redirect()->route('app.cases.show', $case)->with('edit_success', 'Analysis deleted.');
    }

    /**
     * Pattern C step 1: persist files to disk + create Document rows. NO AI.
     * Idempotent by sha256: if (case, sha256) already exists, skip.
     * The actual ingest happens later in AnalysisPipelineJob.
     */
    private function persistUploads(CaseRecord $case, array $files): void
    {
        $disk = Storage::disk('local');
        $dir = "cases/{$case->id}/documents";

        foreach ($files as $uploaded) {
            $contents = file_get_contents($uploaded->getRealPath());
            $sha256 = hash('sha256', $contents);

            // Idempotency — skip if same content already on this case
            $existing = Document::where('case_id', $case->id)
                ->where('content_sha256', $sha256)
                ->first();
            if ($existing) {
                Log::info('persist-uploads: skipping duplicate', [
                    'case' => $case->id,
                    'filename' => $uploaded->getClientOriginalName(),
                    'sha256' => $sha256,
                ]);
                continue;
            }

            $ext = $uploaded->getClientOriginalExtension() ?: 'bin';
            $stored = $disk->putFileAs($dir, $uploaded, Str::random(20).'.'.$ext);

            Document::create([
                'case_id' => $case->id,
                'user_id' => auth()->id(),
                'original_filename' => $uploaded->getClientOriginalName(),
                'stored_path' => $stored,
                'mime_type' => $uploaded->getClientMimeType(),
                'bytes' => $uploaded->getSize(),
                'content_sha256' => $sha256,
                // detected_doc_type / ocr_text / etc. populated later by the job
            ]);
        }
    }

    private function authorise(CaseRecord $case): void
    {
        abort_if((int) $case->user_id !== (int) auth()->id(), 403);
    }
}
