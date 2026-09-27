<?php

namespace App\Mail;

use App\Models\Deal;
use App\Support\BillingTime;
use App\Support\DealTerms;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Heads-up before a Paddle trial converts. Not the generic TrialEndingMail: that one
 * says "an invoice will be sent", whereas Paddle charges the card on file automatically.
 */
class DealTrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Deal $deal) {}

    public function envelope(): Envelope
    {
        $support = DealTerms::supportEmail();
        $days = BillingTime::daysUntil($this->deal->nextChargeAt());
        $when = $days === null ? 'soon' : ($days <= 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in {$days} days"));

        return new Envelope(
            subject: "Your free trial ends {$when} — {$this->deal->business_name}",
            replyTo: $support ? [new Address($support)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deal-trial-ending',
            with: [
                'deal' => $this->deal,
                'firstName' => DealTerms::firstName($this->deal),
                'chargeDate' => BillingTime::date($this->deal->nextChargeAt()),
                'amount' => DealTerms::money($this->deal->agreed_monthly_price),
                'daysLeft' => BillingTime::daysUntil($this->deal->nextChargeAt()),
                'supportEmail' => DealTerms::supportEmail(),
            ],
        );
    }
}
