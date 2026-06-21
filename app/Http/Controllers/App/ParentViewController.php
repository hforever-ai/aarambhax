<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\StudentNote;
use App\Models\StudentPYQAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParentViewController extends Controller
{
    // Student generates/views their share link
    public function shareLink(Request $request)
    {
        $user = $request->user();
        if (! $user->parent_token) {
            $user->update(['parent_token' => Str::random(32)]);
        }
        return response()->json(['url' => route('parent.view', $user->parent_token)]);
    }

    // Public parent view — no auth required, token-gated
    public function view(string $token)
    {
        $student = User::where('parent_token', $token)->firstOrFail();

        $logs = DailyLog::where('user_id', $student->id)
            ->orderByDesc('log_date')
            ->limit(14)
            ->get();

        $todayLog = $logs->first()?->log_date?->isToday() ? $logs->first() : null;

        $totalHours   = $logs->sum('hours_studied');
        $studyDays    = $logs->where('hours_studied', '>', 0)->count();
        $avgMood      = $logs->whereNotNull('mood')->avg('mood');
        $totalExpense = $logs->sum('expenses');

        $weakTopics = StudentPYQAttempt::where('user_id', $student->id)
            ->where('status', 'not_understood')
            ->with('pyq')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($a) => $a->pyq?->topic)
            ->filter()
            ->unique()
            ->values();

        $noteCount = StudentNote::where('user_id', $student->id)->count();

        return view('app.parent_view', compact(
            'student', 'logs', 'todayLog',
            'totalHours', 'studyDays', 'avgMood', 'totalExpense',
            'weakTopics', 'noteCount'
        ));
    }
}
