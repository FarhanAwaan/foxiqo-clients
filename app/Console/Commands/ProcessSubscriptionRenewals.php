<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:process-renewals';
    protected $description = 'Process subscription renewals for expired periods';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $subscriptions = Subscription::active()
            ->where('current_period_end', '<', now())
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions to renew");

        foreach ($subscriptions as $subscription) {
            try {
                $subscriptionService->renew($subscription);
                $this->info("Renewed subscription {$subscription->uuid}");
            } catch (\Exception $e) {
                $this->error("Failed to renew {$subscription->uuid}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
