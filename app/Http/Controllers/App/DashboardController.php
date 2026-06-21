<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CaseRecord;
use App\Models\DailyLog;
use App\Models\Draft;
use App\Models\Hearing;
use App\Models\StudentNote;
use App\Services\Quota\UserApiQuota;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        $recentDrafts = Draft::where('user_id', $userId)
            ->latest('updated_at')->limit(6)->get();

        $upcomingHearings = Hearing::where('user_id', $userId)
            ->where('date', '>=', today())
            ->orderBy('date')->limit(5)->get();

        $activeCases = CaseRecord::where('user_id', $userId)
            ->where('status', 'active')->count();

        $quotaUsage = UserApiQuota::fromConfig()->currentUsage($user);

        if ($user->isStudent()) {
            $filterSubject = request('subject');
            $filterClass   = request('class_name');
            $notesQuery = StudentNote::where('user_id', $userId);
            if ($filterSubject) $notesQuery->where('subject', $filterSubject);
            if ($filterClass)   $notesQuery->where('class_name', $filterClass);
            $studentNotes = $notesQuery->latest()->limit(100)->get();
            $allSubjects  = StudentNote::where('user_id', $userId)->whereNotNull('subject')->distinct()->pluck('subject');
            $allClasses   = StudentNote::where('user_id', $userId)->whereNotNull('class_name')->distinct()->pluck('class_name');

            $todayLog = DailyLog::where('user_id', $userId)
                ->where('log_date', today()->toDateString())
                ->first();

            $practiceNote = StudentNote::where('user_id', $userId)
                ->whereNotNull('questions_json')
                ->latest('updated_at')
                ->first();

            $practiceQuestions = [];
            if ($practiceNote) {
                $allQs = json_decode($practiceNote->questions_json, true) ?? [];
                shuffle($allQs);
                $practiceQuestions = array_slice($allQs, 0, 3);
            }

            return view('app.dashboard_student', compact(
                'activeCases',
                'upcomingHearings',
                'studentNotes',
                'allSubjects',
                'allClasses',
                'filterSubject',
                'filterClass',
                'todayLog',
                'practiceNote',
                'practiceQuestions',
            ));
        }

        return view('app.dashboard', compact(
            'recentDrafts',
            'upcomingHearings',
            'activeCases',
            'quotaUsage',
        ));
    }
}
