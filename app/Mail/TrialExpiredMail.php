<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Invoice $invoice,
        public PaymentLink $paymentLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Trial Has Ended — Payment Required: {$this->subscription->agent->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-expired',
            with: [
                'subscription' => $this->subscription,
                'company'      => $this->subscription->company,
                'agent'        => $this->subscription->agent,
                'plan'         => $this->subscription->plan,
                'invoice'      => $this->invoice,
                'paymentUrl'   => $this->paymentLink->payment_url,
            ],
        );
    }
}
