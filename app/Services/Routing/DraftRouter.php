<?php

namespace App\Services\Routing;

use App\Models\DraftCatalogueEntry;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Collection;

/**
 * Given a list of canonical document types the client uploaded, pick the
 * candidate draft templates from the catalogue. Two layers:
 *
 *  Layer 1 — pure SQL/PHP scoring:
 *    auto if 1 candidate, gap if 0, else move to Layer 2
 *
 *  Layer 2 — LLM disambiguation:
 *    sends 2-5 candidates + uploaded docs → recommendation + clarifying questions
 */
class DraftRouter
{
    public function __construct(private readonly GeminiClient $gemini)
    {
    }

    public static function make(): self
    {
        return new self(GeminiClient::make());
    }

    /**
     * @param  string[]  $uploadedDocTypes  e.g. ['FIR', 'charge_sheet', 'medical_report']
     * @param  array     $filters           ['forum' => 'cg_hc', 'language' => 'hi', ...]
     * @return array  ['mode' => auto|disambiguate|gap, 'candidates' => [...], 'recommendation' => ?, 'questions' => ?]
     */
    public function recommend(array $uploadedDocTypes, array $filters = []): array
    {
        $uploadedSet = array_map('strtolower', $uploadedDocTypes);

        $query = DraftCatalogueEntry::query();
        foreach (['forum', 'language', 'state', 'district', 'case_category'] as $f) {
            if (! empty($filters[$f])) {
                $query->where($f, $filters[$f]);
            }
        }

        $all = $query->get();
        $scored = $this->scoreCandidates($all, $uploadedSet);

        // Top match must score > 0
        $candidates = $scored->filter(fn ($c) => $c['score'] > 0)->values();

        if ($candidates->isEmpty()) {
            return [
                'mode'       => 'gap',
                'message'    => 'No catalogue entry matches these uploaded documents. New template may be needed.',
                'candidates' => [],
            ];
        }

        $top = $candidates->first();
        $second = $candidates->get(1);

        // Auto-pick when SQL has a clear winner. The LLM is good at drafting language,
        // but the SQL score encodes Vikash bhai's domain knowledge (which inputs map to
        // which draft type). When SQL is confident, do not ask LLM — it second-guesses.
        // Triggers:
        //   (a) only one candidate scoring above threshold, OR
        //   (b) top score ≥ 1.5x the next candidate, OR
        //   (c) full required_inputs match AND no other candidate fully matches required
        $secondScore = $second['score'] ?? 0;
        $sqlConfident = $candidates->count() === 1
            || ($top['score'] >= 1.5 * max($secondScore, 1))
            || ($top['required_match_ratio'] >= 1.0
                && (! $second || $second['required_match_ratio'] < 1.0));

        if ($sqlConfident) {
            return [
                'mode'           => 'auto',
                'recommendation' => $this->candidateView($top),
                'alternatives'   => $candidates->slice(1, 3)->map(fn ($c) => $this->candidateView($c))->values()->all(),
            ];
        }

        // Disambiguation needed — top 5 to LLM
        $shortlist = $candidates->take(5);
        return [
            'mode'         => 'disambiguate',
            'candidates'   => $shortlist->map(fn ($c) => $this->candidateView($c))->values()->all(),
            'llm_advice'   => $this->llmDisambiguate($uploadedDocTypes, $shortlist),
        ];
    }

    /**
     * Score every candidate against the uploaded documents.
     *
     * Score formula (simple but explainable):
     *   +5 for each required_input the user uploaded
     *   +2 for each optional_input the user uploaded
     *   -10 for each forbidden_input the user uploaded
     *   penalty if not all required_inputs are present
     */
    private function scoreCandidates(Collection $entries, array $uploadedSet): Collection
    {
        return $entries->map(function (DraftCatalogueEntry $e) use ($uploadedSet) {
            $required = array_map('strtolower', $e->required_inputs ?? []);
            $optional = array_map('strtolower', $e->optional_inputs ?? []);
            $forbidden = array_map('strtolower', $e->forbidden_inputs ?? []);

            $reqHit = array_intersect($required, $uploadedSet);
            $optHit = array_intersect($optional, $uploadedSet);
            $forbHit = array_intersect($forbidden, $uploadedSet);

            $score = (count($reqHit) * 5)
                + (count($optHit) * 2)
                - (count($forbHit) * 10);

            return [
                'entry'                => $e,
                'score'                => $score,
                'required_hit'         => array_values($reqHit),
                'required_missing'     => array_values(array_diff($required, $uploadedSet)),
                'required_match_ratio' => count($required) > 0 ? count($reqHit) / count($required) : 0,
                'optional_hit'         => array_values($optHit),
                'forbidden_hit'        => array_values($forbHit),
            ];
        })->sortByDesc('score')->values();
    }

