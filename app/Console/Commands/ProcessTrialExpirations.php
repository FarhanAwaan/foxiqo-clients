<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessTrialExpirations extends Command
{
    protected $signature = 'subscriptions:process-trial-expirations';
    protected $description = 'Convert expired free trials into paid subscriptions and send payment invoices';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $expired = Subscription::trialExpired()->with(['agent', 'company', 'plan'])->get();

        if ($expired->isEmpty()) {
            $this->info('No expired trials found.');
            return Command::SUCCESS;
        }

        foreach ($expired as $subscription) {
            try {
                $subscriptionService->expireTrial($subscription);
                $this->info("Trial expired for subscription {$subscription->uuid} ({$subscription->agent->name})");
            } catch (\Exception $e) {
                $this->error("Failed to expire trial {$subscription->uuid}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$expired->count()} expired trial(s).");

        return Command::SUCCESS;
    }
}
