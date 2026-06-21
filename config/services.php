<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Aarambh AI pipeline running on the Hostinger VPS (FastAPI on :8090,
    // exposed publicly via Cloudflare Tunnel). See app/Services/AarambhAi/.
    'aarambh_ai' => [
        'url' => env('AARAMBH_API_URL'),
        'token' => env('AARAMBH_API_TOKEN'),
        'timeout' => (int) env('AARAMBH_API_TIMEOUT', 120),
    ],

    // Gemini direct API (used by EditOrchestrator etc. — separate from VPS pipeline)
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model_fast' => env('GEMINI_MODEL_FAST', 'gemini-2.5-flash'),
        'model_pro' => env('GEMINI_MODEL_PRO', 'gemini-2.5-pro'),

        // Free-tier keys (5 projects under Vikash bhai's Google account).
        // Round-robin'd by GeminiKeyRouter so each project's daily quota
        // (1500 Flash Lite, 250 Flash) is used evenly. Total free capacity:
        // 7500 Flash Lite + 1250 Flash calls/day.
        'free_keys' => [
            1 => env('GEMINI_API_KEY_FREE_1'),
            2 => env('GEMINI_API_KEY_FREE_2'),
            3 => env('GEMINI_API_KEY_FREE_3'),
            4 => env('GEMINI_API_KEY_FREE_4'),
            5 => env('GEMINI_API_KEY_FREE_5'),
        ],
        'model_free_lite' => env('GEMINI_MODEL_FREE_LITE', 'gemini-2.5-flash-lite'),
        'model_free_flash' => env('GEMINI_MODEL_FREE_FLASH', 'gemini-2.5-flash'),

        // Per-million-token prices in USD. Convert to INR via 'usd_to_inr'.
        // Source: ai.google.dev/pricing (verify when prices change).
        'pricing' => [
            'gemini-2.5-pro' => ['in' => 1.25, 'out' => 5.00],
            'gemini-2.5-flash' => ['in' => 0.075, 'out' => 0.30],
            'gemini-2.5-flash-lite' => ['in' => 0.0375, 'out' => 0.15],
        ],
        'usd_to_inr' => (float) env('GEMINI_USD_TO_INR', 84.0),
    ],

    // DeepSeek — invoked through the VPS FastAPI pipeline (key lives on VPS, not here).
    // Used as a chat-tab model override option. We only carry model IDs and pricing.
    'deepseek' => [
        'model_flash' => env('DEEPSEEK_MODEL_FLASH', 'deepseek-v4-flash'),
        'model_pro' => env('DEEPSEEK_MODEL_PRO', 'deepseek-v4-pro'),
        'pricing' => [
            'deepseek-v4-flash' => ['in' => 0.14, 'out' => 0.28],
            'deepseek-v4-pro' => ['in' => 0.435, 'out' => 0.87],
        ],
    ],

    // Aarambh app config (admin notification recipients, quota limits, etc.)
    'aarambh_app' => [
        // Comma-separated list of admin emails to notify on new registrations.
        // Defaults to the founders. Set ADMIN_NOTIFY_EMAILS in .env to override.
        'admin_notify_emails' => env(
            'ADMIN_NOTIFY_EMAILS',
            'hforever@gmail.com,advvikashagrawal@gmail.com'
        ),

        // Per-user Gemini API quota limits. Admins bypass these.
        'quota_per_hour' => (int) env('USER_QUOTA_PER_HOUR', 60),
        'quota_per_day' => (int) env('USER_QUOTA_PER_DAY', 200),
    ],

];
