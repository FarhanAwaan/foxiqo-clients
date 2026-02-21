<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\MarkOverdueInvoices;
use App\Console\Commands\ProcessSubscriptionRenewals;
use App\Console\Commands\ProcessTrialExpirations;
use App\Console\Commands\SendExpiryNotifications;
use App\Console\Commands\SendTrialEndingWarnings;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command(ProcessTrialExpirations::class)->dailyAt('08:00')->timezone('America/New_York');
Schedule::command(SendTrialEndingWarnings::class)->dailyAt('08:15')->timezone('America/New_York');
Schedule::command(ProcessSubscriptionRenewals::class)->dailyAt('08:30')->timezone('America/New_York');
Schedule::command(SendExpiryNotifications::class)->dailyAt('09:00')->timezone('America/New_York');
Schedule::command(MarkOverdueInvoices::class)->dailyAt('12:00')->timezone('America/New_York');

Schedule::command('queue:work database --tries=3 --timeout=90 --sleep=3 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
