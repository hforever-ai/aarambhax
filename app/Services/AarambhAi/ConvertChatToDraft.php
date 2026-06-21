<?php

namespace App\Services\AarambhAi;

use App\Models\CaseAnalysis;
use App\Models\CaseRecord;
use App\Models\Conversation;
use App\Models\Draft;
use App\Models\DraftCatalogueEntry;
use App\Models\DraftMessage;
use App\Models\DraftSnapshot;
use App\Models\User;
use App\Services\Routing\DraftRouter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The bridge: take a CaseAnalysis OR a Conversation and turn it into a real
 * Draft using the existing /v1/draft pipeline.
 *
 * What it does:
 *   1. Pick a catalogue template — either user-specified, or via DraftRouter
 *      using the Case's detected document types.
 *   2. Compose a Drafter system prompt (PromptComposer with persona=drafter).
 *   3. Build the case_profile from Case.state_json + analysis/conversation
 *      summary so the Drafter has full provenance context.
 *   4. Call /v1/draft via AarambhAiClient (existing).
 *   5. Persist Draft row with source_analysis_id or source_conversation_id set.
 */
class ConvertChatToDraft
{
    public function __construct(
        private readonly AarambhAiClient $ai,
        private readonly PromptComposer $prompts,
        private readonly DraftRouter $router,
    ) {
    }

    public static function make(): self
    {
        return new self(AarambhAiClient::make(), new PromptComposer(), DraftRouter::make());
    }

    /**
     * Convert a CaseAnalysis into a Draft. Returns the new Draft.
     */
    public function fromAnalysis(
        CaseAnalysis $analysis,
        User $user,
        ?string $forumOverride = null,
        ?string $draftTypeOverride = null,
        ?string $languageOverride = null,
    ): Draft {
        $case = $analysis->case;
        $detectedDocs = $this->detectedDocTypes($case);

        $catalogueEntry = $this->pickCatalogueEntry(
            detectedDocs: $detectedDocs,
            forumHint: $forumOverride ?: $case->forum,
            languageHint: $languageOverride ?: $analysis->language,
            draftTypeOverride: $draftTypeOverride,
        );

        $forum = $forumOverride ?: ($catalogueEntry?->forum ?: $case->forum ?: 'cg_hc');
        $draftType = $draftTypeOverride ?: ($catalogueEntry?->draft_type ?: 'Application');
        $language = $languageOverride ?: ($catalogueEntry?->language ?: $analysis->language ?: 'en');

        $caseProfile = $this->caseProfileFromAnalysis($analysis);

        $sourceAnalysisContext = "═══ STRATEGIC ANALYSIS (advocate has reviewed) ═══\n\n"
            .$analysis->current_content_md;

        return $this->callDrafterAndPersist(
            case: $case,
            user: $user,
            forum: $forum,
            draftType: $draftType,
            language: $language,
            caseProfile: $caseProfile,
            catalogueEntry: $catalogueEntry,
            sourceContextLabel: 'analysis',
            sourceContext: $sourceAnalysisContext,
            sourceAnalysisId: $analysis->id,
            sourceConversationId: null,
        );
    }

    /**
     * Convert a Conversation into a Draft. Marks the conversation as
     * status=converted_to_draft and links converted_draft_id.
     */
    public function fromConversation(
        Conversation $conversation,
        User $user,
        ?string $forumOverride = null,
        ?string $draftTypeOverride = null,
        ?string $languageOverride = null,
    ): Draft {
        $case = $conversation->case;
        $detectedDocs = $this->detectedDocTypes($case);

        $catalogueEntry = $this->pickCatalogueEntry(
            detectedDocs: $detectedDocs,
            forumHint: $forumOverride ?: $case->forum,
            languageHint: $languageOverride ?: $conversation->language,
            draftTypeOverride: $draftTypeOverride,
        );

        $forum = $forumOverride ?: ($catalogueEntry?->forum ?: $case->forum ?: 'cg_hc');
        $draftType = $draftTypeOverride ?: ($catalogueEntry?->draft_type ?: 'Application');
        $language = $languageOverride ?: ($catalogueEntry?->language ?: $conversation->language ?: 'en');

        $caseProfile = $case->state_json['extracted_facts'] ?? [];

        // Build a compact context block from chat summary + recent messages
        $contextParts = [];
        if ($conversation->context_summary_md) {
            $contextParts[] = "EARLIER DISCUSSION (summary):\n\n".$conversation->context_summary_md;
        }
        $recent = $conversation->activeMessages()->orderBy('created_at')->get();
        if ($recent->isNotEmpty()) {
            $contextParts[] = "RECENT DISCUSSION:\n\n".$recent->map(
                fn ($m) => strtoupper($m->role).': '.$m->content
            )->implode("\n\n");
        }
        $sourceContext = "═══ ADVOCATE-AI DISCUSSION (advocate has agreed strategy) ═══\n\n"
            .implode("\n\n", $contextParts);

        $draft = $this->callDrafterAndPersist(
            case: $case,
            user: $user,
            forum: $forum,
            draftType: $draftType,
            language: $language,
            caseProfile: $caseProfile,
            catalogueEntry: $catalogueEntry,
            sourceContextLabel: 'conversation',
            sourceContext: $sourceContext,
            sourceAnalysisId: $conversation->source_analysis_id,
            sourceConversationId: $conversation->id,
        );

        $conversation->update([
            'status' => 'converted_to_draft',
            'converted_draft_id' => $draft->id,
        ]);

        return $draft;
    }

