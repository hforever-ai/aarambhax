<?php

namespace App\Services\Gemini;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin wrapper around the Gemini REST API.
 *
 * Configure via .env:
 *   GEMINI_API_KEY=<paid key>      GEMINI_API_KEY_FREE_1..5=<free keys>
 *   GEMINI_MODEL_FAST / GEMINI_MODEL_PRO / GEMINI_MODEL_FREE_LITE
 *
 * Key rotation: every generateContent call rotates through the WHOLE key pool
 * on a 429 (RESOURCE_EXHAUSTED). A 429 only reaches the caller / front-end once
 * EVERY key is exhausted. thinking is disabled and output capped at 64k.
 */
class GeminiClient
{
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $modelFast = 'gemini-2.5-flash',
        private readonly string $modelPro = 'gemini-2.5-pro',
    ) {
    }

    public static function make(): self
    {
        return new self(
            apiKey: config('services.gemini.api_key'),
            modelFast: config('services.gemini.model_fast', 'gemini-2.5-flash'),
            modelPro: config('services.gemini.model_pro', 'gemini-2.5-pro'),
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Full key pool: primary (paid) key first, then free-tier keys. Deduped.
     *
     * @return array<int,string>
     */
    public static function keyPool(): array
    {
        $keys = [];
        $primary = (string) config('services.gemini.api_key', '');
        if ($primary !== '') {
            $keys[] = $primary;
        }
        foreach ((array) config('services.gemini.free_keys', []) as $k) {
            $k = (string) $k;
            if ($k !== '' && ! in_array($k, $keys, true)) {
                $keys[] = $k;
            }
        }
        return $keys;
    }

    /**
     * POST generateContent, rotating through the whole pool on 429. Only throws
     * a 429 once every key is exhausted. Forces thinkingBudget=0 + 64k output.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public static function callWithRotation(string $model, array $payload, ?string $preferredKey = null, int $timeout = 60): array
    {
        $base = 'https://generativelanguage.googleapis.com/v1beta/models';

        $pool = self::keyPool();
        if ($preferredKey !== null && $preferredKey !== '') {
            $pool = array_values(array_filter($pool, fn ($k) => $k !== $preferredKey));
            array_unshift($pool, $preferredKey);
        }
        if (empty($pool)) {
            throw new RuntimeException('No Gemini keys configured');
        }

        $gc = $payload['generationConfig'] ?? [];
        $gc['maxOutputTokens'] = 65536;
        $gc['thinkingConfig'] = ['thinkingBudget' => 0];
        $payload['generationConfig'] = $gc;

        $total = count($pool);
        foreach ($pool as $i => $key) {
            $url = "{$base}/{$model}:generateContent?key=".urlencode($key);
            try {
                $response = Http::timeout($timeout)->post($url, $payload);
            } catch (ConnectionException $e) {
                throw new RuntimeException('Gemini connection failed: '.$e->getMessage(), 0, $e);
            }

            if ($response->successful()) {
                return $response->json();
            }

            $status = $response->status();
            if ($status === 429 || str_contains($response->body(), 'RESOURCE_EXHAUSTED')) {
                Log::warning('Gemini 429 — rotating key '.($i + 1).'/'.$total);
                continue;
            }

            throw new RuntimeException('Gemini API error '.$status.': '.$response->body());
        }

        throw new RuntimeException("All Gemini keys rate-limited (429) — {$total} keys exhausted", 429);
    }

    /**
     * Generate text. Stub when no key configured. Rotates keys on 429.
     *
     * @return array{text:string,tokens_input:int,tokens_output:int,model:string}
     */
    public function generate(string $systemPrompt, string $userPrompt, bool $usePro = false, float $temperature = 0.7): array
    {
        if (! $this->isConfigured()) {
            return [
                'text' => "[STUBBED — set GEMINI_API_KEY in .env to enable real generation]\n\nSystem: ".substr($systemPrompt, 0, 200)."\n\nUser: ".substr($userPrompt, 0, 200),
                'tokens_input' => 0,
                'tokens_output' => 0,
                'model' => 'stub',
            ];
        }

        $model = $usePro ? $this->modelPro : $this->modelFast;

        $data = self::callWithRotation($model, [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
            ],
            'generationConfig' => ['temperature' => $temperature],
        ], $this->apiKey, 60);

        $text = data_get($data, 'candidates.0.content.parts.0.text', '');
        $usage = $data['usageMetadata'] ?? [];

        return [
            'text' => $text,
            'tokens_input' => (int) ($usage['promptTokenCount'] ?? 0),
            'tokens_output' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'model' => $model,
        ];
    }
}
