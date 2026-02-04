<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Services\EmailService;

class SendSubscriptionActivatedEmail
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function handle(SubscriptionActivated $event): void
    {
        $this->emailService->sendSubscriptionActivated(
            $event->subscription,
            $event->invoice
        );
    }
}
