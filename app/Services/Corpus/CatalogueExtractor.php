<?php

namespace App\Services\Corpus;

use App\Models\DraftCatalogueEntry;
use App\Services\Gemini\GeminiClient;
use RuntimeException;

/**
 * Reads a single .docx legal draft and produces a structured fingerprint
 * (forum, draft_type, language, required/optional/forbidden inputs,
 * statutes, template outline). Stored in `draft_catalogue` table.
 *
 * One LLM call per draft, ~₹1.50 each on Gemini 2.5 Flash.
 */
class CatalogueExtractor
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly DocxTextExtractor $docx,
    ) {
    }

    public static function make(): self
    {
        return new self(GeminiClient::make(), new DocxTextExtractor());
    }

    /**
     * Extract + persist. Returns the saved DraftCatalogueEntry.
     * If $force=false and the path is already catalogued, returns existing.
     */
    public function extractFile(string $absolutePath, bool $force = false): DraftCatalogueEntry
    {
        $existing = DraftCatalogueEntry::where('source_path', $absolutePath)->first();
        if ($existing && ! $force) {
            return $existing;
        }

        $cleanedText = $this->docx->extract($absolutePath);
        if (mb_strlen($cleanedText) < 100) {
            throw new RuntimeException("Extracted text too short ({$absolutePath}) — only ".mb_strlen($cleanedText).' chars');
        }

        // Truncate to ~25K chars (~6K tokens) — system prompt + truncated body
        // fits comfortably in Gemini 2.5 Flash context. The opening 25K of any
        // pleading contains all the routing-relevant signal.
        $forLLM = mb_substr($cleanedText, 0, 25000);

        $system = $this->systemPrompt();
        $user = "DRAFT TEXT:\n\n{$forLLM}\n\n---\nReturn the JSON fingerprint as specified.";

        $result = $this->gemini->generate($system, $user, usePro: false, temperature: 0.1);

        $fingerprint = $this->parseFingerprint($result['text']);

        $payload = array_merge([
            'source_path'         => $absolutePath,
            'source_filename'     => basename($absolutePath),
            'source_bytes'        => is_file($absolutePath) ? filesize($absolutePath) : null,
            'cleaned_text'        => $cleanedText,
            'raw_ai_response'     => ['text' => $result['text']],
            'ai_model_used'       => $result['model'] ?? null,
            'tokens_input'        => $result['tokens_input'] ?? null,
            'tokens_output'       => $result['tokens_output'] ?? null,
        ], $this->normaliseFingerprint($fingerprint));

        if ($existing) {
            $existing->update($payload);
            return $existing->fresh();
        }

        return DraftCatalogueEntry::create($payload);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior Indian advocate cataloguing legal pleadings for a draft-routing system.

You will be shown the text of ONE existing legal pleading. Output ONLY a single JSON
object with the fingerprint. No preamble, no markdown fences, no commentary.

Schema (every field required; use null when truly unknown — do NOT guess):

{
  "forum": "cg_hc | cg_district | cg_revenue | cg_family | cg_sessions | sc | tribunal | other",
  "court": "exact court name from header (e.g., 'High Court of Chhattisgarh at Bilaspur', 'JMFC Pathalgaon', 'SDM Pathalgaon')",
  "draft_type": "specific type — be precise. Good: 'Anticipatory Bail Application', 'DV Act §12 Application', 'Application u/s 115 CGLRC', 'SLP (Civil)'. Bad: 'Application', 'Petition'",
  "language": "en | hi | bilingual",
  "case_category": "criminal | civil | family | revenue | service | constitutional | consumer | rera | tribunal",
  "state": "Chhattisgarh | Uttar Pradesh | Maharashtra | India | other",
  "district": "if present in cause title (e.g., 'Jashpur', 'Raigarh'), else null",

  "required_inputs": [
    "list of CLIENT-PROVIDED documents this draft must have referenced to be drafted",
    "use canonical names: FIR, charge_sheet, lower_court_order, lower_court_judgment, legal_notice, sale_deed, khasra, khatauni, mutation_entry, aadhaar, pan_card, bank_statement, cheque, medical_report, fsl_report, school_record, marriage_certificate, death_certificate, partition_deed, lease_deed, rent_agreement, gift_deed, will, power_of_attorney, vakalatnama, photographs, witness_statement, section_164_statement, charge_memo, summons, warrant, builder_agreement, rera_registration, employment_letter, salary_slip, prior_complaint"
  ],
  "optional_inputs": ["docs that strengthen this draft choice but are not strictly required"],
  "forbidden_inputs": ["if present, suggest a DIFFERENT draft instead. Use same canonical names."],

  "statutes": [
    {"section_no": "exact section as cited in draft (e.g., '12', '438', '115')",
     "statute": "BNS | BNSS | BSA | CrPC | IPC | CPC | CGLRC | NI Act | DV Act | HMA | Constitution | POCSO | NDPS | Arbitration Act | Consumer Protection Act | RERA | other"}
  ],

  "template_outline": [
    "ordered list of section headings as they appear in the draft body",
    "examples: 'Cause Title', 'Synopsis', 'List of Dates', 'Particulars of Petitioner', 'Particulars of Respondent', 'Subject Matter', 'Facts', 'Grounds', 'Relief Sought', 'Caveat Clause', 'Affidavit', 'Verification'"
  ],

  "ai_confidence": "0.0 to 1.0 — how confident you are in this fingerprint"
}

RULES:
- A field unknown from the text MUST be null. Never invent.
- For required_inputs / forbidden_inputs, think: "what client-provided document is this draft built ON or AGAINST?" Not just what's mentioned in passing.
- draft_type must be specific enough to route on. "Civil Application" is too vague.
- Do not include the canonical_sample text in your output — only the fingerprint JSON.
PROMPT;
    }

    /**
     * Tolerant JSON parser — strips markdown fences, handles minor LLM messiness.
     */
    private function parseFingerprint(string $raw): array
    {
        $text = trim($raw);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/', '', $text);
        $text = trim($text);

        // Find first { and last } — defensive against trailing prose
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException("No JSON object found in LLM response: ".substr($raw, 0, 300));
        }
        $jsonStr = substr($text, $start, $end - $start + 1);

        $parsed = json_decode($jsonStr, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('LLM returned malformed JSON: '.json_last_error_msg().' — '.substr($jsonStr, 0, 300));
        }
        return $parsed;
    }

    /**
     * Coerce LLM output into DB column shape.
     */
    private function normaliseFingerprint(array $fp): array
    {
        $confidence = $fp['ai_confidence'] ?? null;
        if (is_string($confidence)) {
            $confidence = (float) $confidence;
        }

        return [
            'forum'             => $fp['forum'] ?? null,
            'court'             => $fp['court'] ?? null,
            'draft_type'        => $fp['draft_type'] ?? null,
            'language'          => $fp['language'] ?? null,
            'case_category'     => $fp['case_category'] ?? null,
            'state'             => $fp['state'] ?? null,
            'district'          => $fp['district'] ?? null,
            'required_inputs'   => $fp['required_inputs'] ?? [],
            'optional_inputs'   => $fp['optional_inputs'] ?? [],
            'forbidden_inputs'  => $fp['forbidden_inputs'] ?? [],
            'statutes'          => $fp['statutes'] ?? [],
            'template_outline'  => $fp['template_outline'] ?? [],
            'ai_confidence'     => is_numeric($confidence) ? (float) $confidence : null,
        ];
    }
}
