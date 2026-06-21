<?php

namespace App\Http\Controllers\App;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Models\CaseRecord;
use App\Models\Draft;
use App\Services\DraftEditor\EditOrchestrator;
use App\Services\Quota\UserApiQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DraftController extends Controller
{
    public function index()
    {
        $drafts = Draft::where('user_id', auth()->id())
            ->latest('updated_at')->paginate(20);
        return view('app.drafts.index', compact('drafts'));
    }

    public function create()
    {
        $cases = CaseRecord::where('user_id', auth()->id())->where('status', 'active')->get();
        return view('app.drafts.new', compact('cases'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:500'],
            'forum'       => ['required', 'in:cg_hc,cg_district,cg_revenue,tribunal'],
            'category'    => ['required', 'string', 'max:50'],
            'draft_type'  => ['required', 'string', 'max:100'],
            'language'    => ['required', 'in:en,hi,bilingual'],
            'case_id'     => ['nullable', 'exists:case_records,id'],

            // Free-form facts captured from the wizard form. Stored as JSON.
            'parties'     => ['nullable', 'string'],
            'court_name'  => ['nullable', 'string', 'max:200'],
            'case_no'     => ['nullable', 'string', 'max:100'],
            'jurisdiction'=> ['nullable', 'string', 'max:100'],
            'key_facts'   => ['nullable', 'string'],
            'khasra_no'   => ['nullable', 'string', 'max:100'],
            'khata_no'    => ['nullable', 'string', 'max:100'],
        ]);

        $contextFacts = array_filter([
            'parties' => $data['parties'] ?? null,
            'court_name' => $data['court_name'] ?? null,
            'case_no' => $data['case_no'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? null,
            'key_facts' => $data['key_facts'] ?? null,
            'khasra_no' => $data['khasra_no'] ?? null,
            'khata_no' => $data['khata_no'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'draft', 1);
        } catch (QuotaExceededException $e) {
            return back()->withErrors(['quota' => $e->getMessage()])->withInput();
        }

        $draft = Draft::create([
            'user_id' => auth()->id(),
            'case_id' => $data['case_id'] ?? null,
            'title' => $data['title'],
            'forum' => $data['forum'],
            'category' => $data['category'],
            'draft_type' => $data['draft_type'],
            'language' => $data['language'],
            'context_facts' => $contextFacts,
            'context_legal' => ['cited_sections' => [], 'cited_judgments' => []],
            'context_user_prefs' => [],
            'current_content_md' => '',
            'status' => 'drafting',
        ]);

        // Generate initial draft (uses GeminiClient — stubs cleanly without API key)
        try {
            EditOrchestrator::make()->generateInitial($draft);
        } catch (\Throwable $e) {
            // Fall through — user can retry from editor
            session()->flash('draft_warning', 'Initial generation failed: '.$e->getMessage().'. You can retry from the editor.');
        }

        return redirect()->route('app.drafts.show', $draft->id);
    }

    public function show(Draft $draft)
    {
        $this->authorise($draft);
        $draft->load(['messages', 'citations']);
        return view('app.drafts.show', compact('draft'));
    }

    public function applyEdit(Request $request, Draft $draft)
    {
        $this->authorise($draft);

        $data = $request->validate([
            'intent'           => ['required', 'in:rewrite_section,tighten,add_citation,free_form'],
            'instruction'      => ['nullable', 'string', 'max:2000'],
            'selection_text'   => ['nullable', 'string'],
            'selection_start'  => ['nullable', 'integer', 'min:0'],
            'selection_end'    => ['nullable', 'integer', 'min:0'],
            'target_section_id'=> ['nullable', 'string', 'max:100'],
        ]);

        try {
            UserApiQuota::fromConfig()->checkAndConsume($request->user(), 'draft_edit', 1, $draft);
        } catch (QuotaExceededException $e) {
            return back()->withErrors(['quota' => $e->getMessage()]);
        }

        try {
            $result = EditOrchestrator::make()->applyEdit($draft, $data);
        } catch (\Throwable $e) {
            return back()->withErrors(['edit' => $e->getMessage()]);
        }

        return redirect()->route('app.drafts.show', $draft->id)
            ->with('edit_success', 'Edit applied. Review the diff in the chat sidebar.');
    }

    public function revert(Request $request, Draft $draft)
    {
        $this->authorise($draft);
        $data = $request->validate(['snapshot_id' => ['required', 'integer']]);
        EditOrchestrator::make()->revertToSnapshot($draft, (int) $data['snapshot_id']);
        return redirect()->route('app.drafts.show', $draft->id)->with('edit_success', 'Reverted to snapshot.');
    }

    public function destroy(Draft $draft)
    {
        $this->authorise($draft);
        $draft->delete();
        return redirect()->route('app.drafts.index')->with('edit_success', 'Draft deleted.');
    }

    private function authorise(Draft $draft): void
    {
        abort_if((int) $draft->user_id !== (int) auth()->id(), 403);
    }
}
