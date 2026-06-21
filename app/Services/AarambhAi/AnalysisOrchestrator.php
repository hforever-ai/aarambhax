<?php

namespace App\Services\AarambhAi;

use App\Models\AnalysisMessage;
use App\Models\AnalysisSnapshot;
use App\Models\CaseAnalysis;
use App\Models\CaseRecord;
use App\Models\Document;
use App\Models\User;
use App\Services\Gemini\CostCalculator;
use App\Services\Gemini\GeminiKeyRouter;
use App\Services\Gemini\PiiRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Generates a strategic CaseAnalysis from a Case's documents + state, and
 * applies refinements via the chat sidebar (same UX pattern as DraftEditor).
 *
 * Flow:
 *   1. generateInitial(): combine all Case documents → /v1/architect (refresh state)
 *      → /v1/analyse (Pro 2.5) → save CaseAnalysis row + initial AnalysisSnapshot
 *   2. applyEdit(): same as EditOrchestrator pattern but on AnalysisMessage /
 *      AnalysisSnapshot tables; uses persona=chat-assistant + analyst overlay.
 */
class AnalysisOrchestrator
{
    public const SUPPORTED_INTENTS = ['rewrite_section', 'tighten', 'add_risk', 'suggest_precedent', 'free_form'];

    public function __construct(
        private readonly AarambhAiClient $ai,
        private readonly PromptComposer $prompts,
        private readonly PocsoRedactor $redactor,
        private readonly GeminiKeyRouter $router,
        private readonly PiiRedactor $piiRedactor,
        private readonly CostCalculator $costCalc,
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
            new CostCalculator(),
        );
    }

    /**
     * Build a tier-routed payload (free-tier with PII redaction by default,
     * upgraded to free Flash if doc count is heavy) and return router-picked
     * key + model. Caller passes these to the AI client for per-call override.
     *
     * @param array $docPack
     * @param string $flow  'analyse' | 'chat' (passed through to GeminiKeyRouter::decideTier)
     * @return array{key:string, model:string, tier:string, redacted_doc_pack:array, pii_redactions:int}
     */
    private function routeForFreeTier(array $docPack, string $flow): array
    {
        $decision = GeminiKeyRouter::decideTier($flow, count($docPack));
        $picked = $this->router->pick($decision['tier'], $decision['model_hint']);

        $redactedPack = $docPack;
        $redactionCount = 0;
        if ($decision['redact_pii']) {
            $r = $this->piiRedactor->redactDocPack($docPack);
            $redactedPack = $r['doc_pack'];
            $redactionCount = $r['total_redactions'];
        }

        return [
            'key' => $picked['key'],
            'model' => $picked['model'],
            'tier' => $decision['tier'],
            'redacted_doc_pack' => $redactedPack,
            'pii_redactions' => $redactionCount,
        ];
    }

    /**
     * Run /v1/analyse on an existing CaseAnalysis row (created earlier in queued
     * state). Writes results in-place into the row. Used by AnalysisPipelineJob
     * so the row appears in the UI immediately while AI work proceeds in background.
     */
    public function generateForExisting(CaseAnalysis $analysis, ?string $userSummary = null): CaseAnalysis
    {
        $case = $analysis->case;
        $documents = $case->documents()->orderBy('created_at')->get();
        if ($documents->isEmpty()) {
            throw new RuntimeException('No documents on case to analyse');
        }

        $docPack = $documents->map(fn (Document $d) => [
            'source_filename' => $d->original_filename,
            'detected_doc_type' => $d->detected_doc_type,
            'language' => $d->language,
            'cleaned_markdown' => $d->ocr_text,
            'structured_fields' => $d->structured_fields ?? [],
        ])->toArray();

        $forum = $case->forum ?: 'cg_hc';
        $language = $analysis->language ?: ($case->state_json['language'] ?? 'en');
        $overlays = $this->detectOverlays($case);
        $stateJson = $case->state_json ?? [];

        $sysPrompt = $this->prompts->build(
            persona: 'analyst',
            forum: $forum,
            language: $language,
            overlays: $overlays,
            caseStateJson: $stateJson,
        );

        // Route through tier router → free Flash Lite (or free Flash if >=10 docs)
        $route = $this->routeForFreeTier($docPack, 'analyse');

        $result = $this->ai->analyse(
            systemPrompt: $sysPrompt,
            docPack: $route['redacted_doc_pack'],
            stateJson: $stateJson,
            userSummary: $userSummary,
            caseId: $case->id,
            geminiApiKey: $route['key'],
            modelOverride: $route['model'],
        );

        $analysisMd = $this->redactor->redact($result['analysis_markdown'] ?? '');
        $updatedState = $result['updated_state_json'] ?? [];

        if (! empty($updatedState)) {
            $merged = array_replace_recursive($stateJson, $updatedState);
            $case->update(['state_json' => $merged]);
            $stateJson = $merged;
        }

        return DB::transaction(function () use ($analysis, $analysisMd, $result, $stateJson) {
            $analysis->update([
                'context_facts' => $stateJson['extracted_facts'] ?? [],
                'context_legal' => [
                    'sections_invoked' => $stateJson['sections_invoked'] ?? [],
                    'theories' => $stateJson['legal_theories'] ?? [],
                ],
                'context_user_prefs' => array_merge($analysis->context_user_prefs ?? [], [
                    'tokens_in' => $result['tokens_in'] ?? 0,
                    'tokens_out' => $result['tokens_out'] ?? 0,
                    'elapsed_ms' => $result['elapsed_ms'] ?? 0,
                ]),
                'current_content_md' => $analysisMd,
            ]);

            $msg = AnalysisMessage::create([
                'analysis_id' => $analysis->id,
                'role' => 'assistant',
                'content' => $analysisMd,
                'intent' => 'initial_analysis',
                'model_used' => $result['model_used'] ?? null,
                'tokens_input' => $result['tokens_in'] ?? null,
                'tokens_output' => $result['tokens_out'] ?? null,
                'created_at' => now(),
            ]);

            AnalysisSnapshot::create([
                'analysis_id' => $analysis->id,
                'content_md' => $analysisMd,
                'context_snapshot' => ['state_json' => $stateJson],
                'created_by' => 'ai_edit',
                'message_id' => $msg->id,
                'label' => 'Initial analysis',
                'created_at' => now(),
            ]);

            return $analysis->fresh();
        });
    }

    /**
     * First analysis for a Case. Pulls all Documents on the Case, refreshes
     * state_json via /v1/architect if needed, then runs /v1/analyse.
     */
    public function generateInitial(
        CaseRecord $case,
        User $user,
        string $analysisType = 'strategic',
        ?string $userSummary = null,
    ): CaseAnalysis {
        $documents = $case->documents()->orderBy('created_at')->get();
        if ($documents->isEmpty()) {
            throw new RuntimeException('No documents on this case to analyse. Upload at least one file first.');
        }

        // Build doc_pack from already-ingested Documents.
        $docPack = $documents->map(fn (Document $d) => [
            'source_filename' => $d->original_filename,
            'detected_doc_type' => $d->detected_doc_type,
            'language' => $d->language,
            'cleaned_markdown' => $d->ocr_text,
            'structured_fields' => $d->structured_fields ?? [],
        ])->toArray();

        // Step 1: refresh case state_json via architect (cheap; ensures fresh extraction)
        $stateJson = $case->state_json ?? [];
        try {
            $arch = $this->ai->architect($docPack, $userSummary);
            if (! empty($arch['case_profile'])) {
                $stateJson = array_merge($stateJson, [
                    'extracted_facts' => $arch['case_profile'],
                ]);
                $case->update(['state_json' => $stateJson]);
            }
        } catch (\Throwable $e) {
            Log::warning('AnalysisOrchestrator: architect refresh failed', ['err' => $e->getMessage()]);
            // Continue with whatever state we already have
        }

        // Step 2: compose analyst persona prompt for this Case
        $forum = $case->forum ?: 'cg_hc';
        $language = $case->state_json['language'] ?? 'en';
        $overlays = $this->detectOverlays($case);

        $sysPrompt = $this->prompts->build(
            persona: 'analyst',
            forum: $forum,
            language: $language,
            overlays: $overlays,
            caseStateJson: $stateJson,
        );

        // Step 3: route through tier router → free Flash Lite (PII-redacted)
        $route = $this->routeForFreeTier($docPack, 'analyse');

        $result = $this->ai->analyse(
            systemPrompt: $sysPrompt,
            docPack: $route['redacted_doc_pack'],
            stateJson: $stateJson,
            userSummary: $userSummary,
            caseId: $case->id,
            geminiApiKey: $route['key'],
            modelOverride: $route['model'],
        );

        $analysisMd = $this->redactor->redact($result['analysis_markdown'] ?? '');
        $updatedState = $result['updated_state_json'] ?? [];

        // Merge updated state back into Case
        if (! empty($updatedState)) {
            $merged = array_replace_recursive($stateJson, $updatedState);
            $case->update(['state_json' => $merged]);
            $stateJson = $merged;
        }

        // Step 4: persist CaseAnalysis + initial snapshot + initial message
        return DB::transaction(function () use ($case, $user, $analysisType, $analysisMd, $result, $stateJson) {
            $analysis = CaseAnalysis::create([
                'case_id' => $case->id,
                'user_id' => $user->id,
                'title' => 'Analysis · '.now()->format('d M Y H:i'),
                'language' => $case->state_json['language'] ?? 'en',
                'analysis_type' => $analysisType,
                'context_facts' => $stateJson['extracted_facts'] ?? [],
                'context_legal' => [
                    'sections_invoked' => $stateJson['sections_invoked'] ?? [],
                    'theories' => $stateJson['legal_theories'] ?? [],
                ],
                'context_user_prefs' => [
                    'analysis_type' => $analysisType,
                    'tokens_in' => $result['tokens_in'] ?? 0,
                    'tokens_out' => $result['tokens_out'] ?? 0,
                    'elapsed_ms' => $result['elapsed_ms'] ?? 0,
                ],
                'current_content_md' => $analysisMd,
                'status' => 'editing',
            ]);

            $msg = AnalysisMessage::create([
                'analysis_id' => $analysis->id,
                'role' => 'assistant',
                'content' => $analysisMd,
                'intent' => 'initial_analysis',
                'model_used' => $result['model_used'] ?? null,
                'tokens_input' => $result['tokens_in'] ?? null,
                'tokens_output' => $result['tokens_out'] ?? null,
                'created_at' => now(),
            ]);

            AnalysisSnapshot::create([
                'analysis_id' => $analysis->id,
                'content_md' => $analysisMd,
                'context_snapshot' => [
                    'state_json' => $stateJson,
                    'doc_pack_filenames' => array_column(
                        $analysis->case->documents()->get(['original_filename'])->toArray(),
                        'original_filename'
                    ),
                ],
                'created_by' => 'ai_edit',
                'message_id' => $msg->id,
                'label' => 'Initial analysis',
                'created_at' => now(),
            ]);

            return $analysis->fresh();
        });
    }

    /**
     * Apply a refinement intent to an existing analysis (chat-sidebar action).
     */
    public function applyEdit(CaseAnalysis $analysis, array $params): array
    {
        $intent = $params['intent'] ?? 'free_form';
        if (! in_array($intent, self::SUPPORTED_INTENTS, true)) {
            throw new RuntimeException("Unsupported intent: {$intent}");
        }

        $case = $analysis->case;
        $forum = $case->forum ?: 'cg_hc';
        $language = $analysis->language ?: 'en';
        $overlays = $this->detectOverlays($case);
        $stateJson = $case->state_json ?? [];

        // Use chat-assistant persona for refinements (smaller, conversational)
        $sysPrompt = $this->prompts->build(
            persona: 'chat-assistant',
            forum: $forum,
            language: $language,
            overlays: $overlays,
            caseStateJson: $stateJson,
        );

        // Build a turn that asks for refinement of the current analysis
        $instruction = $params['instruction'] ?? '';
        $selectionText = $params['selection_text'] ?? null;

        $userBlock = "Current analysis:\n\n```\n".($analysis->current_content_md ?: '(empty)')."\n```\n\n";
        if ($selectionText) {
            $userBlock .= "Selected text:\n\n```\n{$selectionText}\n```\n\n";
        }
        $userBlock .= match ($intent) {
            'tighten' => 'Tighten the writing — same content, fewer words. Return ONLY the revised analysis markdown.',
            'rewrite_section' => "Rewrite the selected section. Instruction: {$instruction}. Return ONLY the revised section text (replacing the selection).",
            'add_risk' => 'Add a "Risks" sub-section with 3-5 specific risks for the recommended filing. Append to the existing analysis. Return the FULL updated analysis.',
            'suggest_precedent' => 'Suggest 3-5 likely precedents (HC + SC) supporting the recommended filing. Mark each as `[CITATION NEEDED — verify]` since unverified. Append as a new section. Return the FULL updated analysis.',
            'free_form' => "Apply this instruction to the analysis: {$instruction}. Return the FULL updated analysis.",
            default => 'No-op.',
        };

        // Recent messages — last 10 for context (chat-sidebar history)
        $recent = $analysis->messages()->latest('created_at')->limit(10)->get()->reverse()->map(fn (AnalysisMessage $m) => [
            'role' => $m->role === 'user' ? 'user' : 'assistant',
            'content' => $m->content,
        ])->values()->toArray();

        // Log the user instruction
        $userMsg = AnalysisMessage::create([
            'analysis_id' => $analysis->id,
            'role' => 'user',
            'content' => $instruction ?: "(intent: {$intent})",
            'intent' => $intent,
            'target_section_id' => $params['target_section_id'] ?? null,
            'selection_start' => $params['selection_start'] ?? null,
            'selection_end' => $params['selection_end'] ?? null,
            'created_at' => now(),
        ]);

        // Route refinement chats through free tier (PII-redacted)
        $route = $this->routeForFreeTier([], 'chat');

        $result = $this->ai->chat(
            systemPrompt: $sysPrompt,
            newUserMessage: $userBlock,
            stateJson: $stateJson,
            recentMessages: $recent,
            conversationId: null,
            geminiApiKey: $route['key'],
            modelOverride: $route['model'],
        );

        $newText = $this->redactor->redact($result['assistant_message'] ?? '');

        return DB::transaction(function () use ($analysis, $params, $newText, $result, $intent, $userMsg) {
            // For rewrite_section, splice; otherwise replace whole body
            if ($intent === 'rewrite_section'
                && isset($params['selection_start'], $params['selection_end'])
                && $params['selection_start'] !== $params['selection_end']) {
                $newContent = mb_substr($analysis->current_content_md, 0, (int) $params['selection_start'])
                    .$newText
                    .mb_substr($analysis->current_content_md, (int) $params['selection_end']);
            } else {
                $newContent = $newText;
            }

            $analysis->update(['current_content_md' => $newContent]);

            $assistantMsg = AnalysisMessage::create([
                'analysis_id' => $analysis->id,
                'role' => 'assistant',
                'content' => $newText,
                'intent' => $intent,
                'model_used' => $result['model_used'] ?? null,
                'tokens_input' => $result['tokens_in'] ?? null,
                'tokens_output' => $result['tokens_out'] ?? null,
                'created_at' => now(),
            ]);

            AnalysisSnapshot::create([
                'analysis_id' => $analysis->id,
                'content_md' => $newContent,
                'context_snapshot' => ['state_json' => $analysis->case->state_json ?? []],
                'created_by' => 'ai_edit',
                'message_id' => $assistantMsg->id,
                'label' => "{$intent}: ".mb_substr((string) ($params['instruction'] ?? ''), 0, 60),
                'created_at' => now(),
            ]);

            return [
                'analysis' => $analysis->fresh(),
                'user_message' => $userMsg,
                'assistant_message' => $assistantMsg,
                'new_content' => $newContent,
            ];
        });
    }

    /**
     * Look at Case fields and state_json to decide which overlays to apply.
     */
    private function detectOverlays(CaseRecord $case): array
    {
        $overlays = [];
        $state = $case->state_json ?? [];
        $forum = $case->forum ?: '';

        // Bilaspur HC Rules 2007 — applies to all CG HC drafts/analyses
        if ($forum === 'cg_hc') {
            $overlays[] = 'bilaspur_hc_rules_2007';
        }

        // §170-B CGLRC — tribal land restoration. Apply if revenue + state hints tribal.
        if ($forum === 'cg_revenue') {
            $tags = $state['tags'] ?? [];
            if (in_array('tribal_land', $tags, true) || ! empty($state['extracted_facts']['tribal_seller'])) {
                $overlays[] = 'lrc_170b';
            }
        }

        // Schedule V — apply if village in known Schedule V district list
        $scheduleVDistricts = ['Bastar', 'Dantewada', 'Sukma', 'Bijapur', 'Narayanpur', 'Kondagaon', 'Kanker',
                               'Surguja', 'Surajpur', 'Balrampur', 'Korea', 'Jashpur', 'Mungeli'];
        if (in_array($case->jila, $scheduleVDistricts, true)) {
            $overlays[] = 'schedule_v_tribal';
        }

        return array_unique($overlays);
    }
}
