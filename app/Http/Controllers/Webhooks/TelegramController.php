<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Telegram webhook receiver.
 *
 * Set webhook once during deploy:
 *   curl -F "url=https://aarambhax.net/webhooks/telegram" \
 *        https://api.telegram.org/bot<TOKEN>/setWebhook
 *
 * Handles:
 *   - /start <pairing_code>     → links Telegram chat to user account
 *   - inline button callbacks   → done:<hearing_id>, reschedule:<hearing_id>
 */
class TelegramController extends Controller
{
    public function handle(Request $request, TelegramClient $client)
    {
        $payload = $request->all();
        Log::info('Telegram webhook', $payload);

        // Inline button callback
        if ($cb = ($payload['callback_query'] ?? null)) {
            return $this->handleCallback($cb, $client);
        }

        // Regular message
        if ($msg = ($payload['message'] ?? null)) {
            return $this->handleMessage($msg, $client);
        }

        return response()->json(['ok' => true]);
    }

    private function handleMessage(array $msg, TelegramClient $client)
    {
        $chatId = (string) ($msg['chat']['id'] ?? '');
        $text   = trim((string) ($msg['text'] ?? ''));

        if (Str::startsWith($text, '/start')) {
            $code = trim(Str::after($text, '/start'));
            if ($code === '') {
                $client->sendMessage($chatId, "Welcome to *Aarambhax Legal*. Pair this chat to your account at https://aarambhax.net/app/settings/telegram to receive hearing reminders.");
                return response()->json(['ok' => true]);
            }

            $user = User::where('telegram_pairing_code', $code)->first();
            if (! $user) {
                $client->sendMessage($chatId, "Invalid pairing code. Generate a fresh one at https://aarambhax.net/app/settings/telegram and try `/start <code>` again.");
                return response()->json(['ok' => true]);
            }

            $user->update([
                'telegram_chat_id' => $chatId,
                'telegram_pairing_code' => null,
            ]);
            $client->sendMessage($chatId, "✓ Paired to *{$user->name}*. You'll get hearing reminders 24 hours before each listing. Reply /help for commands.");
            return response()->json(['ok' => true]);
        }

        if ($text === '/help') {
            $client->sendMessage($chatId, "Aarambhax bot commands:\n/start <code> — pair this chat\n/today — list today's hearings\n/help — this message");
            return response()->json(['ok' => true]);
        }

        if ($text === '/today') {
            $user = User::where('telegram_chat_id', $chatId)->first();
            if (! $user) {
                $client->sendMessage($chatId, "This chat isn't paired yet. Run /start <code> first.");
                return response()->json(['ok' => true]);
            }
            $hearings = Hearing::where('user_id', $user->id)->whereDate('date', today())->orderBy('time')->get();
            if ($hearings->isEmpty()) {
                $client->sendMessage($chatId, "No hearings today. Have a good one.");
            } else {
                $lines = $hearings->map(fn ($h) => "📋 *".($h->case->title ?? 'Untitled')."* — ".($h->time ?: 'time TBD')." — ".($h->purpose ?: '—'))->implode("\n");
                $client->sendMessage($chatId, "Today's hearings:\n\n{$lines}");
            }
            return response()->json(['ok' => true]);
        }

        $client->sendMessage($chatId, "I didn't understand that. Try /help.");
        return response()->json(['ok' => true]);
    }

    private function handleCallback(array $cb, TelegramClient $client)
    {
        $data = (string) ($cb['data'] ?? '');
        $client->answerCallback((string) $cb['id'], 'Done');

        if (Str::startsWith($data, 'done:')) {
            $hearingId = (int) Str::after($data, 'done:');
            $hearing = Hearing::find($hearingId);
            if ($hearing) {
                $hearing->update(['outcome' => 'completed (via Telegram)']);
                $client->sendMessage((string) ($cb['message']['chat']['id'] ?? ''), "✓ Marked hearing #{$hearingId} as done.");
            }
        }

        return response()->json(['ok' => true]);
    }
}
