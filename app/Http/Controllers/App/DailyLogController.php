<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'studied_topics' => ['nullable', 'string', 'max:500'],
            'hours_studied'  => ['nullable', 'numeric', 'min:0', 'max:24'],
            'mood'           => ['nullable', 'integer', 'min:1', 'max:5'],
            'food'           => ['nullable', 'string', 'max:300'],
            'expenses'       => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        DailyLog::updateOrCreate(
            ['user_id' => $request->user()->id, 'log_date' => today()->toDateString()],
            $data
        );

        return redirect()->route('app.dashboard')->with('flash', 'Aaj ka log save ho gaya! 👍');
    }
}
