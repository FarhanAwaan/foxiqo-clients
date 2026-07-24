<?php

namespace App\Console\Commands;

use App\Exceptions\SubscriptionRenewalBlockedException;
use App\Jobs\SendPaymentReminder;
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

        $this->info("Found {$subscriptions->count()} subscriptions due for renewal");

        foreach ($subscriptions as $subscription) {
            try {
                $subscriptionService->renew($subscription);
                $this->info("Renewed subscription {$subscription->uuid}");
            } catch (SubscriptionRenewalBlockedException $e) {
                $unpaidInvoice = $subscription->invoices()
                    ->where('billing_period_start', $subscription->current_period_start)
                    ->whereIn('status', ['sent', 'overdue', 'draft'])
                    ->latest()
                    ->first();

                if ($unpaidInvoice) {
                    SendPaymentReminder::dispatch($unpaidInvoice);
                }

                $this->warn("Skipped renewal for {$subscription->uuid}: payment not received for the current period. Reminder sent.");
            } catch (\Exception $e) {
                $this->error("Failed to renew {$subscription->uuid}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
