<?php

namespace App\Mail;

use App\Models\Deal;
use App\Support\DealTerms;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The customer's copy of a deal: what they're agreeing to and the secure checkout link.
 * Sent automatically when a deal is created (unless the closer opts out) and on demand.
 */
class DealCheckoutLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Deal $deal) {}

    public function envelope(): Envelope
    {
        $support = DealTerms::supportEmail();

        return new Envelope(
            subject: $this->deal->isRetainerDeal()
                ? "Your monthly retainer is ready — {$this->deal->business_name}"
                : "Your {$this->deal->business_name} AI receptionist is ready to activate",
            replyTo: $support ? [new Address($support)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deal-checkout-link',
            with: [
                'deal' => $this->deal,
                'firstName' => DealTerms::firstName($this->deal),
                'rows' => DealTerms::rows($this->deal),
                'checkoutUrl' => route('billing.deal.show', $this->deal),
                'supportEmail' => DealTerms::supportEmail(),
            ],
        );
    }
}
