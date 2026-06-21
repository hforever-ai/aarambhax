<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily 8 PM IST: Nudge students who haven't logged today + one practice question teaser.
Schedule::command('zenith:send-study-reminders')
    ->dailyAt('20:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

// Daily 6 AM IST: Telegram reminders for hearings in the next 24 hours.
// Add to system cron via: * * * * *  cd /path && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('aarambhax:send-hearing-reminders')
    ->dailyAt('06:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();
