<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Events\PaymentReceived;
use App\Exceptions\InvoiceAlreadyPaidException;
use Carbon\Carbon;

class InvoiceService
{
    public function __construct(
        protected AuditService $auditService,
        protected EmailService $emailService
    ) {}

    public function createForSubscription(Subscription $subscription): Invoice
    {
        $amount = $subscription->getEffectivePrice();
        $dueDays = SystemSetting::getValue('invoice_due_days', 7);

        $periodStart = $subscription->current_period_start ?? Carbon::today();
        $periodEnd = $subscription->current_period_end ?? Carbon::today()->addDays(30);

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'subscription_id' => $subscription->id,
            'company_id' => $subscription->company_id,
            'amount' => $amount,
            'status' => 'draft',
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'due_date' => Carbon::parse($periodStart)->addDays($dueDays),
        ]);

        $this->auditService->log('invoice_created', $invoice);

        return $invoice;
    }

    /**
     * Bill the metered usage for a period that just closed — a separate invoice
     * from the subscription's flat retainer, since the retainer keeps going through
     * whatever rail already collects it (Paddle's recurring charge, or the usual
     * Nsave/manual flow) and a variable usage amount can't ride along with that.
     * Billed in arrears because minutes aren't known until the period is over.
     */
    public function createUsageInvoice(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd, int $minutes): Invoice
    {
        $rate = (float) ($subscription->plan->per_minute_rate ?? 0);
        $dueDays = SystemSetting::getValue('invoice_due_days', 7);

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'subscription_id' => $subscription->id,
            'company_id' => $subscription->company_id,
            'invoice_type' => 'usage',
            'amount' => round($minutes * $rate, 2),
            'usage_minutes' => $minutes,
            'status' => 'draft',
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'due_date' => Carbon::today()->addDays($dueDays),
        ]);

        $this->auditService->log('usage_invoice_created', $invoice);

        return $invoice;
    }

    /**
     * Record a one-off Paddle charge that isn't tied to a subscription period —
     * currently just the deal's one-time activation fee, created the moment
     * Paddle confirms the transaction (before any Agent/Subscription exists to
     * attach a normal invoice to). A point-in-time charge, so billing_period is
     * just today rather than a real range.
     */
    public function createStandaloneInvoice(
        int $companyId,
        string $invoiceType,
        float $amount,
        string $paddleTransactionId,
        ?string $paddleStatus = null
    ): Invoice {
        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'subscription_id' => null,
            'company_id' => $companyId,
            'invoice_type' => $invoiceType,
            'amount' => $amount,
            'status' => 'draft',
            'billing_period_start' => Carbon::today(),
            'billing_period_end' => Carbon::today(),
            'due_date' => Carbon::today(),
            'paddle_transaction_id' => $paddleTransactionId,
            'paddle_status' => $paddleStatus,
        ]);

        $this->auditService->log('invoice_created', $invoice);

        return $invoice;
    }

    /**
     * Create a payment link for an invoice (self-hosted).
     */
    public function createPaymentLink(Invoice $invoice, bool $manual = false, bool $sendEmail = true): PaymentLink
    {
        if ($invoice->status === 'paid') {
            throw new InvoiceAlreadyPaidException('Cannot create payment link for a paid invoice.');
        }

        $expiryDays = SystemSetting::getValue('payment_link_expiry_days', 14);

        // Payment token and URL are auto-generated in PaymentLink::creating() event
        $paymentLink = PaymentLink::create([
            'invoice_id' => $invoice->id,
            'provider' => 'internal',
            'amount' => $invoice->amount,
            'status' => 'pending',
            'sent_at' => now(),
            'sent_manually' => $manual,
            'expires_at' => now()->addDays($expiryDays),
        ]);

        if ($invoice->status === 'draft') {
            $invoice->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $this->auditService->log('payment_link_created', $paymentLink);

        if ($sendEmail) {
            $this->emailService->sendPaymentLink($invoice, $paymentLink);
        }

        return $paymentLink;
    }

    /**
     * Alias for backward compatibility.
     */
    public function sendPaymentLink(Invoice $invoice, bool $manual = false): PaymentLink
    {
        return $this->createPaymentLink($invoice, $manual);
    }

    /**
     * Get or create an active payment link for an invoice.
     */
    public function getOrCreateActivePaymentLink(Invoice $invoice): ?PaymentLink
    {
        if ($invoice->status === 'paid') {
            return null;
        }

        // Find existing active payment link
        $activeLink = $invoice->paymentLinks()
            ->active()
            ->latest()
            ->first();

        if ($activeLink) {
            return $activeLink;
        }

        // Create a new one
        return $this->createPaymentLink($invoice);
    }

    public function markAsPaid(Invoice $invoice, string $provider, ?string $transactionId = null, ?PaymentLink $paymentLink = null, bool $fireEvent = true): Payment
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Update any open payment links
        $invoice->paymentLinks()
            ->whereIn('status', ['pending', 'sent'])
            ->update(['status' => 'paid', 'paid_at' => now()]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_link_id' => $paymentLink?->id,
            'amount' => $invoice->amount,
            'provider' => $provider,
            'provider_transaction_id' => $transactionId,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->auditService->log('payment_received', $payment);

        if ($fireEvent) {
            event(new PaymentReceived($invoice, $payment));
        }

        return $payment;
    }

    /**
     * Void an unpaid invoice.
     */
    public function voidInvoice(Invoice $invoice, ?string $reason = null): void
    {
        if ($invoice->status === 'paid') {
            throw new InvoiceAlreadyPaidException('Cannot void a paid invoice.');
        }

        $oldValues = $invoice->toArray();

        // Cancel all active payment links
        $invoice->paymentLinks()
            ->whereIn('status', ['pending', 'sent'])
            ->update(['status' => 'cancelled']);

        $invoice->update([
            'status' => 'voided',
            'notes' => $reason ? "Voided: {$reason}" : 'Voided',
        ]);

        $this->auditService->log('invoice_voided', $invoice, $oldValues);
    }

    /**
     * Check if an invoice can be safely voided.
     */
    public function canVoid(Invoice $invoice): bool
    {
        return !in_array($invoice->status, ['paid', 'voided']);
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
