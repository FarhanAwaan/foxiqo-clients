<?php

namespace App\Mail;

use App\Models\PaymentReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReceiptUploadedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentReceipt $receipt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Receipt: {$this->receipt->invoice->company->name} — Invoice {$this->receipt->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-receipt-uploaded',
            with: [
                'receipt' => $this->receipt,
                'invoice' => $this->receipt->invoice,
                'company' => $this->receipt->invoice->company,
                'reviewUrl' => route('admin.receipts.show', $this->receipt),
                'dashboardUrl' => route('admin.dashboard'),
            ],
        );
    }
}
