<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tiny wrapper around Telegram Bot API.
 *
 * Configure via .env:
 *   TELEGRAM_BOT_TOKEN=<from @BotFather>
 *   TELEGRAM_BOT_USERNAME=AarambhaxBot
 *
 * Without the token, all calls log + no-op (so the command runs cleanly).
 */
class TelegramClient
{
    private string $baseUrl = 'https://api.telegram.org';

    public function __construct(
        private readonly ?string $token = null,
        private readonly ?string $botUsername = null,
    ) {
    }

    public static function make(): self
    {
        return new self(
            token: env('TELEGRAM_BOT_TOKEN'),
            botUsername: env('TELEGRAM_BOT_USERNAME'),
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->token);
    }

    public function botUsername(): ?string
    {
        return $this->botUsername;
    }

    /**
     * Send a Markdown-formatted message with optional inline buttons.
     *
     * @param  array  $buttons  Format: [['text' => 'Done', 'callback_data' => 'done:123'], ...]
     */
    public function sendMessage(string $chatId, string $markdown, array $buttons = []): bool
    {
        if (! $this->isConfigured()) {
            Log::info('TelegramClient stubbed (no token) — message would be sent', [
                'chat_id' => $chatId,
                'preview' => mb_substr($markdown, 0, 120),
            ]);
            return true; // stub success
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $markdown,
            'parse_mode' => 'Markdown',
        ];

        if (! empty($buttons)) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => array_chunk($buttons, 3),
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/bot{$this->token}/sendMessage", $payload);

            if ($response->failed()) {
                Log::warning('Telegram sendMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception: '.$e->getMessage());
            return false;
        }
    }

    public function answerCallback(string $callbackQueryId, string $text = ''): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        try {
            return Http::timeout(5)
                ->post("{$this->baseUrl}/bot{$this->token}/answerCallbackQuery", [
                    'callback_query_id' => $callbackQueryId,
                    'text' => $text,
                ])->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram answerCallback exception: '.$e->getMessage());
            return false;
        }
    }
}
