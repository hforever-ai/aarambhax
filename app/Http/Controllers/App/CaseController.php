<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CaseRecord;
use App\Models\Client;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index()
    {
        $cases = CaseRecord::where('user_id', auth()->id())
            ->with(['client', 'hearings' => fn ($q) => $q->where('date', '>=', today())->orderBy('date')->limit(1)])
            ->latest('updated_at')->paginate(20);
        return view('app.cases.index', compact('cases'));
    }

    public function create()
    {
        $clients = Client::where('user_id', auth()->id())->orderBy('name')->get();
        return view('app.cases.new', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'forum' => ['required', 'in:cg_hc,cg_district,cg_revenue,tribunal'],
            'court_name' => ['nullable', 'string', 'max:200'],
            'case_no' => ['nullable', 'string', 'max:100'],
            'jurisdiction' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'opposing_party' => ['nullable', 'string', 'max:500'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'khasra_no' => ['nullable', 'string', 'max:100'],
            'khata_no' => ['nullable', 'string', 'max:100'],
            'gram' => ['nullable', 'string', 'max:100'],
            'tehsil' => ['nullable', 'string', 'max:100'],
            'jila' => ['nullable', 'string', 'max:100'],
        ]);

        $case = CaseRecord::create(array_merge($data, [
            'user_id' => auth()->id(),
            'status' => 'active',
        ]));

        return redirect()->route('app.cases.show', $case)->with('edit_success', 'Case created.');
    }

    /**
     * Legacy /app/cases/{case} route — redirect to the Details tab so old
     * bookmarks keep working. The premium tabbed shell lives at the per-tab
     * routes below.
     */
    public function show(CaseRecord $case)
    {
        $this->authorise($case);
        return redirect()->route('app.cases.tab.details', $case);
    }

    public function tabDetails(CaseRecord $case)
    {
        return $this->renderTab($case, 'details');
    }

    public function tabDocuments(CaseRecord $case)
    {
        return $this->renderTab($case, 'documents');
    }

    public function tabAnalysis(CaseRecord $case)
    {
        return $this->renderTab($case, 'analysis');
    }

    public function tabDraft(CaseRecord $case)
    {
        return $this->renderTab($case, 'draft');
    }

    public function tabBrainstorm(CaseRecord $case)
    {
        return $this->renderTab($case, 'brainstorm');
    }

    public function tabBahas(CaseRecord $case)
    {
        return $this->renderTab($case, 'bahas');
    }

    public function tabCrossExam(CaseRecord $case)
    {
        return $this->renderTab($case, 'cross_exam');
    }

    public function tabSamjhao(CaseRecord $case)
    {
        return $this->renderTab($case, 'samjhao');
    }

    public function tabNotes(CaseRecord $case)
    {
        return $this->renderTab($case, 'notes');
    }

    /**
     * Single render path for all 5 tabs. Each tab includes the same shell
     * (header + tab nav) and a tab-specific blade partial. Eager-loads only
     * what the active tab needs so we don't pay for hearing/draft queries
     * when the user is just looking at Brainstorm.
     */
    private function renderTab(CaseRecord $case, string $activeTab)
    {
        $this->authorise($case);

        // Always load lightweight aggregates needed by the header + tab nav badges
        $case->loadCount(['documents', 'karyas', 'drafts']);
        $case->load(['client']);

        $tabData = match ($activeTab) {
            'details' => [
                'hearings' => $case->hearings()->orderBy('date', 'desc')->limit(20)->get(),
            ],
            'documents' => [
                'documents' => $case->documents()->latest()->get(),
            ],
            'analysis' => [
                'documents' => $case->documents()->whereNotNull('ocr_text')->get(),
                'pastWorks' => $case->karyas()
                    ->whereIn('type', ['case_brief', 'timeline', 'argument_synopsis'])
                    ->latest()->limit(20)->get(),
            ],
            'draft' => array_merge($this->chatTabData($case, 'draft'), [
                'pastWorks' => $case->karyas()
                    ->where('type', 'reply_to_notice')
                    ->latest()->limit(20)->get(),
                'drafts' => $case->drafts()->latest('updated_at')->limit(20)->get(),
            ]),
            'brainstorm' => $this->chatTabData($case, 'brainstorm'),
            'bahas' => $this->chatTabData($case, 'bahas'),
            'cross_exam' => $this->chatTabData($case, 'cross_exam'),
            'samjhao' => $this->chatTabData($case, 'samjhao'),
            'notes'   => $this->chatTabData($case, 'notes'),
            default => [],
        };

        return view('app.cases.tabs.shell', array_merge([
            'case' => $case,
            'activeTab' => $activeTab,
        ], $tabData));
    }

    public function close(CaseRecord $case)
    {
        $this->authorise($case);
        $case->update(['status' => 'closed']);
        return back()->with('edit_success', 'Case closed.');
    }

    public function reopen(CaseRecord $case)
    {
        $this->authorise($case);
        $case->update(['status' => 'active']);
        return back()->with('edit_success', 'Case reopened.');
    }

    public function destroy(CaseRecord $case)
    {
        $this->authorise($case);
        $case->delete();
        return redirect()->route('app.cases.index')->with('edit_success', 'Case deleted.');
    }

    private function authorise(CaseRecord $case): void
    {
        abort_if((int) $case->user_id !== (int) auth()->id(), 403);
    }

    /**
     * Generic chat-tab data loader — used by Brainstorm / Bahas / Cross-Exam
     * / Samjhao tabs. Each kind gets its own conversation thread on the case.
     * ConversationOrchestrator::getOrCreate(case, user, kind) ensures the
     * right thread is loaded.
     */
    private function chatTabData(CaseRecord $case, string $kind): array
    {
        $orchestrator = \App\Services\AarambhAi\ConversationOrchestrator::make();
        $conversation = $orchestrator->getOrCreate($case, request()->user(), $kind);
        $conversation->load(['activeMessages' => fn ($q) => $q->orderBy('created_at')]);

        return [
            'documents' => $case->documents()->whereNotNull('ocr_text')->get(),
            'conversation' => $conversation,
            'messages' => $conversation->activeMessages,
        ];
    }
}
