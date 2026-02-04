<?php

namespace App\Listeners;

use App\Events\CircuitBreakerTriggered;
use App\Services\EmailService;

class SendUsageAlertEmail
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function handle(CircuitBreakerTriggered $event): void
    {
        $this->emailService->sendUsageAlert($event->subscription);
    }
}
