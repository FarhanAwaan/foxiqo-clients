<?php

namespace App\Mail;

use App\Models\PaymentReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentReceipt $receipt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Receipt Rejected: Invoice {$this->receipt->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt-rejected',
            with: [
                'receipt' => $this->receipt,
                'invoice' => $this->receipt->invoice,
                'company' => $this->receipt->invoice->company,
                'paymentUrl' => $this->receipt->paymentLink?->payment_url,
            ],
        );
    }
}
