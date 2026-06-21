<?php

namespace App\Services;

use App\Models\PYQQuestion;
use App\Models\StudentNote;
use App\Models\StudentPYQAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PYQMatchingService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model  = config('services.gemini.model_free_lite', 'gemini-2.5-flash-lite');
    }

    /**
     * Identify which predefined topics appear in the note, store them, return matched PYQs.
     */
    public function matchForNote(StudentNote $note): void
    {
        $source = $note->organised_md ?: $note->ocr_text ?: $note->note_text;
        if (! $source) return;

        $allTopics = implode(', ', PYQQuestion::allTopics());

        $response = $this->call("You are a JEE content classifier.

A student's study note is given below. Identify which of these JEE topics are CLEARLY covered in the note.

AVAILABLE TOPICS:
{$allTopics}

STUDENT NOTE:
{$source}

Return ONLY a JSON array of matching topic names from the list above (exact spelling).
Maximum 5 topics. Only include topics that are clearly and directly covered.
Example: [\"Kinematics\", \"Newton's Laws and Friction\"]
Return empty array [] if nothing matches.");

        $json  = preg_replace('/^```(?:json)?\s*/m', '', trim($response));
        $json  = preg_replace('/\s*```$/m', '', $json);
        $topics = json_decode($json, true);

        if (! is_array($topics)) $topics = [];

        // Filter to only valid topics
        $valid = array_intersect($topics, PYQQuestion::allTopics());

        $note->update(['matched_topics' => array_values($valid)]);
    }

    /**
     * Fetch PYQs for the note's matched topics, with user's previous attempts attached.
     */
    public function getPYQsForNote(StudentNote $note, int $userId, int $limit = 6): Collection
    {
        $topics = $note->matched_topics ?? [];
        if (empty($topics)) return collect();

        $pyqs = PYQQuestion::whereIn('topic', $topics)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        if ($pyqs->isEmpty()) return collect();

        // Attach attempt status for this user
        $attempts = StudentPYQAttempt::where('user_id', $userId)
            ->whereIn('pyq_id', $pyqs->pluck('id'))
            ->pluck('status', 'pyq_id');

        return $pyqs->map(function ($pyq) use ($attempts) {
            $pyq->attempt_status = $attempts->get($pyq->id);
            return $pyq;
        });
    }

    private function call(string $prompt): string
    {
        try {
            $data = \App\Services\Gemini\GeminiClient::callWithRotation($this->model, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1],
            ], $this->apiKey, 30);
        } catch (\Throwable) {
            return '[]';
        }

        return data_get($data, 'candidates.0.content.parts.0.text', '[]');
    }
}
