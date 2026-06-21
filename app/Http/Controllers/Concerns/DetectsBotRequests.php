<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Header-based bot detection — kills Tier 0 (script kiddies) and most of
 * Tier 1 (curl/python with spoofed UA). Does NOT catch headless browsers
 * (Tier 2) — that's what Cloudflare Turnstile is for.
 *
 * Layered defense: this is one of several gates the registration / login
 * flow runs. Each gate is cheap and stacks; bypassing all of them is hard.
 *
 * Caller pattern:
 *   if ($this->looksLikeBot($request)) {
 *       return $this->botRejectResponse($request);
 *   }
 */
trait DetectsBotRequests
{
    /**
     * Patterns of User-Agent strings that obviously aren't browsers.
     * Conservative list — only well-known automation tools. Real browsers
     * NEVER match these patterns.
     */
    private const BOT_UA_PATTERNS = [
        'bot', 'crawl', 'spider', 'scrape', 'fetch',
        'curl', 'wget', 'python-requests', 'python-urllib',
        'go-http-client', 'java/', 'apache-httpclient', 'libwww',
        'okhttp', 'httpie', 'postmanruntime', 'insomnia',
        'aiohttp', 'node-fetch', 'axios', 'guzzlehttp',
    ];

    /**
     * Hosts allowed to be the Origin header on auth POSTs.
     * Production + dev. Add staging if needed.
     */
    private const ALLOWED_ORIGIN_HOSTS = [
        'aarambhax.in',
        'www.aarambhax.in',
        '127.0.0.1',
        'localhost',
    ];

    public function looksLikeBot(Request $request): bool
    {
        $ua = (string) $request->header('User-Agent', '');

        // 1. Empty or missing User-Agent — every legitimate browser sends one
        if ($ua === '') {
            return true;
        }

        // 2. UA contains a known automation tool name
        $uaLower = strtolower($ua);
        foreach (self::BOT_UA_PATTERNS as $needle) {
            if (str_contains($uaLower, $needle)) {
                return true;
            }
        }

        // 3. Real browsers ALWAYS send Accept-Language on form POSTs.
        //    Bots using basic libraries usually don't.
        if (! $request->hasHeader('Accept-Language')) {
            return true;
        }

        // 4. Real browsers send Accept including text/html on form POSTs
        //    (since they navigate to the next page after submit).
        $accept = (string) $request->header('Accept', '');
        if ($accept !== '' && ! str_contains($accept, 'text/html') && ! str_contains($accept, '*/*')) {
            return true;
        }

        // 5. If Origin is set (which browsers always do on cross-origin POSTs
        //    and modern browsers do on same-origin POSTs too), it must match
        //    our domain. CSRF token check below adds another layer.
        $origin = $request->header('Origin');
        if ($origin) {
            $host = parse_url($origin, PHP_URL_HOST);
            if (! in_array($host, self::ALLOWED_ORIGIN_HOSTS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Standard rejection response for bot-like requests. Returns a 422
     * (semantic: "we can see your request but it looks invalid") with no
     * detail of WHY — don't help the bot debug itself.
     */
    public function botRejectResponse(Request $request)
    {
        // Soft-rejection: pretend it kinda worked so the bot doesn't probe further.
        // Don't tell them WHICH check tripped. For real users mistakenly caught,
        // they'll see a generic message and can contact admin.
        if ($request->expectsJson()) {
            return response()->json(['error' => 'invalid_request'], 422);
        }
        return back()
            ->withErrors(['email' => 'Your request could not be processed. If you believe this is an error, contact admin@aarambhax.in.'])
            ->withInput($request->only('name', 'email'));
    }
}
