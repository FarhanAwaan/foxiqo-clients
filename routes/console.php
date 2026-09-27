<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\MarkOverdueInvoices;
use App\Console\Commands\ProcessSubscriptionRenewals;
use App\Console\Commands\ProcessTrialExpirations;
use App\Console\Commands\ReconcilePaddle;
use App\Console\Commands\SendExpiryNotifications;
use App\Console\Commands\SendPaddleTrialReminders;
use App\Console\Commands\SendTrialEndingWarnings;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command(ProcessTrialExpirations::class)->dailyAt('08:00')->timezone('America/New_York');
Schedule::command(SendTrialEndingWarnings::class)->dailyAt('08:15')->timezone('America/New_York');
Schedule::command(ProcessSubscriptionRenewals::class)->dailyAt('08:30')->timezone('America/New_York');
Schedule::command(SendExpiryNotifications::class)->dailyAt('09:00')->timezone('America/New_York');
Schedule::command(MarkOverdueInvoices::class)->dailyAt('12:00')->timezone('America/New_York');

// Paddle: the webhooks do the real-time work; these two make it hands-off when one is missed.
// Reconcile polls Paddle for paid deals, subscription state and due charges the webhooks didn't
// deliver (in the background — it makes HTTP calls and must not hold up the every-minute queue
// worker below). Reminders warn the customer AND the admin before a trial's first charge.
// (withoutOverlapping(30): a background run that dies would otherwise hold its lock for the default 24 hours.)
Schedule::command(ReconcilePaddle::class)->everyFifteenMinutes()->withoutOverlapping(30)->runInBackground();
Schedule::command(SendPaddleTrialReminders::class)->dailyAt('08:20')->timezone('America/New_York');

Schedule::command('queue:work database --tries=3 --timeout=90 --sleep=3 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();
