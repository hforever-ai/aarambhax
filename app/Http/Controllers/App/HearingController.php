<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CaseRecord;
use App\Models\Hearing;
use Illuminate\Http\Request;

class HearingController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $upcoming = Hearing::where('user_id', $userId)
            ->where('date', '>=', today())
            ->with('case')
            ->orderBy('date')->orderBy('time')->get();
        $past = Hearing::where('user_id', $userId)
            ->where('date', '<', today())
            ->with('case')
            ->orderByDesc('date')->limit(20)->get();
        return view('app.hearings.index', compact('upcoming', 'past'));
    }

    public function create(Request $request)
    {
        $cases = CaseRecord::where('user_id', auth()->id())->where('status', 'active')->orderBy('title')->get();
        $selectedCaseId = $request->query('case_id');
        return view('app.hearings.new', compact('cases', 'selectedCaseId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'case_id' => ['required', 'exists:case_records,id'],
            'date'    => ['required', 'date'],
            'time'    => ['nullable', 'date_format:H:i'],
            'court'   => ['nullable', 'string', 'max:200'],
            'purpose' => ['nullable', 'string', 'max:200'],
        ]);
        $case = CaseRecord::findOrFail($data['case_id']);
        abort_if((int) $case->user_id !== (int) auth()->id(), 403);

        Hearing::create(array_merge($data, ['user_id' => auth()->id()]));
        return redirect()->route('app.cases.show', $case)->with('edit_success', 'Hearing added. Telegram reminder will go out 24 hours before if you have alerts enabled.');
    }

    public function destroy(Hearing $hearing)
    {
        abort_if((int) $hearing->user_id !== (int) auth()->id(), 403);
        $hearing->delete();
        return back()->with('edit_success', 'Hearing deleted.');
    }
}
