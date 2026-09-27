<?php

namespace App\Console\Commands;

use App\Services\PaddleLifecycleService;
use Illuminate\Console\Command;

class SendPaddleTrialReminders extends Command
{
    protected $signature = 'paddle:send-trial-reminders {--days= : How many days before the first charge to remind (default: billing.trial_reminder_days)}';
    protected $description = 'Remind customers and admins that a Paddle trial\'s first charge is coming up';

    public function handle(PaddleLifecycleService $lifecycle): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : null;

        $sent = $lifecycle->sendTrialReminders($days);

        $this->info("Sent {$sent} Paddle trial reminder(s).");

        return Command::SUCCESS;
    }
}
