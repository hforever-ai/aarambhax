<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\StudentNote;
use App\Services\PYQMatchingService;
use App\Services\StudentNoteAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentNoteController extends Controller
{
    public function index(Request $request)
    {
        $userId        = $request->user()->id;
        $filterSubject = $request->query('subject');
        $filterClass   = $request->query('class_name');
        $search        = $request->query('q');
        $viewMode      = $request->query('view', 'subject'); // subject | month | revision

        $query = StudentNote::where('user_id', $userId);
        if ($filterSubject) $query->where('subject', $filterSubject);
        if ($filterClass)   $query->where('class_name', $filterClass);
        if ($search)        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('note_text', 'like', "%{$search}%")
              ->orWhere('ocr_text', 'like', "%{$search}%");
        });

        if ($viewMode === 'revision') {
            // Oldest-scanned first — only notes that have been processed
            $notes = $query->where('ai_status', 'done')->orderBy('updated_at', 'asc')->get();
        } else {
            $notes = $query->latest()->get();
        }

        $allSubjects = StudentNote::where('user_id', $userId)->whereNotNull('subject')->distinct()->pluck('subject');
        $allClasses  = StudentNote::where('user_id', $userId)->whereNotNull('class_name')->distinct()->pluck('class_name');

        return view('app.student_notes.index', compact(
            'notes', 'allSubjects', 'allClasses', 'filterSubject', 'filterClass', 'search', 'viewMode'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'    => ['nullable', 'string', 'max:100'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'title'      => ['nullable', 'string', 'max:200'],
            'image'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:20480'],
            'note_text'  => ['nullable', 'string', 'max:5000'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('student-notes', 'public');
        }

        $note = StudentNote::create([
            'user_id'    => $request->user()->id,
            'subject'    => $data['subject'] ?? null,
            'class_name' => $data['class_name'] ?? null,
            'title'      => $data['title'] ?? null,
            'image_path' => $imagePath,
            'note_text'  => $data['note_text'] ?? null,
        ]);

        return redirect()->route('app.student-notes.show', $note)
            ->with('flash', 'Note saved. Scan it to extract and organise the content.');
    }

    public function show(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);

        $matchedPYQs = (new PYQMatchingService())->getPYQsForNote($note, $request->user()->id);

        return view('app.student_notes.show', compact('note', 'matchedPYQs'));
    }

    public function scan(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);

        try {
            (new StudentNoteAiService())->scan($note);
            $msg = 'Notes scanned and organised.';
        } catch (\Throwable $e) {
            $msg = 'Scan failed: '.$e->getMessage();
        }

        return redirect()->route('app.student-notes.show', $note)->with('flash', $msg);
    }

    public function generateQuestions(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);

        try {
            (new StudentNoteAiService())->generateQuestions($note);
            $msg = 'Practice questions generated.';
        } catch (\Throwable $e) {
            $msg = 'Failed: '.$e->getMessage();
        }

        return redirect()->route('app.student-notes.show', $note)->with('flash', $msg);
    }

    public function polish(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);

        try {
            (new StudentNoteAiService())->polish($note);
            $msg = 'Notes polished.';
        } catch (\Throwable $e) {
            $msg = 'Failed: '.$e->getMessage();
        }

        return redirect()->route('app.student-notes.show', $note)->with('flash', $msg);
    }

    public function formulaSheet(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);
        try {
            (new StudentNoteAiService())->formulaSheet($note);
            $msg = 'Formula sheet generated.';
        } catch (\Throwable $e) {
            $msg = 'Failed: '.$e->getMessage();
        }
        return redirect()->route('app.student-notes.show', [$note, 'tab' => 'insights'])->with('flash', $msg);
    }

    public function flashcards(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);
        try {
            (new StudentNoteAiService())->flashcards($note);
            $msg = 'Flashcards generated.';
        } catch (\Throwable $e) {
            $msg = 'Failed: '.$e->getMessage();
        }
        return redirect()->route('app.student-notes.show', [$note, 'tab' => 'insights'])->with('flash', $msg);
    }

    public function askAi(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);
        $question = $request->validate(['question' => ['required', 'string', 'max:500']])['question'];
        try {
            $answer = (new StudentNoteAiService())->askAi($note, $question);
            return response()->json(['answer' => $answer]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeFromYoutube(Request $request)
    {
        $data = $request->validate([
            'youtube_url' => ['required', 'string', 'max:500'],
            'subject'     => ['nullable', 'string', 'max:100'],
            'class_name'  => ['nullable', 'string', 'max:100'],
        ]);

        $service = new StudentNoteAiService();

        if (! file_exists(base_path('scripts/fetch_transcript.py')) && ! function_exists('proc_open')) {
            return back()->withErrors(['youtube_url' => 'YouTube transcript feature is not available on this server. Please type your notes manually.']);
        }

        try {
            $transcript = $service->fetchYoutubeTranscript($data['youtube_url']);
        } catch (\Throwable $e) {
            return back()->withErrors(['youtube_url' => $e->getMessage()]);
        }

        $note = StudentNote::create([
            'user_id'     => $request->user()->id,
            'subject'     => $data['subject'] ?? null,
            'class_name'  => $data['class_name'] ?? null,
            'youtube_url' => $data['youtube_url'],
            'source_type' => 'youtube',
            'note_text'   => $transcript,
        ]);

        try {
            $service->organiseTranscript($note);
        } catch (\Throwable $e) {
            return redirect()->route('app.student-notes.show', $note)
                ->with('flash', 'Transcript fetched but AI organising failed: '.$e->getMessage());
        }

        return redirect()->route('app.student-notes.show', $note)
            ->with('flash', 'YouTube transcript fetched and organised.');
    }

    public function destroy(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) abort(403);

        if ($note->image_path) {
            Storage::disk('public')->delete($note->image_path);
        }

        $note->delete();
        return redirect()->route('app.dashboard')->with('flash', 'Note deleted.');
    }
}
