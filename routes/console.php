<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\ProcessSubscriptionRenewals;
use App\Console\Commands\SendExpiryNotifications;
use App\Console\Commands\MarkOverdueInvoices;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command(ProcessSubscriptionRenewals::class)->dailyAt('08:30')->timezone('America/New_York');
Schedule::command(SendExpiryNotifications::class)->dailyAt('09:00')->timezone('America/New_York');
Schedule::command(MarkOverdueInvoices::class)->dailyAt('12:00')->timezone('America/New_York');

Schedule::command('queue:work database --tries=3 --timeout=90 --sleep=3 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
