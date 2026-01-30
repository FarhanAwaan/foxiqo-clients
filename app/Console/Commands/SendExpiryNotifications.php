<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiryWarningMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExpiryNotifications extends Command
{
    protected $signature = 'subscriptions:send-expiry-notifications';
    protected $description = 'Send notifications for subscriptions expiring soon';

    public function handle(): int
    {
        // Get subscriptions expiring in 7 days
        $subscriptions = Subscription::expiringSoon(7)->get();

        foreach ($subscriptions as $subscription) {
            $company = $subscription->company;

            Mail::to($company->effective_billing_email)
                ->send(new SubscriptionExpiryWarningMail($subscription));

            \App\Models\Notification::create([
                'company_id' => $company->id,
                'type' => 'subscription_expiry_warning',
                'channel' => 'email',
                'subject' => 'Subscription Expiring Soon',
                'body' => "Subscription for {$subscription->agent->name} expires on {$subscription->current_period_end->format('M d, Y')}",
                'data' => ['subscription_id' => $subscription->id],
                'sent_at' => now(),
            ]);

            $this->info("Sent expiry notification for subscription {$subscription->uuid}");
        }

        return Command::SUCCESS;
    }
}
