<?php

namespace App\Mail;

use App\Models\Invoice;
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
 * "Your card was charged" — sent when Paddle collects a retainer charge (a monthly renewal, or the
 * first charge when a free trial ends). Deliberately not SubscriptionRenewalMail: that one is for
 * invoices this app collects itself, shows a Due Date and says a payment link will follow, none of
 * which is true when Paddle has already taken the money.
 *
 * When a usage-overage charge for the same period ALSO went through, $usageInvoice combines it into
 * this one email as a second line — see PaddleLifecycleService::resolveRetainerReceipt() — rather
 * than the customer getting two separate receipts for two charges that happened the same day.
 */
class PaddleChargeReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $subjectName what the charge was for — the assistant's name, or the business
     * @param string $greeting the salutation, e.g. "Hi Sam" — already personalised by the caller
     */
    public function __construct(
        public Invoice $invoice,
        public string $subjectName,
        public string $greeting,
        public bool $convertedFromTrial,
        public ?Carbon $nextChargeAt = null,
        public ?Invoice $usageInvoice = null,
    ) {}

    public function envelope(): Envelope
    {
        $support = DealTerms::supportEmail();

        return new Envelope(
            subject: match (true) {
                $this->convertedFromTrial => "Your free trial has ended — payment received for {$this->subjectName}",
                $this->usageInvoice !== null => 'Payment received — ' . DealTerms::money($this->totalAmount()) . " for {$this->subjectName}",
                default => "Payment received — {$this->subjectName}",
            },
            replyTo: $support ? [new Address($support)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.paddle-charge-receipt',
            with: [
                'invoice' => $this->invoice,
                'usageInvoice' => $this->usageInvoice,
                'subjectName' => $this->subjectName,
                'greeting' => $this->greeting,
                'convertedFromTrial' => $this->convertedFromTrial,
                'amount' => DealTerms::money($this->invoice->amount),
                'usageAmount' => $this->usageInvoice ? DealTerms::money($this->usageInvoice->amount) : null,
                'totalAmount' => DealTerms::money($this->totalAmount()),
                'nextChargeDate' => $this->nextChargeAt ? BillingTime::date($this->nextChargeAt) : null,
            ],
        );
    }

    protected function totalAmount(): float
    {
        return (float) $this->invoice->amount + (float) ($this->usageInvoice->amount ?? 0);
    }
}
