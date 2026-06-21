<?php

namespace App\Pipelines\Prompts;

/**
 * Builds prompts for the 4 supported edit intents:
 *   - rewrite_section : replace a selected portion preserving context
 *   - tighten         : shorten without losing legal substance
 *   - add_citation    : insert authority for a claim
 *   - free_form       : free-form natural-language instruction
 *
 * Every prompt receives the FULL conversation memory so the AI never forgets
 * the original facts, parties, forum, language, or previous edits.
 */
class EditIntentPrompt
{
    public const VERSION = 'edit_intent_v1';

    public static function system(): string
    {
        return <<<'TXT'
You are an Indian advocate's drafting assistant. You edit existing legal drafts based on advocate instructions, while preserving the full case context.

PRESERVATION RULES (CRITICAL):
1. Every fact in the original draft (parties, dates, case numbers, khasra/khata, sections cited) must remain unchanged unless the advocate explicitly asks to change it.
2. Every previous citation must remain unless the advocate asks to remove or replace it.
3. The forum-specific format (cause-title style, numbering, language) must remain consistent.
4. The language (English / Hindi) of the draft must remain unchanged unless the advocate explicitly asks to translate.
5. Tone and register must match the rest of the draft — do not slip from formal legal Hindi into Hinglish, or from precise English into casual prose.

LEGAL ACCURACY:
- Never invent new section numbers, judgments, or case citations
- If a citation is needed and not in the existing context, use "[VERIFY: brief description]"
- Use post-July-2024 BNS/BNSS/BSA citations unless the original draft uses IPC/CrPC

OUTPUT:
- Return ONLY the new text that replaces the selection (or the new section if adding)
- No preamble, no explanation, no markdown code-fences
- Match indentation and markdown style of the surrounding draft
TXT;
    }

    public static function user(string $intent, array $params): string
    {
        $facts        = json_encode($params['context_facts'] ?? [], JSON_UNESCAPED_UNICODE);
        $legal        = json_encode($params['context_legal'] ?? [], JSON_UNESCAPED_UNICODE);
        $forum        = $params['forum'] ?? 'unknown';
        $language     = $params['language'] ?? 'en';
        $fullDraft    = $params['full_draft'] ?? '';
        $selection    = $params['selection_text'] ?? '';
        $instruction  = $params['instruction'] ?? '';
        $prior        = $params['prior_messages_summary'] ?? '';

        $intentInstruction = match ($intent) {
            'rewrite_section' => "Rewrite the selected section. Apply this guidance: {$instruction}",
            'tighten'         => "Tighten the selected section. Reduce length by ~30-40% without losing legal substance. Keep all facts and citations.",
            'add_citation'    => "Add a citation supporting the selected text. Use ONLY citations from the legal context provided. Do not invent. Frame: \"{$instruction}\"",
            'free_form'       => "Apply this instruction: {$instruction}",
            default           => "Apply this instruction: {$instruction}",
        };

        return <<<TXT
CASE CONTEXT (preserve all of this):
Forum: {$forum}
Language: {$language}

FACTS (do not invent beyond these):
{$facts}

LEGAL CONTEXT (sections / judgments already in play):
{$legal}

PRIOR EDITS THIS SESSION (so you remember what the advocate has been doing):
{$prior}

FULL CURRENT DRAFT (for reference — do not rewrite):
---BEGIN DRAFT---
{$fullDraft}
---END DRAFT---

SELECTED SECTION (this is what you replace):
---BEGIN SELECTION---
{$selection}
---END SELECTION---

YOUR TASK:
{$intentInstruction}

Return only the new text that replaces the selection.
TXT;
    }
}
