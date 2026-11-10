<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:run-automated-billing')
    ->daily() // 12 midnight
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(
        storage_path('logs/billing.log')
    );

Schedule::command('app:send-overdue-sms')
    ->dailyAt('09:00')
    ->timezone(config('sms.timezone'))
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(
        storage_path('logs/sms.log')
    );

// delivers queued SMS using only the existing schedule:run cron, no separate worker process needed
Schedule::command('queue:work --queue=' . config('sms.queue') . ' --stop-when-empty --max-time=55 --timeout=75')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();