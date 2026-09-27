<?php

namespace App\Mail;

use App\Models\Deal;
use App\Support\BillingTime;
use App\Support\DealTerms;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One customer-facing notice for every change to a deal's billing, so each is a few
 * lines of copy rather than its own template:
 *
 *   trial_changed    — the first-charge date moved (extended or shortened)
 *   cancel_scheduled — set to cancel at the end of the trial / paid period, no more charges
 *   cancel_withdrawn — a scheduled cancellation was undone; billing carries on
 *   cancelled        — cancelled now; nothing further will be charged
 */
class DealBillingUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KINDS = ['trial_changed', 'cancel_scheduled', 'cancel_withdrawn', 'cancelled'];

    public function __construct(
        public Deal $deal,
        public string $kind,
        public ?Carbon $previousChargeAt = null,
    ) {}

    public function envelope(): Envelope
    {
        $support = DealTerms::supportEmail();

        $subject = match ($this->kind) {
            'trial_changed' => "Your trial has been updated — {$this->deal->business_name}",
            'cancel_scheduled' => "Your subscription is set to cancel — {$this->deal->business_name}",
            'cancel_withdrawn' => "Your subscription will continue — {$this->deal->business_name}",
            default => "Your subscription has been cancelled — {$this->deal->business_name}",
        };

        return new Envelope(subject: $subject, replyTo: $support ? [new Address($support)] : []);
    }

    public function content(): Content
    {
        $next = $this->deal->nextChargeAt();

        return new Content(
            view: 'emails.deal-billing-update',
            with: [
                'deal' => $this->deal,
                'kind' => $this->kind,
                'firstName' => DealTerms::firstName($this->deal),
                'amount' => DealTerms::money($this->deal->agreed_monthly_price),
                'chargeDate' => BillingTime::date($next),
                'previousChargeDate' => $this->previousChargeAt ? BillingTime::date($this->previousChargeAt) : null,
                'endDate' => BillingTime::date($this->deal->paddle_scheduled_change_at ?? $this->deal->paddle_canceled_at),
                'supportEmail' => DealTerms::supportEmail(),
            ],
        );
    }
}
