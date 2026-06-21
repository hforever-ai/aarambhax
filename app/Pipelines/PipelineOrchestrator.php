<?php

namespace App\Pipelines;

use App\Models\Post;
use App\Models\PostPipelineRun;
use App\Models\PostPipelineStep;
use App\Pipelines\Prompts\DraftEnPrompt;
use App\Pipelines\Prompts\OutlinePrompt;
use App\Pipelines\Prompts\TranslateHiPrompt;
use App\Services\Citations\CitationExtractor;
use App\Services\Citations\CitationVerifier;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates the editorial pipeline. Each public method advances the state
 * machine by one transition and logs an audit step.
 *
 * State machine (simplified):
 *   idea → outline_draft → outline_review → outline_approved
 *   → draft_en → en_review → en_approved
 *   → (optional draft_hi → hi_review → both_approved)
 *   → assets_ready → published
 */
class PipelineOrchestrator
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly CitationExtractor $extractor,
        private readonly CitationVerifier $verifier,
    ) {
    }

    public static function make(): self
    {
        return new self(GeminiClient::make(), new CitationExtractor(), new CitationVerifier());
    }

    public function startRun(Post $post): PostPipelineRun
    {
        return DB::transaction(function () use ($post) {
            $run = $post->pipelineRuns()->create(['state' => 'idea']);
            $post->update(['current_pipeline_run_id' => $run->id]);
            return $run;
        });
    }

    public function generateOutline(PostPipelineRun $run, array $params): PostPipelineRun
    {
        $this->expectState($run, ['idea', 'outline_draft']);
        $start = microtime(true);
        $system = OutlinePrompt::system();
        $user = OutlinePrompt::user($params);

        $result = $this->gemini->generate($system, $user, usePro: false, temperature: 0.4);

        $outline = $this->safeJsonDecode($result['text']);

        $this->logStep($run, 'outline_gen', $system."\n\n---\n\n".$user, $result, $start, [
            'parsed_output' => $outline ? json_encode($outline, JSON_UNESCAPED_UNICODE) : null,
            'status' => $outline ? 'success' : 'failed',
            'error_message' => $outline ? null : 'Failed to parse outline JSON',
        ]);

        if ($outline) {
            $run->update([
                'state' => 'outline_review',
                'outline_json' => $outline,
            ]);
        }
        return $run->fresh();
    }

    public function approveOutline(PostPipelineRun $run): PostPipelineRun
    {
        $this->expectState($run, ['outline_review']);
        $run->update(['state' => 'outline_approved']);
        return $run;
    }

    public function generateDraftEn(PostPipelineRun $run, int $targetWords = 1800): PostPipelineRun
    {
        $this->expectState($run, ['outline_approved', 'draft_en']);
        if (! $run->outline_json) {
            throw new RuntimeException('Cannot draft without an approved outline.');
        }

        $start = microtime(true);
        $system = DraftEnPrompt::system();
        $user = DraftEnPrompt::user(json_encode($run->outline_json, JSON_UNESCAPED_UNICODE), $targetWords);

        $result = $this->gemini->generate($system, $user, usePro: true, temperature: 0.7);

        $this->logStep($run, 'draft_gen_en', $system."\n\n---\n\n".$user, $result, $start, [
            'parsed_output' => $result['text'],
            'status' => 'success',
        ]);

        $post = $run->post;
        $post->update([
            'body' => $result['text'],
            'language' => 'en',
        ]);

        $this->extractAndStoreCitations($post, $result['text']);

        $run->update(['state' => 'en_review']);
        return $run->fresh();
    }

    public function approveEn(PostPipelineRun $run, bool $bilingual = false): PostPipelineRun
    {
        $this->expectState($run, ['en_review']);
        $run->update(['state' => $bilingual ? 'en_approved' : 'both_approved']);
        return $run;
    }

    public function translateHi(PostPipelineRun $run): PostPipelineRun
    {
        $this->expectState($run, ['en_approved', 'draft_hi']);
        $start = microtime(true);
        $english = $run->post->body ?? '';
        $system = TranslateHiPrompt::system();
        $user = TranslateHiPrompt::user($english);

        $result = $this->gemini->generate($system, $user, usePro: true, temperature: 0.3);

        $this->logStep($run, 'translate_hi', $system."\n\n---\n\n".$user, $result, $start, [
            'parsed_output' => $result['text'],
            'status' => 'success',
        ]);

        $hiPost = $this->cloneAsHindi($run->post, $result['text']);
        $run->update(['state' => 'hi_review', 'notes' => 'Hindi clone created as post '.$hiPost->id]);
        return $run->fresh();
    }

    public function approveHi(PostPipelineRun $run): PostPipelineRun
    {
        $this->expectState($run, ['hi_review']);
        $run->update(['state' => 'both_approved']);
        return $run;
    }

    public function publish(PostPipelineRun $run): PostPipelineRun
    {
        $this->expectState($run, ['both_approved', 'assets_ready']);

        $post = $run->post;
        $post->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Run citation verifier one last time before publish
        $this->verifier->verifyAll($post->id);

        $run->update(['state' => 'published']);
        return $run->fresh();
    }

    public function unpublish(PostPipelineRun $run): PostPipelineRun
    {
        $run->post->update(['status' => 'draft']);
        $run->update(['state' => 'archived']);
        return $run;
    }

    // ─────────────────────────────────────────────────────────

    private function expectState(PostPipelineRun $run, array $allowed): void
    {
        if (! in_array($run->state, $allowed, true)) {
            throw new RuntimeException("Pipeline run #{$run->id} is in state '{$run->state}', expected one of: ".implode(', ', $allowed));
        }
    }

    private function logStep(PostPipelineRun $run, string $type, string $prompt, array $result, float $start, array $extra = []): PostPipelineStep
    {
        return PostPipelineStep::create(array_merge([
            'pipeline_run_id' => $run->id,
            'step_type' => $type,
            'model_used' => $result['model'] ?? null,
            'prompt' => $prompt,
            'raw_output' => $result['text'] ?? null,
            'tokens_input' => $result['tokens_input'] ?? null,
            'tokens_output' => $result['tokens_output'] ?? null,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'status' => 'success',
            'created_at' => now(),
        ], $extra));
    }

    private function safeJsonDecode(string $text): ?array
    {
        // Extract JSON object even if model wrapped in ```json ... ```
        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            $decoded = json_decode($m[0], true);
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    }

    private function extractAndStoreCitations(Post $post, string $body): void
    {
        $post->citations()->delete();
        foreach ($this->extractor->extract($body) as $cit) {
            $post->citations()->create([
                'citation_type' => $cit['type'],
                'raw_text' => $cit['raw_text'],
                'statute_code' => $cit['statute_code'],
                'section_no' => $cit['section_no'],
                'position_in_post' => $cit['position'],
                'verification_status' => 'pending',
            ]);
        }
        $this->verifier->verifyAll($post->id);
    }

    private function cloneAsHindi(Post $en, string $hindiBody): Post
    {
        return Post::create([
            'slug' => $en->slug.'-hi',
            'language' => 'hi',
            'translation_group_id' => $en->translation_group_id ?? $en->id,
            'category_id' => $en->category_id,
            'archetype' => $en->archetype,
            'title' => $en->title,
            'subtitle' => $en->subtitle,
            'excerpt' => $en->excerpt,
            'body' => $hindiBody,
            'meta_title' => $en->meta_title,
            'meta_description' => $en->meta_description,
            'author_id' => $en->author_id,
            'status' => 'draft',
            'reading_time_minutes' => $en->reading_time_minutes,
        ]);
    }
}