    private function candidateView(array $c): array
    {
        /** @var DraftCatalogueEntry $e */
        $e = $c['entry'];
        return [
            'id'                  => $e->id,
            'draft_type'          => $e->draft_type,
            'forum'               => $e->forum,
            'court'               => $e->court,
            'language'            => $e->language,
            'district'            => $e->district,
            'case_category'       => $e->case_category,
            'source_filename'     => $e->source_filename,
            'score'               => $c['score'],
            'required_match_ratio'=> round($c['required_match_ratio'], 2),
            'required_hit'        => $c['required_hit'],
            'required_missing'    => $c['required_missing'],
            'optional_hit'        => $c['optional_hit'],
            'forbidden_hit'       => $c['forbidden_hit'],
            'statutes'            => $e->statutes,
        ];
    }

    /**
     * Ask Gemini to disambiguate when SQL scoring leaves multiple viable candidates.
     */
    private function llmDisambiguate(array $uploadedDocTypes, Collection $candidates): array
    {
        if (! $this->gemini->isConfigured()) {
            return ['advice' => '[stub — set GEMINI_API_KEY]'];
        }

        $candidateLines = $candidates->map(function ($c) {
            $e = $c['entry'];
            return sprintf(
                '%s — %s, %s [%s] (required match %d%% — missing: %s)',
                $e->draft_type,
                $e->forum,
                $e->court,
                $e->language,
                round($c['required_match_ratio'] * 100),
                empty($c['required_missing']) ? 'none' : implode(', ', $c['required_missing'])
            );
        })->implode("\n");

        $system = <<<'PROMPT'
You are a senior Indian advocate at Vikash Agarwal's chambers. A junior has uploaded
some client documents. The catalogue has already been pre-filtered and SCORED by a
deterministic matching engine. The candidates are listed below in SCORE-DESCENDING
order. The candidate at index 0 has the HIGHEST score and is the firm's most likely
answer.

YOUR JOB:
- Default to recommending the candidate at index 0 unless there is a SPECIFIC reason
  to deviate (e.g., a clear forum/language/jurisdiction mismatch, or domain knowledge
  the score did not capture).
- Do NOT pick a lower-scored candidate just because its name "feels" more specific.
- Output 1-3 clarifying questions a junior advocate should ask before drafting.

Output ONLY this JSON (no markdown, no preamble):

{
  "primary_recommendation_index": 0,
  "reasoning": "2-3 sentences explaining why this is the right pick (or, if you
                override the score, explain the specific reason)",
  "alternatives_worth_considering": ["index numbers as strings, e.g. '1', '3'"],
  "confidence": "high | medium | low",
  "questions_for_advocate": ["1-3 short questions, each one sentence"]
}
PROMPT;

        $user = "UPLOADED DOCS: ".implode(', ', $uploadedDocTypes)."\n\nCANDIDATE DRAFTS (0-indexed):\n{$candidateLines}";

        try {
            $result = $this->gemini->generate($system, $user, usePro: false, temperature: 0.2);
            $text = trim($result['text']);
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```\s*$/', '', $text);
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start === false || $end === false) {
                return ['advice' => 'LLM returned non-JSON: '.substr($result['text'], 0, 200)];
            }
            $parsed = json_decode(substr($text, $start, $end - $start + 1), true);
            return $parsed ?: ['advice' => 'unparseable LLM response'];
        } catch (\Throwable $e) {
            return ['advice' => 'LLM call failed: '.$e->getMessage()];
        }
    }
}
