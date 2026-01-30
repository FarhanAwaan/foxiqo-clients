<?php

namespace App\Jobs;

use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function handle(): void
    {
        $company = $this->invoice->company;

        Mail::to($company->effective_billing_email)
            ->send(new PaymentReminderMail($this->invoice));

        // Log notification
        \App\Models\Notification::create([
            'company_id' => $company->id,
            'type' => 'payment_reminder',
            'channel' => 'email',
            'subject' => "Payment Reminder: Invoice {$this->invoice->invoice_number}",
            'body' => "Reminder for invoice {$this->invoice->invoice_number}",
            'data' => ['invoice_id' => $this->invoice->id],
            'sent_at' => now(),
        ]);
    }
}
