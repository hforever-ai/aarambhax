<?php

namespace App\Console\Commands;

use App\Models\PYQQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedPYQs extends Command
{
    protected $signature = 'zenith:seed-pyqs {--subject= : Only seed this subject} {--force : Re-seed even if topic already has questions}';
    protected $description = 'Generate JEE PYQ-style questions via Gemini and seed the database.';

    private string $apiKey;
    private string $model = 'gemini-2.5-flash-lite';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function handle(): int
    {
        $this->apiKey = config('services.gemini.api_key');
        if (! $this->apiKey) {
            $this->error('GEMINI_API_KEY not set.');
            return self::FAILURE;
        }

        $allTopics = PYQQuestion::topics();
        $filterSubject = $this->option('subject');
        $force = $this->option('force');

        foreach ($allTopics as $subject => $topics) {
            if ($filterSubject && strtolower($filterSubject) !== strtolower($subject)) continue;

            $this->info("\n── {$subject} ──");

            foreach ($topics as $topic) {
                $existing = PYQQuestion::where('subject', $subject)->where('topic', $topic)->count();

                if ($existing >= 12 && ! $force) {
                    $this->line("  ↷ {$topic} — already has {$existing} Qs, skipping");
                    continue;
                }

                $this->line("  Generating: {$topic}…");

                $questions = $this->generate($subject, $topic);

                if (empty($questions)) {
                    $this->warn("  ✗ {$topic} — empty response, skipping");
                    continue;
                }

                $inserted = 0;
                foreach ($questions as $q) {
                    try {
                        PYQQuestion::create([
                            'subject'    => $subject,
                            'topic'      => $topic,
                            'year'       => $q['year'] ?? rand(2018, 2024),
                            'type'       => $q['type'] ?? 'mcq',
                            'question'   => $q['question'] ?? '',
                            'options'    => $q['options'] ?? null,
                            'answer'     => (string) ($q['answer'] ?? ''),
                            'solution'   => $q['solution'] ?? null,
                            'difficulty' => (int) ($q['difficulty'] ?? 2),
                        ]);
                        $inserted++;
                    } catch (\Throwable $e) {
                        $this->warn("    skip malformed Q: ".$e->getMessage());
                    }
                }

                $this->info("  ✓ {$topic} — inserted {$inserted} questions");
                sleep(8); // 8s between calls — free-tier Gemini rate limit (~10 req/min)
            }
        }

        $total = PYQQuestion::count();
        $this->info("\nDone — {$total} total PYQs in database.");
        return self::SUCCESS;
    }

    private function generate(string $subject, string $topic): array
    {
        $prompt = "Generate 4 JEE Mains-style questions for {$subject} — {$topic}.
3 MCQs (options A/B/C/D) + 1 numerical. Year 2019-2024. Difficulty 1-3. Solution: 1 line max.
Return ONLY this JSON (no markdown, no fences, no explanation):
[{\"year\":2022,\"type\":\"mcq\",\"question\":\"...\",\"options\":[\"A. ...\",\"B. ...\",\"C. ...\",\"D. ...\"],\"answer\":\"B\",\"solution\":\"...\",\"difficulty\":2}]";

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout(120)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'     => 0.4,
                    'maxOutputTokens' => 32768,
                ],
            ]);

            if (! $response->successful()) {
                $this->warn("    API error: ".$response->status().' '.$response->body());
                return [];
            }

            $body = $response->json();

            // Log finish reason if not STOP (safety block, quota, etc.)
            $finishReason = $body['candidates'][0]['finishReason'] ?? 'UNKNOWN';
            if ($finishReason !== 'STOP') {
                $this->warn("    Blocked/skipped — finishReason: {$finishReason}");
                return [];
            }

            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $text = preg_replace('/^```(?:json)?\s*/m', '', trim($text));
            $text = preg_replace('/\s*```$/m', '', $text);

            if ($text === '') {
                $this->warn("    Empty text despite STOP. promptFeedback: ".json_encode($body['promptFeedback'] ?? []));
                return [];
            }

            $data = json_decode($text, true);
            if (! is_array($data)) {
                $this->warn("    JSON parse failed. Raw: ".substr($text, 0, 300));
                return [];
            }
            if (empty($data)) {
                $this->warn("    Model returned empty array []");
                return [];
            }
            return $data;
        } catch (\Throwable $e) {
            $this->warn("    Exception: ".$e->getMessage());
            return [];
        }
    }
}
