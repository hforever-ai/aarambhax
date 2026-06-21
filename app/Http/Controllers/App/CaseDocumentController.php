<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\IngestDocumentsJob;
use App\Models\CaseRecord;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Case-scoped document upload — separate from CaseAnalysisController which
 * bundles upload+analysis. This controller is upload-ONLY: drop files, ingest
 * them, return to the Documents tab with status badges. Analysis / Draft /
 * Brainstorm tabs then pick whichever docs they want to use.
 *
 * Route: POST /app/cases/{case}/documents
 */
class CaseDocumentController extends Controller
{
    public function store(Request $request, CaseRecord $case)
    {
        $this->authorise($case);

        // Cap at 15 MB per file. With native PDF input to Gemini, ingests of
        // 11-12 MB phone-scanned PDFs reliably complete in 60-90 seconds —
        // well under Cloudflare's 100s tunnel timeout. 17+ MB starts to risk
        // timeouts. (Async ingest via Gemini File API would lift this cap to
        // 50+ MB; deferred until needed.)
        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'max:15360', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif'],
        ], [
            'files.*.max' => 'Each file must be 15 MB or smaller. For larger files (e.g. full court judgments), split into parts and upload separately.',
        ]);

        try {
            $createdDocs = $this->persistUploads($case, $data['files'], (int) $request->user()->id);
        } catch (Throwable $e) {
            Log::error('case-document-persist failed', ['err' => $e->getMessage()]);
            return back()->withErrors(['files' => 'File save failed: '.$e->getMessage()]);
        }

        if (empty($createdDocs)) {
            // Idempotent dedup means user re-uploaded the same files —
            // not an error, just nothing new to ingest.
            return redirect()
                ->route('app.cases.tab.documents', $case)
                ->with('flash', 'No new files (duplicates skipped).');
        }

        // Trigger ingest-only background job — parallel-3, no analysis.
        try {
            IngestDocumentsJob::dispatch($case->id);
        } catch (Throwable $e) {
            Log::warning('case-document-dispatch failed (non-fatal)', ['err' => $e->getMessage()]);
            // Files are saved; user can manually trigger ingest later
        }

        $count = count($createdDocs);
        return redirect()
            ->route('app.cases.tab.documents', $case)
            ->with('flash', "{$count} ".Str::plural('document', $count).' uploaded — processing in background.');
    }

    public function destroy(Request $request, CaseRecord $case, Document $document)
    {
        $this->authorise($case);
        if ((int) $document->case_id !== (int) $case->id) {
            abort(404);
        }
        try {
            if ($document->stored_path) {
                Storage::disk('local')->delete($document->stored_path);
            }
        } catch (\Throwable) {
            // OK — file may have been removed
        }
        $document->delete();
        return back()->with('flash', 'Document deleted.');
    }

    /**
     * Persist uploaded files to disk + create Document rows. Idempotent by
     * SHA-256 hash so retrying a failed upload doesn't double-save.
     *
     * @return array<int, Document>  newly-created Document rows (skips dedupes)
     */
    private function persistUploads(CaseRecord $case, array $files, int $userId): array
    {
        $created = [];
        $dir = "cases/{$case->id}/documents";
        $disk = Storage::disk('local');

        foreach ($files as $uploaded) {
            if (! $uploaded || ! $uploaded->isValid()) {
                continue;
            }

            $hash = hash_file('sha256', $uploaded->getRealPath());
            $existing = Document::where('case_id', $case->id)
                ->where('content_sha256', $hash)
                ->first();
            if ($existing) {
                continue;
            }

            $ext = $uploaded->getClientOriginalExtension() ?: 'pdf';
            $stored = $disk->putFileAs($dir, $uploaded, Str::random(20).'.'.$ext);
            $created[] = Document::create([
                'case_id' => $case->id,
                'user_id' => $userId,
                'original_filename' => $uploaded->getClientOriginalName(),
                'stored_path' => $stored,
                'mime_type' => $uploaded->getClientMimeType(),
                'bytes' => $uploaded->getSize(),
                'content_sha256' => $hash,
            ]);
        }

        return $created;
    }

    private function authorise(CaseRecord $case): void
    {
        abort_if((int) $case->user_id !== (int) auth()->id(), 403);
    }
}
