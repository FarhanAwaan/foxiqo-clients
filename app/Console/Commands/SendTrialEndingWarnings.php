<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\EmailService;
use Illuminate\Console\Command;

class SendTrialEndingWarnings extends Command
{
    protected $signature = 'subscriptions:send-trial-ending-warnings {--days=3 : Days before trial end to send warning}';
    protected $description = 'Send warning emails for trials ending soon';

    public function handle(EmailService $emailService): int
    {
        $days = (int) $this->option('days');

        $subscriptions = Subscription::trialEndingSoon($days)
            ->with(['agent', 'company', 'plan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No trials ending soon.');
            return Command::SUCCESS;
        }

        foreach ($subscriptions as $subscription) {
            try {
                $emailService->sendTrialEndingWarning($subscription);

                // Mark as warned so we don't send again tomorrow
                $subscription->update(['trial_ending_warned' => true]);

                $daysLeft = $subscription->trialDaysRemaining();
                $this->info("Sent trial ending warning for {$subscription->uuid} ({$subscription->agent->name}) — {$daysLeft} day(s) left");
            } catch (\Exception $e) {
                $this->error("Failed to send warning for {$subscription->uuid}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$subscriptions->count()} trial ending warning(s).");

        return Command::SUCCESS;
    }
}
