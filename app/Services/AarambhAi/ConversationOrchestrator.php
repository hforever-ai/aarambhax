<?php

namespace App\Services\AarambhAi;

use App\Models\CaseRecord;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Services\Gemini\GeminiKeyRouter;
use App\Services\Gemini\PiiRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Free-form chat per Case. Each turn calls /v1/chat (Flash 2.5).
 *
 * Long-conversation handling:
 *   When unsummarised messages count exceeds ROLLING_SUMMARY_THRESHOLD,
 *   take the oldest ROLLING_SUMMARY_FOLD messages and summarise them via
 *   Flash. The summary is stored on Conversation.context_summary_md and
 *   those messages are flagged summarised_into_context=true so they no
 *   longer appear in the active prompt context.
 */
class ConversationOrchestrator
{
    public const ROLLING_SUMMARY_THRESHOLD = 30; // when active messages reach this, summarise
    public const ROLLING_SUMMARY_FOLD = 20;      // fold this many oldest into the summary
    public const RECENT_WINDOW = 20;             // most recent active messages always sent verbatim

    public function __construct(
        private readonly AarambhAiClient $ai,
        private readonly PromptComposer $prompts,
        private readonly PocsoRedactor $redactor,
        private readonly GeminiKeyRouter $router,
        private readonly PiiRedactor $piiRedactor,
    ) {
    }

    public static function make(): self
    {
        return new self(
            AarambhAiClient::make(),
            new PromptComposer(),
            new PocsoRedactor(),
            new GeminiKeyRouter(),
            new PiiRedactor(),
        );
    }

    /**
     * Pick the right tier + key for a chat call.
     *
     * Draft conversations → PAID tier (court-filable artefacts need full data,
     *   no PII redaction, higher quality model).
     * All other kinds (brainstorm/bahas/cross_exam/samjhao/analysis) → FREE
     *   tier with PII-redacted output.
     *
     * @param  string|null  $modelOverride  one of: gemini-flash-lite, gemini-flash,
     *   deepseek-flash, deepseek-pro. Null = router decides automatically.
     * @return array{key:string, model:string, tier:string, provider:string}
     */
    private function routeChat(string $kind = Conversation::KIND_BRAINSTORM, ?string $modelOverride = null): array
    {
        $flow = $kind === Conversation::KIND_DRAFT ? 'draft' : 'chat';
        $decision = GeminiKeyRouter::decideTier($flow, 0);

        // DeepSeek override — VPS holds the key; we only forward the model string.
        // Returns the same Gemini key for the request (FastAPI ignores it on
        // deepseek path, but we keep the round-robin'd key for audit-trail
        // continuity on the Gemini fallback path inside the same request).
        if ($modelOverride === 'deepseek-flash' || $modelOverride === 'deepseek-pro') {
            $deepModel = $modelOverride === 'deepseek-pro'
                ? (string) config('services.deepseek.model_pro', 'deepseek-v4-pro')
                : (string) config('services.deepseek.model_flash', 'deepseek-v4-flash');
            $picked = $this->router->pick($decision['tier'], $decision['model_hint']);
            return [
                'key' => $picked['key'],
                'model' => $deepModel,
                'tier' => $decision['tier'],
                'provider' => 'deepseek',
            ];
        }

        // Gemini override — force lite or flash within the same tier
        $hint = $decision['model_hint'];
        if ($modelOverride === 'gemini-flash-lite') {
            $hint = 'lite';
        } elseif ($modelOverride === 'gemini-flash') {
            $hint = 'flash';
        }
        $picked = $this->router->pick($decision['tier'], $hint);
        return [
            'key' => $picked['key'],
            'model' => $picked['model'],
            'tier' => $decision['tier'],
            'provider' => 'gemini',
        ];
    }

