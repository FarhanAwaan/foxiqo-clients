<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Support\DealTerms;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "We charged you for last period's usage" — sent once Paddle actually collects a usage-overage
 * one-time charge (PaddleLifecycleService::applyUsageCharge()), separate from the retainer's own
 * PaddleChargeReceiptMail because it's a different amount, on its own line, for a reason (minutes
 * used) the retainer receipt doesn't mention.
 */
class PaddleUsageChargeReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public Subscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        $support = DealTerms::supportEmail();

        return new Envelope(
            subject: "Usage charge \u{2014} " . DealTerms::money($this->invoice->amount) . ' for ' . ($this->subscription->agent->name ?? 'your assistant'),
            replyTo: $support ? [new Address($support)] : [],
        );
    }

    public function content(): Content
    {
        $rate = (float) ($this->subscription->plan->per_minute_rate ?? 0);

        return new Content(
            view: 'emails.paddle-usage-charge-receipt',
            with: [
                'invoice' => $this->invoice,
                'assistantName' => $this->subscription->agent->name ?? 'your assistant',
                'company' => $this->subscription->company,
                'amount' => DealTerms::money($this->invoice->amount),
                'minutes' => $this->invoice->usage_minutes,
                'rate' => DealTerms::money($rate),
                // billing_period_start/end are DATE columns already holding the correct business day
                // (BillingTime::businessDay() at write time) — format directly, the way every other
                // period display in this app does; running it through BillingTime::date() would
                // re-convert an already-correct calendar day to the display timezone and could shift
                // it across midnight, showing the wrong day.
                'period' => $this->invoice->billing_period_start && $this->invoice->billing_period_end
                    ? $this->invoice->billing_period_start->format('F j, Y') . ' – ' . $this->invoice->billing_period_end->format('F j, Y')
                    : null,
            ],
        );
    }
}
