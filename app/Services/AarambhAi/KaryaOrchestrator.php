<?php

namespace App\Services\AarambhAi;

use App\Models\Document;
use App\Models\Karya;
use App\Services\Gemini\CostCalculator;
use App\Services\Gemini\GeminiKeyRouter;
use App\Services\Gemini\PiiRedactor;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Drives a single Karya run end-to-end:
 *   1. Resolve docs → compose prompt
 *   2. Decide tier (free vs paid) + redact PII if free
 *   3. Pick API key + model via GeminiKeyRouter
 *   4. Call /v1/karya with overrides
 *   5. POCSO-redact output → compute cost → return
 *
 * Tier rules (flow = "karya:{type}"):
 *   reply_to_notice  → paid Flash, no PII redaction (court-filable)
 *   all others       → free Flash Lite (or free Flash if doc count >= 10)
 */
class KaryaOrchestrator
{
    public function __construct(
        private readonly AarambhAiClient $ai,
        private readonly KaryaPromptComposer $composer,
        private readonly PocsoRedactor $redactor,
        private readonly GeminiKeyRouter $router,
        private readonly PiiRedactor $piiRedactor,
        private readonly CostCalculator $costCalc,
    ) {
    }

    public static function make(): self
    {
        return new self(
            ai: AarambhAiClient::make(),
            composer: new KaryaPromptComposer(),
            redactor: new PocsoRedactor(),
            router: new GeminiKeyRouter(),
            piiRedactor: new PiiRedactor(),
            costCalc: new CostCalculator(),
        );
    }

    public function generate(Karya $karya, ?string $userInstruction = null): array
    {
        $karya->loadMissing('case');
        $case = $karya->case;
        if (! $case) {
            throw new RuntimeException("Karya {$karya->id}: case not found");
        }

        $entry = KaryaCatalogue::get($karya->type);
        if (! $entry) {
            throw new RuntimeException("Karya {$karya->id}: unknown type '{$karya->type}'");
        }

        $forum = $case->state_json['forum'] ?? 'cg_district';

        // Build doc_pack from selected Documents (only those with OCR text)
        $documents = $karya->inputDocuments();
        $docPack = $documents
            ->filter(fn (Document $d) => ! empty($d->ocr_text))
            ->map(fn (Document $d) => [
                'document_id' => $d->id,
                'source_filename' => $d->original_filename,
                'detected_doc_type' => $d->detected_doc_type,
                'language' => $d->language,
                'cleaned_markdown' => $d->ocr_text,
                'structured_fields' => $d->structured_fields ?? [],
            ])
            ->values()
            ->toArray();
        $docCount = count($docPack);

        $stateJson = $case->state_json ?? [];

        // Decide tier + key + model from flow + doc count
        $flow = "karya:{$karya->type}";
        $decision = GeminiKeyRouter::decideTier($flow, $docCount);
        $picked = $this->router->pick($decision['tier'], $decision['model_hint']);

        // PII-redact doc_pack + state if free tier
        $redactionCount = 0;
        if ($decision['redact_pii']) {
            $r = $this->piiRedactor->redactDocPack($docPack);
            $docPack = $r['doc_pack'];
            $redactionCount = $r['total_redactions'];
        }

        Log::info('KaryaOrchestrator: dispatching', [
            'karya' => $karya->id,
            'type' => $karya->type,
            'flow' => $flow,
            'tier' => $decision['tier'],
            'model' => $picked['model'],
            'project_index' => $picked['project_index'],
            'forum' => $forum,
            'lang' => $karya->language,
            'docs' => $docCount,
            'pii_redacted' => $redactionCount,
        ]);

        $systemPrompt = $this->composer->build(
            karyaType: $karya->type,
            forum: $forum,
            language: $karya->language ?: $entry['default_lang'],
            caseStateJson: $stateJson,
            userInstruction: $userInstruction,
        );

        $resp = $this->ai->karya(
            karyaType: $karya->type,
            modelTier: $entry['model'],
            systemPrompt: $systemPrompt,
            docPack: $docPack,
            stateJson: $stateJson,
            userInstruction: $userInstruction,
            karyaId: $karya->id,
            geminiApiKey: $picked['key'],
            modelOverride: $picked['model'],
        );

        $output = (string) ($resp['output_markdown'] ?? '');
        $output = $this->redactor->redact($output);

        $tokensIn = (int) ($resp['tokens_in'] ?? 0);
        $tokensOut = (int) ($resp['tokens_out'] ?? 0);
        $modelUsed = (string) ($resp['model_used'] ?? $picked['model']);
        $cost = $this->costCalc->compute($modelUsed, $tokensIn, $tokensOut, $decision['tier']);

        return [
            'output_markdown' => $output,
            'output_json' => $resp['output_json'] ?? [],
            'model_used' => $modelUsed,
            'tokens_in' => $tokensIn,
            'tokens_out' => $tokensOut,
            'elapsed_ms' => (int) ($resp['elapsed_ms'] ?? 0),
            'tier' => $decision['tier'],
            'cost_inr_paise' => (int) $cost['cost_inr_paise'],
            'paid_equivalent_paise' => (int) $cost['paid_equivalent_paise'],
            'pii_redactions' => $redactionCount,
        ];
    }
}
