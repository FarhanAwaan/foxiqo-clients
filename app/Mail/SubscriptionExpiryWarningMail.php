<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Subscription Expiring Soon: {$this->subscription->agent->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-expiry-warning',
            with: [
                'subscription' => $this->subscription,
                'company' => $this->subscription->company,
                'agent' => $this->subscription->agent,
                'plan' => $this->subscription->plan,
                'daysRemaining' => now()->diffInDays($this->subscription->current_period_end, false),
            ],
        );
    }
}
