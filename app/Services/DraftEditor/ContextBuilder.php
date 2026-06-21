<?php

namespace App\Services\DraftEditor;

use App\Models\Draft;

/**
 * Assembles the full conversational context for an edit operation.
 *
 * This is what makes Aarambhax different from Lawttorney — every edit gets
 * the FULL history so the AI never forgets facts, parties, or previous edits.
 */
class ContextBuilder
{
    public function build(Draft $draft, ?string $selectionText = null): array
    {
        $messages = $draft->messages()
            ->latest('id')
            ->limit(20)        // last 20 turns is plenty
            ->get()
            ->reverse()        // chronological for the model
            ->values();

        return [
            'forum'        => $draft->forum,
            'language'     => $draft->language,
            'category'     => $draft->category,
            'draft_type'   => $draft->draft_type,
            'context_facts'  => $draft->context_facts ?? [],
            'context_legal'  => $draft->context_legal ?? [],
            'context_user_prefs' => $draft->context_user_prefs ?? [],
            'full_draft'   => $draft->current_content_md,
            'selection_text' => $selectionText,
            'prior_messages_summary' => $this->summariseMessages($messages),
        ];
    }

    private function summariseMessages($messages): string
    {
        if ($messages->isEmpty()) {
            return '(none — this is the first edit)';
        }

        return $messages->map(function ($msg) {
            $intent = $msg->intent ? "[{$msg->intent}] " : '';
            $body = mb_substr(strip_tags((string) $msg->content), 0, 200);
            return "- {$msg->role}: {$intent}{$body}";
        })->implode("\n");
    }
}
