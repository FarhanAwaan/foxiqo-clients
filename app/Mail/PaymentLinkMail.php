<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\PaymentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public PaymentLink $paymentLink
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Required: Invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link',
            with: [
                'invoice' => $this->invoice,
                'paymentLink' => $this->paymentLink,
                'company' => $this->invoice->company,
                'paymentUrl' => $this->paymentLink->payment_url,
            ],
        );
    }
}
