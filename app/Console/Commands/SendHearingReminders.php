<?php

namespace App\Console\Commands;

use App\Models\Hearing;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;

class SendHearingReminders extends Command
{
    protected $signature = 'aarambhax:send-hearing-reminders {--dry-run : Print what would be sent without sending}';

    protected $description = 'Send Telegram reminders for hearings in the next 24 hours.';

    public function handle(TelegramClient $client): int
    {
        $tomorrow = today()->addDay();
        $today = today();

        $hearings = Hearing::with(['case', 'user'])
            ->whereBetween('date', [$today, $tomorrow])
            ->whereNull('reminded_at')
            ->whereHas('user', fn ($q) => $q->whereNotNull('telegram_chat_id')->where('telegram_alerts_enabled', true))
            ->get();

        if ($hearings->isEmpty()) {
            $this->info('No hearings to remind about.');
            return self::SUCCESS;
        }

        $this->info("Sending reminders for {$hearings->count()} hearing(s)...");

        foreach ($hearings as $hearing) {
            $caseTitle = $hearing->case?->title ?? 'Untitled case';
            $caseNo = $hearing->case?->case_no ?? '—';
            $court = $hearing->court ?: ($hearing->case?->court_name ?: 'Court TBD');
            $time = $hearing->time ?: 'time TBD';
            $purpose = $hearing->purpose ?: '—';
            $when = $hearing->date->isToday() ? 'Today' : 'Tomorrow';

            $message = "🔔 *{$when}'s hearing*\n\n📋 {$caseTitle}\n📁 {$caseNo}\n🏛 {$court}\n⏰ {$time}\n📌 {$purpose}";

            $buttons = [
                ['text' => '✅ Done', 'callback_data' => "done:{$hearing->id}"],
                ['text' => '🔄 Reschedule', 'callback_data' => "reschedule:{$hearing->id}"],
            ];

            if ($this->option('dry-run')) {
                $this->line("[DRY] To chat {$hearing->user->telegram_chat_id}: ".str_replace("\n", ' / ', $message));
                continue;
            }

            $sent = $client->sendMessage($hearing->user->telegram_chat_id, $message, $buttons);
            if ($sent) {
                $hearing->update(['reminded_at' => now()]);
                $this->info("  ✓ #{$hearing->id} → {$hearing->user->name}");
            } else {
                $this->error("  ✗ #{$hearing->id} failed");
            }
        }

        return self::SUCCESS;
    }
}
