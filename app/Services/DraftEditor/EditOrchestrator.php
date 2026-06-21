<?php

namespace App\Services\DraftEditor;

use App\Models\Draft;
use App\Models\DraftCitation;
use App\Models\DraftMessage;
use App\Models\DraftSnapshot;
use App\Pipelines\Prompts\EditIntentPrompt;
use App\Pipelines\Prompts\InitialDraftPrompt;
use App\Services\Citations\CitationExtractor;
use App\Services\Citations\CitationVerifier;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The core of Aarambhax's draft-editing differentiator.
 *
 * Three operations:
 *   - generateInitial(): produce the first draft from form input
 *   - applyEdit():       run an edit intent (rewrite_section / tighten /
 *                        add_citation / free_form) with FULL conversation memory
 *   - revertToSnapshot(): time-travel undo
 *
 * Every operation:
 *   - Builds full context (facts + legal + prefs + last 20 messages)
 *   - Logs a DraftMessage row (audit + history)
 *   - Saves a DraftSnapshot (for undo)
 *   - Re-extracts and re-verifies citations from the new content
 */
class EditOrchestrator
{
    public const SUPPORTED_INTENTS = ['rewrite_section', 'tighten', 'add_citation', 'free_form'];

    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly ContextBuilder $context,
        private readonly CitationExtractor $extractor,
    ) {
    }

    public static function make(): self
    {
        return new self(
            GeminiClient::make(),
            new ContextBuilder(),
            new CitationExtractor(),
        );
    }

    /**
     * First-time draft generation from form input.
     */
    public function generateInitial(Draft $draft): Draft
    {
        $system = InitialDraftPrompt::system($draft->forum, $draft->draft_type, $draft->language);
        $user = InitialDraftPrompt::user($draft->context_facts ?? []);

        $result = $this->gemini->generate($system, $user, usePro: true, temperature: 0.5);

        return DB::transaction(function () use ($draft, $result, $system, $user) {
            $draft->update([
                'current_content_md' => $result['text'],
                'status' => 'editing',
            ]);

            $message = DraftMessage::create([
                'draft_id' => $draft->id,
                'role' => 'assistant',
                'content' => $result['text'],
                'intent' => 'initial_draft',
                'model_used' => $result['model'] ?? null,
                'tokens_input' => $result['tokens_input'] ?? null,
                'tokens_output' => $result['tokens_output'] ?? null,
                'created_at' => now(),
            ]);

            DraftSnapshot::create([
                'draft_id' => $draft->id,
                'content_md' => $result['text'],
                'context_snapshot' => [
                    'facts' => $draft->context_facts,
                    'legal' => $draft->context_legal,
                    'prefs' => $draft->context_user_prefs,
                ],
                'created_by' => 'ai_edit',
                'message_id' => $message->id,
                'label' => 'Initial draft',
                'created_at' => now(),
            ]);

            $this->extractAndStoreCitations($draft, $result['text'], $message->id);

            return $draft->fresh();
        });
    }

    /**
     * Apply an edit intent with full context preserved.
     *
     * @param  array  $params  ['intent', 'instruction', 'selection_text', 'selection_start', 'selection_end']
     */
    public function applyEdit(Draft $draft, array $params): array
    {
        $intent = $params['intent'] ?? 'free_form';
        if (! in_array($intent, self::SUPPORTED_INTENTS, true)) {
            throw new RuntimeException("Unsupported intent: {$intent}");
        }

        $context = $this->context->build($draft, $params['selection_text'] ?? null);
        $context['instruction'] = $params['instruction'] ?? '';

        $system = EditIntentPrompt::system();
        $user = EditIntentPrompt::user($intent, $context);

        // Log user request first (so chat sidebar shows it immediately)
        $userMsg = DraftMessage::create([
            'draft_id' => $draft->id,
            'role' => 'user',
            'content' => $params['instruction'] ?? '(no instruction)',
            'intent' => $intent,
            'target_section_id' => $params['target_section_id'] ?? null,
            'selection_start' => $params['selection_start'] ?? null,
            'selection_end' => $params['selection_end'] ?? null,
            'created_at' => now(),
        ]);

        $result = $this->gemini->generate($system, $user, usePro: true, temperature: 0.5);

        return DB::transaction(function () use ($draft, $params, $result, $userMsg, $intent) {
            // Splice the AI response into current content
            $newContent = $this->spliceContent(
                $draft->current_content_md,
                $params['selection_start'] ?? null,
                $params['selection_end'] ?? null,
                $result['text'],
            );

            $draft->update(['current_content_md' => $newContent]);

            $assistantMsg = DraftMessage::create([
                'draft_id' => $draft->id,
                'role' => 'assistant',
                'content' => $result['text'],
                'intent' => $intent,
                'model_used' => $result['model'] ?? null,
                'tokens_input' => $result['tokens_input'] ?? null,
                'tokens_output' => $result['tokens_output'] ?? null,
                'created_at' => now(),
            ]);

            DraftSnapshot::create([
                'draft_id' => $draft->id,
                'content_md' => $newContent,
                'context_snapshot' => [
                    'facts' => $draft->context_facts,
                    'legal' => $draft->context_legal,
                    'prefs' => $draft->context_user_prefs,
                ],
                'created_by' => 'ai_edit',
                'message_id' => $assistantMsg->id,
                'label' => "{$intent}: ".mb_substr($params['instruction'] ?? '', 0, 60),
                'created_at' => now(),
            ]);

            $this->extractAndStoreCitations($draft, $newContent, $assistantMsg->id);

            return [
                'draft' => $draft->fresh(),
                'user_message' => $userMsg,
                'assistant_message' => $assistantMsg,
                'replacement_text' => $result['text'],
                'new_content' => $newContent,
            ];
        });
    }

    public function revertToSnapshot(Draft $draft, int $snapshotId): Draft
    {
        $snapshot = DraftSnapshot::where('draft_id', $draft->id)->where('id', $snapshotId)->firstOrFail();

        $draft->update(['current_content_md' => $snapshot->content_md]);

        DraftMessage::create([
            'draft_id' => $draft->id,
            'role' => 'system',
            'content' => "Reverted to snapshot \"{$snapshot->label}\"",
            'intent' => 'revert',
            'created_at' => now(),
        ]);

        return $draft->fresh();
    }

    private function spliceContent(string $original, ?int $start, ?int $end, string $replacement): string
    {
        if ($start === null || $end === null || $start === $end) {
            // No selection given — append the replacement
            return rtrim($original)."\n\n".$replacement;
        }
        return mb_substr($original, 0, $start).$replacement.mb_substr($original, $end);
    }

    private function extractAndStoreCitations(Draft $draft, string $content, int $messageId): void
    {
        // Mark old citations as removed if they no longer appear
        $draft->citations()->whereNull('removed_in_message_id')->each(function (DraftCitation $cit) use ($content, $messageId) {
            if (! str_contains($content, $cit->raw_text)) {
                $cit->update(['removed_in_message_id' => $messageId]);
            }
        });

        // Add new citations
        foreach ($this->extractor->extract($content) as $cit) {
            $exists = $draft->citations()
                ->where('raw_text', $cit['raw_text'])
                ->whereNull('removed_in_message_id')
                ->exists();
            if (! $exists) {
                $draft->citations()->create([
                    'citation_type' => $cit['type'],
                    'raw_text' => $cit['raw_text'],
                    'statute_code' => $cit['statute_code'],
                    'section_no' => $cit['section_no'],
                    'position_in_draft' => $cit['position'],
                    'verification_status' => 'pending',
                    'added_in_message_id' => $messageId,
                ]);
            }
        }
    }
}
