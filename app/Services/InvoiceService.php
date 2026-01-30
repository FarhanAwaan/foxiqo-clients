<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Events\PaymentReceived;
use Carbon\Carbon;

class InvoiceService
{
    public function __construct(
        protected PayoneerService $payoneerService,
        protected AuditService $auditService
    ) {}

    public function createForSubscription(Subscription $subscription): Invoice
    {
        $amount = $subscription->getEffectivePrice();
        $dueDays = SystemSetting::getValue('invoice_due_days', 7);

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'subscription_id' => $subscription->id,
            'company_id' => $subscription->company_id,
            'amount' => $amount,
            'status' => 'draft',
            'billing_period_start' => $subscription->current_period_start,
            'billing_period_end' => $subscription->current_period_end,
            'due_date' => Carbon::parse($subscription->current_period_start)->addDays($dueDays),
        ]);

        $this->auditService->log('invoice_created', $invoice);

        return $invoice;
    }

    public function sendPaymentLink(Invoice $invoice, bool $manual = false): PaymentLink
    {
        $company = $invoice->company;
        $expiryDays = SystemSetting::getValue('payment_link_expiry_days', 14);

        $payoneerResponse = $this->payoneerService->createPaymentRequest(
            $invoice->amount,
            $invoice->invoice_number,
            $company->effective_billing_email,
            "Invoice {$invoice->invoice_number} for {$company->name}"
        );

        $paymentLink = PaymentLink::create([
            'invoice_id' => $invoice->id,
            'provider' => 'payoneer',
            'provider_reference' => $payoneerResponse['request_id'] ?? null,
            'payment_url' => $payoneerResponse['payment_url'],
            'amount' => $invoice->amount,
            'status' => 'sent',
            'sent_at' => now(),
            'sent_manually' => $manual,
            'expires_at' => now()->addDays($expiryDays),
        ]);

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->auditService->log('payment_link_sent', $paymentLink);

        return $paymentLink;
    }

    public function markAsPaid(Invoice $invoice, string $provider, ?string $transactionId = null): Payment
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Update any open payment links
        $invoice->paymentLinks()
            ->whereIn('status', ['created', 'sent'])
            ->update(['status' => 'paid', 'paid_at' => now()]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'provider' => $provider,
            'provider_transaction_id' => $transactionId,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->auditService->log('payment_received', $payment);

        event(new PaymentReceived($invoice, $payment));

        return $payment;
    }

    protected function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice
            ? intval(substr($lastInvoice->invoice_number, -4)) + 1
            : 1;

        return sprintf('INV-%s%s-%04d', $year, $month, $sequence);
    }
}
