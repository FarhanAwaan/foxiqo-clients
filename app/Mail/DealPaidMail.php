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
 * "Payment received — here's what happens next." Uses Paddle's confirmed first-charge
 * date (the deal's mirrored trial end), so the date promised here is the date charged.
 */
class DealPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param bool $invited whether a portal-login invitation went out alongside this — false for a
     *   repeat customer who already has one, so the email doesn't promise a link that never comes.
     */
    public function __construct(public Deal $deal, public bool $invited = true) {}

    public function envelope(): Envelope
    {
        $support = DealTerms::supportEmail();

        return new Envelope(
            subject: $this->deal->isRetainerDeal()
                ? "Your monthly retainer is set up — {$this->deal->business_name}"
                : "Payment received — welcome, {$this->deal->business_name}",
            replyTo: $support ? [new Address($support)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deal-paid',
            with: [
                'deal' => $this->deal,
                'invited' => $this->invited,
                'firstName' => DealTerms::firstName($this->deal),
                'rows' => DealTerms::rows($this->deal, confirmed: true),
                'loginUrl' => route('login'),
                'supportEmail' => DealTerms::supportEmail(),
            ],
        );
    }
}
