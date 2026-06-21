<?php

namespace App\Http\Controllers\App;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Services\AarambhAi\PipelineOrchestrator;
use App\Services\Quota\UserApiQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * "Drop client files → get draft" — the primary user-facing flow.
 *
 *   GET  /app/upload          show drop-zone
 *   POST /app/upload          accept multipart files + hints, run pipeline,
 *                             persist Draft, redirect to existing draft show
 */
class DraftFromFilesController extends Controller
{
    public function show()
    {
        return view('app.upload.show');
    }

    public function generate(Request $request)
    {
        // Up to 10 files, 25 MB each. PDFs + images.
        $data = $request->validate([
            'files'              => ['required', 'array', 'min:1', 'max:10'],
            'files.*'            => ['file', 'max:25600', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif'],
            'forum'              => ['nullable', 'string', 'max:50'],
            'language'           => ['nullable', 'string', 'max:10'],
            'desired_draft_type' => ['nullable', 'string', 'max:200'],
            'user_summary'       => ['nullable', 'string', 'max:2000'],
        ]);

        $hints = array_filter([
            'forum'              => $data['forum'] ?? null,
            'language'           => $data['language'] ?? null,
            'desired_draft_type' => $data['desired_draft_type'] ?? null,
            'user_summary'       => $data['user_summary'] ?? null,
        ]);

        // Quota — full upload pipeline = 1 unit (regardless of file count).
        // The pipeline does ingest×N + architect + draft, but Vikash bhai's
        // mental model is "1 attempt to convert files into a draft."
        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'upload_pipeline', 1);
        } catch (QuotaExceededException $e) {
            return back()->withErrors(['quota' => $e->getMessage()])->withInput();
        }

        try {
            $result = PipelineOrchestrator::make()->generateFromFiles($data['files'], $hints);
        } catch (Throwable $e) {
            Log::error('upload-pipeline', ['err' => $e->getMessage()]);
            return back()->withErrors(['pipeline' => 'AI pipeline failed: '.$e->getMessage()])->withInput();
        }

        // Persist as a Draft so the user can edit further in the existing editor
        $forum = $result['stages']['draft']['forum'];
        $draftType = $result['stages']['draft']['draft_type'];
        $language = $result['stages']['draft']['language'];

        $draft = Draft::create([
            'user_id'             => auth()->id(),
            'title'               => $draftType.' — '.now()->format('d M Y H:i'),
            'forum'               => $forum,
            'category'            => 'auto',
            'draft_type'          => $draftType,
            'language'            => $language,
            'context_facts'       => $result['case_profile'],
            'context_legal'       => ['cited_sections' => [], 'cited_judgments' => []],
            'context_user_prefs'  => [
                'pipeline_stages'  => $result['stages'],
                'doc_pack_summary' => array_map(fn ($d) => [
                    'filename' => $d['source_filename'] ?? null,
                    'type' => $d['detected_doc_type'] ?? null,
                    'language' => $d['language'] ?? null,
                ], $result['doc_pack']),
                'flagged_blanks' => $result['flagged_blanks'],
                'catalogue_entry_used' => $result['catalogue_entry'],
                'total_elapsed_ms' => $result['total_elapsed_ms'],
            ],
            'current_content_md'  => $result['draft_markdown'],
            'status'              => 'editing',
        ]);

        return redirect()->route('app.drafts.show', $draft->id)
            ->with('edit_success', 'Draft generated from '.count($data['files']).' file(s). Review the [TO BE FILLED] markers before filing.');
    }
}
