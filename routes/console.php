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


Schedule::command(ProcessSubscriptionRenewals::class)->dailyAt('00:00');
Schedule::command(SendExpiryNotifications::class)->dailyAt('09:00');
Schedule::command(MarkOverdueInvoices::class)->dailyAt('00:00');
