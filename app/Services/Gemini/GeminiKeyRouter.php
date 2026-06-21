<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Routes Gemini API calls between paid (Vikash bhai's billing-enabled key) and
 * free-tier keys (5 separate projects under his Google account).
 *
 * Tier rules:
 *   PAID  → ingest, final drafts, reply_to_notice — never redacted
 *   FREE  → analysis, karya brainstorm, chat — PII-redacted before send
 *
 * Free-tier load balancing: round-robin across 5 keys via a Cache-backed
 * counter. Each key is bound to a separate GCP project, so each gets its
 * own daily quota bucket (1500 Flash Lite, 250 Flash). Total free capacity:
 * 7500 Flash Lite + 1250 Flash calls/day.
 *
 * Models:
 *   gemini-2.5-flash-lite  — default for free-tier (analysis, karya brainstorm, chat)
 *   gemini-2.5-flash       — escalation when doc pack >= 10 docs OR for ingest
 */
class GeminiKeyRouter
{
    public const TIER_PAID = 'paid';
    public const TIER_FREE = 'free';

    private const FREE_KEY_COUNT = 5;
    private const ROUND_ROBIN_CACHE_KEY = 'gemini_free_key_index';

    /**
     * Pick the right key + model for a given call.
     *
     * @param  string  $tier  'paid' | 'free'
     * @param  string  $modelHint  'lite' | 'flash' (only relevant for free tier)
     * @return array{key:string, model:string, project_index:?int}
     */
    public function pick(string $tier, string $modelHint = 'lite'): array
    {
        if ($tier === self::TIER_PAID) {
            $key = (string) config('services.gemini.api_key', '');
            if ($key === '') {
                throw new RuntimeException('GEMINI_API_KEY not configured (paid tier)');
            }
            return [
                'key' => $key,
                'model' => $modelHint === 'flash'
                    ? (string) config('services.gemini.model_fast', 'gemini-2.5-flash')
                    : (string) config('services.gemini.model_fast', 'gemini-2.5-flash'),
                'project_index' => null,
            ];
        }

        // Free tier: round-robin across 5 keys
        $index = $this->nextRoundRobinIndex();
        $envKey = "GEMINI_API_KEY_FREE_{$index}";
        $key = (string) config("services.gemini.free_keys.{$index}", '');
        if ($key === '') {
            // Fallback to paid if no free key configured (shouldn't happen post-deploy)
            return $this->pick(self::TIER_PAID, $modelHint);
        }

        $model = $modelHint === 'flash'
            ? (string) config('services.gemini.model_free_flash', 'gemini-2.5-flash')
            : (string) config('services.gemini.model_free_lite', 'gemini-2.5-flash-lite');

        return [
            'key' => $key,
            'model' => $model,
            'project_index' => $index,
        ];
    }

    /**
     * Decide tier + model for an action, given doc count + flow type.
     *
     * Rules:
     *   ingest, draft, draft_edit, karya:reply_to_notice  → paid (Flash)
     *   analyse, chat, karya:* (except reply_to_notice)   → free
     *     ↳ if doc_count >= 10: free (Flash) instead of free (Flash Lite)
     *
     * @return array{tier:string, model_hint:string, redact_pii:bool}
     */
    public static function decideTier(string $flow, int $docCount = 0): array
    {
        // Always paid: produces filable artefact OR scans raw PII
        $paidFlows = ['ingest', 'draft', 'draft_edit', 'upload_pipeline', 'karya:reply_to_notice'];
        if (in_array($flow, $paidFlows, true)) {
            return [
                'tier' => self::TIER_PAID,
                'model_hint' => 'flash',
                'redact_pii' => false,
            ];
        }

        // Free tier with PII redaction. Escalate to Flash if doc-pack is heavy.
        $modelHint = $docCount >= 10 ? 'flash' : 'lite';
        return [
            'tier' => self::TIER_FREE,
            'model_hint' => $modelHint,
            'redact_pii' => true,
        ];
    }

    /**
     * Atomic round-robin counter via Cache::add + increment, falls back to
     * mt_rand if cache locking has issues. We don't need perfect round-robin —
     * just rough spreading across 5 keys.
     */
    private function nextRoundRobinIndex(): int
    {
        try {
            // Initialize counter if missing; increment otherwise
            Cache::add(self::ROUND_ROBIN_CACHE_KEY, 0, now()->addDays(1));
            $next = Cache::increment(self::ROUND_ROBIN_CACHE_KEY);
            return ((int) $next % self::FREE_KEY_COUNT) + 1;
        } catch (\Throwable) {
            // Cache miss / driver issues — random pick, still spreads load
            return mt_rand(1, self::FREE_KEY_COUNT);
        }
    }
}
