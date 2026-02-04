<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Invoice $invoice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Subscription Renewed: {$this->subscription->agent->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-renewal',
            with: [
                'subscription' => $this->subscription,
                'invoice' => $this->invoice,
                'company' => $this->subscription->company,
                'agent' => $this->subscription->agent,
                'plan' => $this->subscription->plan,
            ],
        );
    }
}
