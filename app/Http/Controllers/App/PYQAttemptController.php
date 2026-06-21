<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\PYQQuestion;
use App\Models\StudentNote;
use App\Models\StudentPYQAttempt;
use Illuminate\Http\Request;

class PYQAttemptController extends Controller
{
    public function store(Request $request, StudentNote $note, PYQQuestion $pyq)
    {
        if ($note->user_id !== $request->user()->id) abort(403);

        $status = $request->validate([
            'status' => ['required', 'in:understood,not_understood'],
        ])['status'];

        StudentPYQAttempt::updateOrCreate(
            ['user_id' => $request->user()->id, 'pyq_id' => $pyq->id],
            ['note_id' => $note->id, 'status' => $status]
        );

        return response()->json(['status' => $status]);
    }
}
