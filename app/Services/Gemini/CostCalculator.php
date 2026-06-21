<?php

namespace App\Services\Gemini;

/**
 * Computes the cost (in INR paise) of a Gemini API call given model + tokens.
 *
 * Pricing source: ai.google.dev/pricing — verify when prices change.
 * USD→INR conversion: configurable via GEMINI_USD_TO_INR (default 84).
 *
 * For free-tier calls (project hasn't exceeded its daily allowance), the
 * actual cost from Google is ₹0. We still compute the "what it would have
 * cost on paid tier" as `paid_equivalent_paise` for visibility — useful for
 * Vikash bhai to see savings vs. cost.
 */
class CostCalculator
{
    /**
     * @return array{cost_inr_paise:int, paid_equivalent_paise:int, model:string, tier:string}
     */
    public function compute(string $model, int $tokensIn, int $tokensOut, string $tier = 'paid'): array
    {
        $pricing = (array) config('services.gemini.pricing', []);
        $entry = $pricing[$model] ?? null;
        $usdToInr = (float) config('services.gemini.usd_to_inr', 84.0);

        if (! $entry || ! is_array($entry)) {
            // Unknown model — assume Flash pricing as a conservative fallback
            $entry = ['in' => 0.075, 'out' => 0.30];
        }

        $costUsd = ($tokensIn / 1_000_000.0) * (float) $entry['in']
                 + ($tokensOut / 1_000_000.0) * (float) $entry['out'];
        $costInrPaise = (int) round($costUsd * $usdToInr * 100);

        return [
            'cost_inr_paise' => $tier === 'free' ? 0 : $costInrPaise,
            'paid_equivalent_paise' => $costInrPaise,
            'model' => $model,
            'tier' => $tier,
        ];
    }

    public static function paiseToRupeesString(int $paise): string
    {
        return '₹'.number_format($paise / 100, 2);
    }
}
