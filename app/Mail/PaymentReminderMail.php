<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Reminder: Invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        $activeLink = $this->invoice->paymentLinks()
            ->active()
            ->latest()
            ->first();

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'invoice' => $this->invoice,
                'company' => $this->invoice->company,
                'paymentUrl' => $activeLink?->payment_url,
            ],
        );
    }
}
