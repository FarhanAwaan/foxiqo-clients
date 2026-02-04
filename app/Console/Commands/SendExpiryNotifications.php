<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\EmailService;
use Illuminate\Console\Command;

class SendExpiryNotifications extends Command
{
    protected $signature = 'subscriptions:send-expiry-notifications';
    protected $description = 'Send notifications for subscriptions expiring soon';

    public function handle(EmailService $emailService): int
    {
        $subscriptions = Subscription::expiringSoon(7)->get();

        foreach ($subscriptions as $subscription) {
            $emailService->sendSubscriptionExpiryWarning($subscription);

            $this->info("Sent expiry notification for subscription {$subscription->uuid}");
        }

        $this->info("Sent {$subscriptions->count()} expiry notification(s).");

        return Command::SUCCESS;
    }
}
