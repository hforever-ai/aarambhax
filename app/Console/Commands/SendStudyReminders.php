<?php

namespace App\Console\Commands;

use App\Models\DailyLog;
use App\Models\StudentNote;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;

class SendStudyReminders extends Command
{
    protected $signature = 'zenith:send-study-reminders {--dry-run : Print without sending}';
    protected $description = 'Evening Telegram nudge to students who have not logged today.';

    public function handle(TelegramClient $client): int
    {
        $students = User::where('role', 'student')
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_alerts_enabled', true)
            ->get();

        if ($students->isEmpty()) {
            $this->info('No Telegram-linked students found.');
            return self::SUCCESS;
        }

        $today = today()->toDateString();
        $sent = 0;

        foreach ($students as $student) {
            $alreadyLogged = DailyLog::where('user_id', $student->id)
                ->where('log_date', $today)
                ->exists();

            if ($alreadyLogged) {
                $this->line("  ↷ {$student->name} — already logged today, skipping");
                continue;
            }

            $firstName = explode(' ', $student->name)[0];

            // Pick one question from most recent scanned note as a teaser
            $teaser = '';
            $practiceNote = StudentNote::where('user_id', $student->id)
                ->whereNotNull('questions_json')
                ->latest('updated_at')
                ->first();

            if ($practiceNote) {
                $qs = json_decode($practiceNote->questions_json, true) ?? [];
                if (! empty($qs)) {
                    $q = $qs[array_rand($qs)];
                    $teaser = "\n\n🎯 *Practice question:*\n_{$q['question']}_";
                }
            }

            $appUrl = rtrim(config('app.url'), '/');
            $message = "📚 *Shrutam — Aaj ka log baaki hai, {$firstName}!*"
                . "\n\nAaj kya padha? Kitne ghante? 2 minute mein bharo — kal compare kar sakte ho."
                . $teaser
                . "\n\n👉 [{$appUrl}/app]({$appUrl}/app)";

            if ($this->option('dry-run')) {
                $this->line("[DRY] → {$student->name} ({$student->telegram_chat_id})");
                $this->line(str_replace("\n", ' / ', $message));
                $sent++;
                continue;
            }

            $ok = $client->sendMessage($student->telegram_chat_id, $message);
            if ($ok) {
                $this->info("  ✓ {$student->name}");
                $sent++;
            } else {
                $this->error("  ✗ {$student->name} — send failed");
            }
        }

        $this->info("Done — {$sent} reminder(s) sent.");
        return self::SUCCESS;
    }
}