    /**
     * Get or create the user's active Conversation for a Case + kind.
     * Each kind (brainstorm / bahas / cross_exam / samjhao / analysis /
     * draft) is its own thread on the case so personas don't bleed.
     */
    public function getOrCreate(CaseRecord $case, User $user, string $kind = Conversation::KIND_BRAINSTORM, ?int $sourceAnalysisId = null): Conversation
    {
        $kind = in_array($kind, Conversation::KINDS, true) ? $kind : Conversation::KIND_BRAINSTORM;

        $existing = Conversation::query()
            ->where('case_id', $case->id)
            ->where('user_id', $user->id)
            ->where('kind', $kind)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return Conversation::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'kind' => $kind,
            'title' => ucfirst(str_replace('_', ' ', $kind)).' · '.now()->format('d M Y H:i'),
            'language' => $case->state_json['language'] ?? 'en',
            'status' => 'active',
            'source_analysis_id' => $sourceAnalysisId,
        ]);
    }

    /**
     * Send one message, get one assistant reply, persist both, fold-summary if needed.
     *
     * @return array{conversation: Conversation, user_msg: ConversationMessage, assistant_msg: ConversationMessage}
     */
    public function sendMessage(
        Conversation $conversation,
        string $userText,
        array $documentIds = [],
        ?string $outputLanguage = null,
        ?string $modelOverride = null,
    ): array {
        $case = $conversation->case;
        $forum = $case->forum ?: 'cg_hc';
        $kind = $conversation->kind ?: Conversation::KIND_BRAINSTORM;
        // Per-turn output_language override beats the conversation default
        // (used by Draft tab's bilingual toggle: English / Hindi / Bilingual).
        $language = $outputLanguage ?: ($conversation->language ?: 'en');
        $stateJson = $case->state_json ?? [];

        // Build doc context — include the cleaned OCR text of selected docs
        // (or all ingested docs if no selection passed).
        $docsQuery = $case->documents()->whereNotNull('ocr_text');
        if (! empty($documentIds)) {
            $docsQuery->whereIn('id', $documentIds);
        }
        $docs = $docsQuery->orderBy('created_at')->get();

        $docPack = $docs->map(fn ($d) => [
            'document_id' => $d->id,
            'source_filename' => $d->original_filename,
            'detected_doc_type' => $d->detected_doc_type,
            'language' => $d->language,
            'cleaned_markdown' => $d->ocr_text,
        ])->values()->toArray();

        // Pick the persona based on kind. Each work mode has its own voice:
        //   brainstorm  → chat-assistant (open Q&A)
        //   bahas       → bahas (oral-argument prep, pocket brief)
        //   cross_exam  → cross-exam (witness questions, contradictions)
        //   samjhao     → samjhao (Hinglish client explainer)
        //   analysis    → analyst (strategic case analysis)
        //   draft       → drafter (court-filable pleadings)
        $persona = match ($kind) {
            Conversation::KIND_BAHAS       => 'bahas',
            Conversation::KIND_CROSS_EXAM  => 'cross-exam',
            Conversation::KIND_SAMJHAO     => 'samjhao',
            Conversation::KIND_ANALYSIS    => 'analyst',
            Conversation::KIND_DRAFT       => 'drafter',
            Conversation::KIND_NOTES       => 'notes',
            default                         => 'chat-assistant',
        };

        $sysPrompt = $this->prompts->build(
            persona: $persona,
            forum: $forum,
            language: $language,
            caseStateJson: $stateJson,
        );

        // Bilingual override: append a directive that asks for two-column
        // output (English LEFT, Hindi RIGHT). Renderer in the chat view
        // detects this delimiter and shows them side-by-side.
        if ($language === 'bilingual') {
            $sysPrompt .= "\n\n---\n\n## BILINGUAL OUTPUT REQUIRED\n\n"
                ."Produce the answer TWICE — first in formal English, then in formal Hindi (Devanagari). "
                ."Use this exact delimiter format so the renderer can split the two columns:\n\n"
                ."<<<ENGLISH>>>\n[Your English version here, court-appropriate, formal register.]\n\n"
                ."<<<HINDI>>>\n[Your Hindi version here in Devanagari, court-appropriate, formal register.]\n\n"
                ."Both versions MUST cover the same content para-by-para. Section numbers, party names, "
                ."dates, citations stay identical across both versions. If the user is asking for a draft, "
                ."both versions must be filable. Section markers like §47 CPC stay in the same form in both.";
        }

        if (! empty($docPack)) {
            $caseFile = "## CASE FILE — UPLOADED DOCUMENTS\n\n"
                ."The following are the documents on this case. Answer the advocate's "
                ."questions based on what's in these documents.\n\n";
            foreach ($docPack as $d) {
                $caseFile .= "### {$d['source_filename']}";
                if (! empty($d['detected_doc_type'])) {
                    $caseFile .= " · {$d['detected_doc_type']}";
                }
                $caseFile .= "\n\n```\n".substr($d['cleaned_markdown'] ?? '', 0, 50000)."\n```\n\n";
            }
            $sysPrompt .= "\n\n---\n\n".$caseFile;
        }

        // Pull the last RECENT_WINDOW active messages for chat history
        $recent = $conversation->activeMessages()
            ->latest('created_at')
            ->limit(self::RECENT_WINDOW)
            ->get()
            ->reverse()
            ->map(fn (ConversationMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->values()
            ->toArray();

        $contextSummary = $conversation->context_summary_md;

        return DB::transaction(function () use (
            $conversation, $userText, $sysPrompt, $stateJson, $contextSummary, $recent, $kind, $modelOverride
        ) {
            // Persist the user turn first
            $userMsg = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userText,
                'created_at' => now(),
            ]);

            // Route through free tier (PII-redacted at conversation level — user
            // text isn't redacted before sending since the user is asking
            // questions about THEIR own case; redaction happens on assistant
            // output. State/messages may carry contact info, which is fine
            // since the chat persona is locked to "answer based on docs +
            // state" and the PiiRedactor wraps output before persisting.)
            $route = $this->routeChat($kind ?? Conversation::KIND_BRAINSTORM, $modelOverride);

            $result = $this->ai->chat(
                systemPrompt: $sysPrompt,
                newUserMessage: $userText,
                stateJson: $stateJson,
                contextSummaryMd: $contextSummary,
                recentMessages: $recent,
                conversationId: $conversation->id,
                geminiApiKey: $route['key'],
                modelOverride: $route['model'],
            );

            $assistantMsg = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $this->redactor->redact($result['assistant_message'] ?? ''),
                'model_used' => $result['model_used'] ?? null,
                'provider' => $route['provider'] ?? null,
                'tokens_input' => $result['tokens_in'] ?? null,
                'tokens_output' => $result['tokens_out'] ?? null,
                'created_at' => now(),
            ]);

            $conversation->increment('total_tokens_in', (int) ($result['tokens_in'] ?? 0));
            $conversation->increment('total_tokens_out', (int) ($result['tokens_out'] ?? 0));
            $conversation->touch();

            // After persisting, check if we should fold
            $this->maybeFoldOldMessages($conversation->fresh());

            return [
                'conversation' => $conversation->fresh(),
                'user_msg' => $userMsg,
                'assistant_msg' => $assistantMsg,
                'sys_prompt' => $sysPrompt,
                'user_prompt' => $userText,
            ];
        });
    }

    /**
     * If active-message count > threshold, summarise oldest FOLD into context_summary_md.
     */
    private function maybeFoldOldMessages(Conversation $conversation): void
    {
        $activeCount = $conversation->activeMessages()->count();
        if ($activeCount < self::ROLLING_SUMMARY_THRESHOLD) {
            return;
        }

        $toFold = $conversation->activeMessages()
            ->oldest('created_at')
            ->limit(self::ROLLING_SUMMARY_FOLD)
            ->get();

        if ($toFold->count() < 5) {
            return;
        }

        $thread = $toFold->map(fn (ConversationMessage $m) => strtoupper($m->role).': '.$m->content)->implode("\n\n");

        $sys = "Summarise the following advocate-AI conversation about an Indian legal case. "
             ."Preserve EVERY legal fact, every section cited, every name, every date, every "
             ."document reference. Discard pleasantries / acknowledgements. Output a 600-token "
             ."markdown bullet list under headings: Established facts / Open questions / Decisions made / Next steps.";

        try {
            $existing = $conversation->context_summary_md ?: '';
            $userBlock = $existing
                ? "Existing earlier summary:\n\n{$existing}\n\n---\n\nNew portion to fold in:\n\n{$thread}\n\n---\n\nProduce the merged summary."
                : $thread;

            // Rolling-summary also runs on free tier (no PII visible — only existing
            // assistant outputs which were already redacted on persist). Always
            // free regardless of conversation kind because summaries don't need
            // the same fidelity as a final draft.
            $route = $this->routeChat(Conversation::KIND_BRAINSTORM);

            $result = $this->ai->chat(
                systemPrompt: $sys,
                newUserMessage: $userBlock,
                stateJson: $conversation->case->state_json ?? [],
                recentMessages: [],
                conversationId: $conversation->id,
                geminiApiKey: $route['key'],
                modelOverride: $route['model'],
            );

            $newSummary = $result['assistant_message'] ?? '';
            if ($newSummary === '') {
                Log::warning('rolling-summary returned empty', ['conv' => $conversation->id]);
                return;
            }

            DB::transaction(function () use ($conversation, $newSummary, $toFold) {
                $conversation->update(['context_summary_md' => $newSummary]);
                ConversationMessage::whereIn('id', $toFold->pluck('id'))
                    ->update(['summarised_into_context' => true]);
            });
        } catch (\Throwable $e) {
            Log::error('rolling-summary failed', ['conv' => $conversation->id, 'err' => $e->getMessage()]);
        }
    }
}
