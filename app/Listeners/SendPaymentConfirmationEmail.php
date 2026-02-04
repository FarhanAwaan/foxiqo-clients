<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\EmailService;

class SendPaymentConfirmationEmail
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function handle(PaymentReceived $event): void
    {
        $this->emailService->sendPaymentConfirmation(
            $event->invoice,
            $event->payment
        );
    }
}
