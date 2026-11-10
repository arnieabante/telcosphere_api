<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:run-automated-billing')
    ->daily() // 12 midnight
    ->when(fn () => config('services.billing.automated_enabled'))
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

// Each run only queues one job per router. Reconnects are applied at any hour,
// disconnects only inside each company's disconnect window.
Schedule::command('mikrotik:sweep')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/mikrotik-sweep.log'));

// Lets a single cron entry run the router jobs on the default queue as well. Turn this
// off when a supervised queue:work is running (MIKROTIK_SCHEDULER_RUNS_QUEUE=false).
Schedule::command('queue:work --stop-when-empty --max-time=55 --sleep=1')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->when(fn () => config('services.mikrotik.scheduler_runs_queue'));
