<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Support\BillingTime;
use App\Events\PaymentReceived;
use App\Exceptions\InvoiceAlreadyPaidException;
use Carbon\Carbon;

class InvoiceService
{
    public function __construct(
        protected AuditService $auditService,
        protected EmailService $emailService
    ) {}

    /**
     * The invoice for the subscription's current period — or for an explicit one, which is for a
     * Paddle charge that belongs to an earlier period than the one the subscription is now in.
     */
    public function createForSubscription(Subscription $subscription, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): Invoice
    {
        $amount = $subscription->getEffectivePrice();
        $dueDays = SystemSetting::getValue('invoice_due_days', 7);

        $periodStart ??= $subscription->current_period_start ?? Carbon::today();
        $periodEnd ??= $subscription->current_period_end ?? Carbon::today()->addDays(30);

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
     * Record a Paddle charge that isn't attached to a Subscription — the deal's one-time
     * activation fee (created the moment Paddle confirms the transaction, before any
     * Agent/Subscription exists), or a retainer charge that lands before an assistant is
     * attached to the deal. Tied to the deal (deal_id) so it can be listed there and
     * adopted by the Subscription later. A point-in-time charge unless a real billing
     * period is given.
     */
    public function createStandaloneInvoice(
        int $companyId,
        string $invoiceType,
        float $amount,
        string $paddleTransactionId,
        ?string $paddleStatus = null,
        ?int $dealId = null,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null,
        ?array $paddleTotals = null
    ): Invoice {
        // "Today" is the business day the customer was charged, not the server's (see BillingTime::businessDay).
        $periodStart ??= BillingTime::businessDay(now());
        $periodEnd ??= $periodStart->copy();

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'subscription_id' => null,
            'deal_id' => $dealId,
            'company_id' => $companyId,
            'invoice_type' => $invoiceType,
            'amount' => $amount,
            'status' => 'draft',
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'due_date' => BillingTime::businessDay(now()),
            'paddle_transaction_id' => $paddleTransactionId,
            'paddle_status' => $paddleStatus,
            'paddle_charged_amount' => $paddleTotals['charged'] ?? null,
            'paddle_tax_amount' => $paddleTotals['tax'] ?? null,
            'paddle_fee_amount' => $paddleTotals['fee'] ?? null,
        ]);

        $this->auditService->log('invoice_created', $invoice);

        return $invoice;
    }

    /**
     * Record what Paddle's transaction actually shows as charged/taxed/kept-as-fee against an
     * invoice already settled by it — the reconciliation figures alongside our own `amount`
     * (App\Support\PaddleMoney::fromTransactionTotals()). Never touches `amount` itself: that stays
     * the agreed price revenue reporting already reads.
     *
     * @param array{charged: ?float, tax: ?float, fee: ?float} $totals
     */
    public function recordPaddleTotals(Invoice $invoice, array $totals): void
    {
        $invoice->update([
            'paddle_charged_amount' => $totals['charged'] ?? null,
            'paddle_tax_amount' => $totals['tax'] ?? null,
            'paddle_fee_amount' => $totals['fee'] ?? null,
        ]);
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

    /** Exposed for the rare case a caller builds an Invoice row itself rather than through one of the create*() methods above (see PaddleLifecycleService::applyUsageCharge()'s unmatched-charge fallback). */
    public function generateInvoiceNumber(): string
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