    // ─── Internals ─────────────────────────────────────────────────────────

    private function detectedDocTypes(CaseRecord $case): array
    {
        return $case->documents()
            ->whereNotNull('detected_doc_type')
            ->pluck('detected_doc_type')
            ->unique()
            ->values()
            ->all();
    }

    private function pickCatalogueEntry(
        array $detectedDocs,
        ?string $forumHint = null,
        ?string $languageHint = null,
        ?string $draftTypeOverride = null,
    ): ?DraftCatalogueEntry {
        // If user explicitly picked a draft_type, respect it
        if ($draftTypeOverride) {
            $hit = DraftCatalogueEntry::where('draft_type', $draftTypeOverride)
                ->when($languageHint, fn ($q) => $q->where('language', $languageHint))
                ->first();
            if ($hit) {
                return $hit;
            }
        }

        if (empty($detectedDocs)) {
            return null;
        }

        $filters = array_filter([
            'forum' => $forumHint,
            'language' => $languageHint,
        ]);

        $result = $this->router->recommend($detectedDocs, $filters);
        if (($result['mode'] ?? null) === 'auto' && ! empty($result['recommendation']['id'])) {
            return DraftCatalogueEntry::find($result['recommendation']['id']);
        }
        if (($result['mode'] ?? null) === 'disambiguate' && ! empty($result['candidates'])) {
            $idx = $result['llm_advice']['primary_recommendation_index'] ?? 0;
            $pick = $result['candidates'][$idx] ?? $result['candidates'][0];
            return DraftCatalogueEntry::find($pick['id']);
        }
        return null;
    }

    private function caseProfileFromAnalysis(CaseAnalysis $analysis): array
    {
        return array_merge(
            $analysis->case->state_json['extracted_facts'] ?? [],
            $analysis->context_facts ?? [],
        );
    }

    private function callDrafterAndPersist(
        CaseRecord $case,
        User $user,
        string $forum,
        string $draftType,
        string $language,
        array $caseProfile,
        ?DraftCatalogueEntry $catalogueEntry,
        string $sourceContextLabel,
        string $sourceContext,
        ?int $sourceAnalysisId,
        ?int $sourceConversationId,
    ): Draft {
        $sysPrompt = $this->prompts->build(
            persona: 'drafter',
            forum: $forum,
            language: $language,
            draftTypeSlug: $catalogueEntry?->draft_type ? $this->slugify($catalogueEntry->draft_type) : null,
            caseStateJson: $case->state_json ?? [],
        );

        // Append the source-context (analysis/conversation) so the Drafter sees
        // why this draft exists.
        $sysPrompt .= "\n\n---\n\n".$sourceContext;

        $result = $this->ai->draft(
            forum: $forum,
            draftType: $draftType,
            language: $language,
            caseProfile: $caseProfile,
            fewShotTemplate: $catalogueEntry?->cleaned_text,
        );

        return DB::transaction(function () use (
            $case, $user, $forum, $draftType, $language, $caseProfile, $result,
            $sourceAnalysisId, $sourceConversationId, $sourceContextLabel, $catalogueEntry
        ) {
            $draft = Draft::create([
                'user_id' => $user->id,
                'case_id' => $case->id,
                'source_analysis_id' => $sourceAnalysisId,
                'source_conversation_id' => $sourceConversationId,
                'title' => $draftType.' — '.now()->format('d M Y H:i'),
                'forum' => $forum,
                'category' => 'auto',
                'draft_type' => $draftType,
                'language' => $language,
                'context_facts' => $caseProfile,
                'context_legal' => ['cited_sections' => [], 'cited_judgments' => []],
                'context_user_prefs' => [
                    'source' => $sourceContextLabel,
                    'catalogue_entry_used' => $catalogueEntry ? [
                        'id' => $catalogueEntry->id,
                        'draft_type' => $catalogueEntry->draft_type,
                        'source_filename' => $catalogueEntry->source_filename,
                    ] : null,
                    'tokens_in' => $result['tokens_in'] ?? 0,
                    'tokens_out' => $result['tokens_out'] ?? 0,
                    'flagged_blanks' => $result['flagged_blanks'] ?? [],
                ],
                'current_content_md' => $result['draft_markdown'] ?? '',
                'status' => 'editing',
            ]);

            DraftSnapshot::create([
                'draft_id' => $draft->id,
                'content_md' => $draft->current_content_md,
                'context_snapshot' => [
                    'state_json' => $case->state_json ?? [],
                    'source' => $sourceContextLabel,
                    'source_id' => $sourceAnalysisId ?: $sourceConversationId,
                ],
                'created_by' => 'ai_edit',
                'message_id' => null,
                'label' => "Initial draft (from {$sourceContextLabel})",
                'created_at' => now(),
            ]);

            DraftMessage::create([
                'draft_id' => $draft->id,
                'role' => 'system',
                'content' => "Draft generated from {$sourceContextLabel}",
                'intent' => 'initial_draft',
                'model_used' => $result['model_used'] ?? null,
                'tokens_input' => $result['tokens_in'] ?? null,
                'tokens_output' => $result['tokens_out'] ?? null,
                'created_at' => now(),
            ]);

            return $draft->fresh();
        });
    }

    private function slugify(string $s): string
    {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-');
    }
}
