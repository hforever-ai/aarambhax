<?php

namespace App\Console\Commands;

use App\Models\NewsletterBroadcast;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendNewsletterBroadcast extends Command
{
    protected $signature = 'aarambhax:send-broadcast {id} {--dry-run}';

    protected $description = 'Send a queued newsletter broadcast to subscribers.';

    public function handle(): int
    {
        $broadcast = NewsletterBroadcast::find((int) $this->argument('id'));
        if (! $broadcast) {
            $this->error("Broadcast not found.");
            return self::FAILURE;
        }

        if ($broadcast->status === 'sent') {
            $this->warn('Already sent.');
            return self::SUCCESS;
        }

        $subscribers = NewsletterSubscriber::where('confirmed', true)
            ->when($broadcast->language_filter !== 'both', function ($q) use ($broadcast) {
                return $q->whereIn('language_pref', [$broadcast->language_filter, 'both']);
            })->get();

        if ($subscribers->isEmpty()) {
            $this->warn('No confirmed subscribers match.');
            return self::SUCCESS;
        }

        $broadcast->update(['status' => 'sending', 'recipient_count' => $subscribers->count()]);

        $sent = 0; $failed = 0;
        $bodyHtml = $broadcast->body_html ?: Str::markdown($broadcast->body_md);

        foreach ($subscribers as $sub) {
            $unsubLink = url('/newsletter/unsubscribe/'.$sub->unsubscribe_token);
            $body = $bodyHtml.'<hr><p style="font-size:12px;color:#666;">'
                  .'You\'re receiving this because you subscribed to Aarambhax Legal updates. '
                  .'<a href="'.$unsubLink.'">Unsubscribe</a>.</p>';

            if ($this->option('dry-run')) {
                $this->line("[DRY] would send to {$sub->email}");
                $sent++;
                continue;
            }

            try {
                Mail::html($body, function ($m) use ($sub, $broadcast) {
                    $m->to($sub->email)->subject($broadcast->subject);
                });
                $sent++;
            } catch (\Throwable $e) {
                Log::error("Newsletter send failed for {$sub->email}: ".$e->getMessage());
                $failed++;
            }
        }

        $broadcast->update([
            'status' => 'sent',
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        $this->info("Done: {$sent} sent, {$failed} failed.");
        return self::SUCCESS;
    }
}
