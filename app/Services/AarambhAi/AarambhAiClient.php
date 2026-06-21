<?php

namespace App\Services\AarambhAi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the Aarambh AI pipeline running on the VPS.
 *
 * Configure via .env:
 *   AARAMBH_API_URL=https://aarambh-api.shrutam.ai
 *   AARAMBH_API_TOKEN=<bearer token from /opt/arambha-legal/.auth_token on VPS>
 *
 * The VPS exposes 6 endpoints (see app/app.py):
 *   POST /v1/ingest    multipart  (file=...)        → cleaned doc-pack
 *   POST /v1/architect json       (doc_pack, ...)   → CaseProfile JSON
 *   POST /v1/research  json       (case_profile)    → statutes + precedents (STUB)
 *   POST /v1/draft     json       (forum, type, ..) → markdown draft
 *   POST /v1/critic    json       (draft_markdown)  → CG HC Rules 2007 checklist
 *   POST /v1/verify    json       (draft_markdown)  → citation extraction (STUB)
 *
 * If AARAMBH_API_TOKEN is unset, methods throw RuntimeException so the rest
 * of the app fails fast rather than calling Gemini through unauthenticated routes.
 */
class AarambhAiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token,
        private readonly int $timeoutSeconds = 120,
    ) {
    }

    public static function make(): self
    {
        // Use config() not env() — env() returns null in production once config is cached
        return new self(
            baseUrl: rtrim((string) config('services.aarambh_ai.url', ''), '/'),
            token: config('services.aarambh_ai.token'),
            timeoutSeconds: (int) config('services.aarambh_ai.timeout', 120),
        );
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && ! empty($this->token);
    }

    /**
     * GET /healthz — no auth required. Returns ['ok'=>bool, ...] or null on failure.
     */
    public function healthz(): ?array
    {
        if ($this->baseUrl === '') {
            return null;
        }
        try {
            $r = Http::timeout(15)->get($this->baseUrl.'/healthz');
            return $r->successful() ? $r->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * POST /v1/ingest — upload one PDF/image, get back structured doc-pack.
     *
     * Multipart endpoint — must NOT use asJson() (it overrides the multipart
     * boundary). Build a separate request without the JSON body wrapper.
     */
    public function ingest(string $absoluteFilePath, ?string $filename = null): array
    {
        $this->guard();
        if (! is_file($absoluteFilePath)) {
            throw new RuntimeException("File not found: {$absoluteFilePath}");
        }
        $filename ??= basename($absoluteFilePath);

        try {
            $r = Http::withToken((string) $this->token)
                ->timeout($this->timeoutSeconds)
                ->retry(1, 1500, throw: false)
                ->acceptJson()                       // ← only accept JSON; do NOT send asJson
                ->attach('file', file_get_contents($absoluteFilePath), $filename)
                ->post($this->baseUrl.'/v1/ingest');
        } catch (ConnectionException $e) {
            throw new RuntimeException('Aarambh AI ingest connection failed: '.$e->getMessage(), 0, $e);
        }
        return $this->okOrThrow($r, '/v1/ingest');
    }

    /**
     * Parallel multi-file ingest via Http::pool. Returns an array keyed by
     * the input index with either an ingest-result array or a Throwable on
     * per-file failure. Partial failures don't throw — caller decides what
     * to do with errors (typically: persist successes, log + surface failures).
     *
     * Concurrency is capped by chunking the file list before passing it in;
     * pass at most 5 files per call. Larger batches: chunk in the caller.
     *
     * @param array<int, array{path:string, name:string}> $files
     * @return array<int, array|\Throwable>
     */
    public function ingestBatch(array $files): array
    {
        $this->guard();
        if (empty($files)) {
            return [];
        }

        // Pre-validate paths and slurp file bytes once. Doing this outside
        // the pool callback avoids race conditions and gives clean errors
        // for missing files before any HTTP work begins.
        $payloads = [];
        foreach ($files as $idx => $f) {
            $path = $f['path'] ?? '';
            $name = $f['name'] ?? ($path !== '' ? basename($path) : 'file');
            if (! is_file($path)) {
                $payloads[$idx] = new RuntimeException("File not found: {$path}");
                continue;
            }
            $payloads[$idx] = ['bytes' => file_get_contents($path), 'name' => $name];
        }

        $token = (string) $this->token;
        $baseUrl = $this->baseUrl;
        $timeout = $this->timeoutSeconds;

        // Build pool of requests for non-error payloads only.
        $poolKeys = array_keys(array_filter($payloads, fn ($p) => is_array($p)));

        $responses = Http::pool(function ($pool) use ($poolKeys, $payloads, $token, $baseUrl, $timeout) {
            return array_map(
                fn ($k) => $pool
                    ->withToken($token)
                    ->timeout($timeout)
                    ->acceptJson()
                    ->attach('file', $payloads[$k]['bytes'], $payloads[$k]['name'])
                    ->post($baseUrl.'/v1/ingest'),
                $poolKeys
            );
        });

        // Reassemble results in original index order
        $out = [];
        foreach ($payloads as $idx => $payload) {
            if ($payload instanceof \Throwable) {
                $out[$idx] = $payload;
                continue;
            }
            $position = array_search($idx, $poolKeys, true);
            $resp = $responses[$position] ?? null;
            if ($resp instanceof \Throwable) {
                $out[$idx] = $resp;
                continue;
            }
            if (! $resp || ! method_exists($resp, 'successful') || ! $resp->successful()) {
                $status = $resp && method_exists($resp, 'status') ? $resp->status() : '???';
                $body = $resp && method_exists($resp, 'body') ? substr((string) $resp->body(), 0, 200) : '';
                $out[$idx] = new RuntimeException("ingestBatch HTTP {$status}: {$body}");
                continue;
            }
            $out[$idx] = $resp->json() ?? [];
        }

        return $out;
    }

    /**
     * POST /v1/architect — synthesize CaseProfile from list of ingested doc-packs.
     */
    public function architect(array $docPack, ?string $userSummary = null): array
    {
        $this->guard();
        $r = $this->client()->post($this->baseUrl.'/v1/architect', [
            'doc_pack' => $docPack,
            'user_summary' => $userSummary,
        ]);
        return $this->okOrThrow($r, '/v1/architect');
    }

    /**
     * POST /v1/research — get statutes + precedents (STUB until RAG wired).
     */
    public function research(array $caseProfile, string $forum, string $caseCategory = 'civil'): array
    {
        $this->guard();
        $r = $this->client()->post($this->baseUrl.'/v1/research', [
            'case_profile' => $caseProfile,
            'forum' => $forum,
            'case_category' => $caseCategory,
        ]);
        return $this->okOrThrow($r, '/v1/research');
    }

    /**
     * POST /v1/draft — generate a draft. The few-shot template should be the
     * cleaned_text of the matching DraftCatalogueEntry from the router.
     */
    public function draft(
        string $forum,
        string $draftType,
        string $language,
        array $caseProfile,
        ?string $fewShotTemplate = null,
        ?array $researchBlock = null,
    ): array {
        $this->guard();
        $r = $this->client()->post($this->baseUrl.'/v1/draft', [
            'forum' => $forum,
            'draft_type' => $draftType,
            'language' => $language,
            'case_profile' => $caseProfile,
            'few_shot_template' => $fewShotTemplate,
            'research_block' => $researchBlock,
        ]);
        return $this->okOrThrow($r, '/v1/draft');
    }

    /**
     * POST /v1/analyse — strategic analysis from doc-pack + state.
     * Caller is expected to have already run architect() to populate state_json.
     *
     * @return array{analysis_markdown:string,updated_state_json:array,model_used:string,tokens_in:int,tokens_out:int,elapsed_ms:int}
     */
    public function analyse(
        string $systemPrompt,
        array $docPack,
        array $stateJson,
        ?string $userSummary = null,
        ?int $caseId = null,
        ?string $geminiApiKey = null,
        ?string $modelOverride = null,
    ): array {
        $this->guard();
        $payload = [
            'case_id' => $caseId,
            'doc_pack' => $docPack,
            'state_json' => empty($stateJson) ? new \stdClass() : $stateJson,
            'system_prompt' => $systemPrompt,
            'user_summary' => $userSummary,
        ];
        if ($geminiApiKey) { $payload['gemini_api_key'] = $geminiApiKey; }
        if ($modelOverride) { $payload['model'] = $modelOverride; }
        $r = $this->client()->post($this->baseUrl.'/v1/analyse', $payload);
        return $this->okOrThrow($r, '/v1/analyse');
    }

    /**
     * POST /v1/chat — single conversation turn. Returns assistant reply.
     *
     * @param array $recentMessages array of ['role'=>'user|assistant', 'content'=>string]
     * @return array{assistant_message:string,model_used:string,tokens_in:int,tokens_out:int,elapsed_ms:int}
     */
    public function chat(
        string $systemPrompt,
        string $newUserMessage,
        array $stateJson = [],
        ?string $contextSummaryMd = null,
        array $recentMessages = [],
        ?int $conversationId = null,
        ?string $geminiApiKey = null,
        ?string $modelOverride = null,
    ): array {
        $this->guard();
        $payload = [
            'conversation_id' => $conversationId,
            'state_json' => empty($stateJson) ? new \stdClass() : $stateJson,
            'context_summary_md' => $contextSummaryMd,
            'recent_messages' => $recentMessages,
            'new_user_message' => $newUserMessage,
            'system_prompt' => $systemPrompt,
        ];
        if ($geminiApiKey) { $payload['gemini_api_key'] = $geminiApiKey; }
        if ($modelOverride) { $payload['model'] = $modelOverride; }
        $r = $this->client()->post($this->baseUrl.'/v1/chat', $payload);
        return $this->okOrThrow($r, '/v1/chat');
    }

    /**
     * POST /v1/karya — run a Karya (case_brief / timeline / argument_synopsis /
     * reply_to_notice / samjhao / ...) against a doc-pack + state.
     *
     * @return array{output_markdown:string,output_json:array,model_used:string,tokens_in:int,tokens_out:int,elapsed_ms:int}
     */
    public function karya(
        string $karyaType,
        string $modelTier,
        string $systemPrompt,
        array $docPack,
        array $stateJson = [],
        ?string $userInstruction = null,
        ?int $karyaId = null,
        ?string $geminiApiKey = null,
        ?string $modelOverride = null,
    ): array {
        $this->guard();
        $payload = [
            'karya_id' => $karyaId,
            'karya_type' => $karyaType,
            'model_tier' => $modelTier,
            'doc_pack' => $docPack,
            'state_json' => empty($stateJson) ? new \stdClass() : $stateJson,
            'system_prompt' => $systemPrompt,
            'user_instruction' => $userInstruction,
        ];
        if ($geminiApiKey) {
            $payload['gemini_api_key'] = $geminiApiKey;
        }
        if ($modelOverride) {
            $payload['model'] = $modelOverride;
        }
        $r = $this->client()->post($this->baseUrl.'/v1/karya', $payload);
        return $this->okOrThrow($r, '/v1/karya');
    }

    /**
     * POST /v1/critic — run CG HC Rules 2007 checklist on a draft.
     */
    public function critic(string $draftMarkdown, string $forum, string $draftType, string $language): array
    {
        $this->guard();
        $r = $this->client()->post($this->baseUrl.'/v1/critic', [
            'draft_markdown' => $draftMarkdown,
            'forum' => $forum,
            'draft_type' => $draftType,
            'language' => $language,
        ]);
        return $this->okOrThrow($r, '/v1/critic');
    }

    /**
     * POST /v1/verify — extract citations from a draft (STUB until IK API wired).
     */
    public function verify(string $draftMarkdown): array
    {
        $this->guard();
        $r = $this->client()->post($this->baseUrl.'/v1/verify', [
            'draft_markdown' => $draftMarkdown,
        ]);
        return $this->okOrThrow($r, '/v1/verify');
    }

    // ─── Internals ─────────────────────────────────────────────────────────

    private function client(): PendingRequest
    {
        return Http::withToken((string) $this->token)
            ->timeout($this->timeoutSeconds)
            ->retry(2, 1500, throw: false)
            ->acceptJson()
            ->asJson();
    }

    private function guard(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Aarambh AI client not configured: set AARAMBH_API_URL and AARAMBH_API_TOKEN in .env'
            );
        }
    }

    private function okOrThrow(\Illuminate\Http\Client\Response $r, string $endpoint): array
    {
        if ($r->successful()) {
            return $r->json() ?? [];
        }
        throw new RuntimeException(
            "Aarambh AI {$endpoint} failed with HTTP {$r->status()}: ".substr($r->body(), 0, 500)
        );
    }
}
