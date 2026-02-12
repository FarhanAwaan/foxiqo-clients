<?php

namespace App\Services;

use App\Events\SubscriptionActivated;
use App\Exceptions\SubscriptionHasPaidInvoiceException;
use App\Models\Agent;
use App\Models\BillingCycle;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected AuditService $auditService,
        protected EmailService $emailService
    ) {}

    public function create(Agent $agent, Plan $plan, ?float $customPrice = null): Subscription
    {
        $subscription = Subscription::create([
            'agent_id' => $agent->id,
            'company_id' => $agent->company_id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'custom_price' => $customPrice,
        ]);

        $this->auditService->log('subscription_created', $subscription);

        // Create invoice and payment link immediately
        $invoice = $this->invoiceService->createForSubscription($subscription);
        $paymentLink = $this->invoiceService->createPaymentLink($invoice, false, false);

        // Send one combined email with subscription info + payment link
        $this->emailService->sendSubscriptionCreated($subscription, $invoice, $paymentLink);

        return $subscription;
    }

    public function activate(Subscription $subscription): void
    {
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays(30);

        $subscription->update([
            'status' => 'active',
            'current_period_start' => $startDate,
            'current_period_end' => $endDate,
            'minutes_used' => 0,
            'circuit_breaker_triggered' => false,
            'circuit_breaker_triggered_at' => null,
            'activated_at' => now(),
            'expires_at' => $endDate->endOfDay(),
        ]);

        // Update the existing invoice billing period to match activation dates
        $latestInvoice = $subscription->invoices()->latest()->first();
        if ($latestInvoice) {
            $latestInvoice->update([
                'billing_period_start' => $startDate,
                'billing_period_end' => $endDate,
            ]);
        }

        $this->auditService->log('subscription_activated', $subscription);
    }

    public function renew(Subscription $subscription): void
    {
        $this->createBillingCycleSnapshot($subscription);

        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays(30);

        $subscription->update([
            'current_period_start' => $startDate,
            'current_period_end' => $endDate,
            'minutes_used' => 0,
            'circuit_breaker_triggered' => false,
            'circuit_breaker_triggered_at' => null,
            'expires_at' => $endDate->endOfDay(),
        ]);

        $invoice = $this->invoiceService->createForSubscription($subscription);

        $this->auditService->log('subscription_renewed', $subscription);

        $this->emailService->sendSubscriptionRenewal($subscription, $invoice);
    }

    /**
     * Check if subscription can be cancelled.
     *
     * @return array{can_cancel: bool, reason: string|null, has_paid_invoice: bool, unpaid_invoices: int}
     */
    public function canCancel(Subscription $subscription): array
    {
        // Check for paid invoices in current billing period
        $paidInvoice = $subscription->invoices()
            ->where('status', 'paid')
            ->where('billing_period_start', $subscription->current_period_start)
            ->first();

        if ($paidInvoice) {
            return [
                'can_cancel' => false,
                'reason' => 'Cannot cancel subscription with a paid invoice for the current billing period.',
                'has_paid_invoice' => true,
                'unpaid_invoices' => 0,
            ];
        }

        // Count unpaid invoices that will be voided
        $unpaidInvoices = $subscription->invoices()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->count();

        return [
            'can_cancel' => true,
            'reason' => null,
            'has_paid_invoice' => false,
            'unpaid_invoices' => $unpaidInvoices,
        ];
    }

    /**
     * Cancel a subscription with business logic safeguards.
     *
     * @throws SubscriptionHasPaidInvoiceException
     */
    public function cancel(Subscription $subscription, ?string $reason = null, bool $force = false): void
    {
        $cancelCheck = $this->canCancel($subscription);

        if (!$cancelCheck['can_cancel'] && !$force) {
            throw new SubscriptionHasPaidInvoiceException($cancelCheck['reason']);
        }

        // Void all unpaid invoices
        $unpaidInvoices = $subscription->invoices()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->get();

        foreach ($unpaidInvoices as $invoice) {
            $this->invoiceService->voidInvoice(
                $invoice,
                "Subscription cancelled" . ($reason ? ": {$reason}" : "")
            );
        }

        // Create billing cycle snapshot if subscription was active
        if ($subscription->status === 'active' && $subscription->current_period_start) {
            $this->createBillingCycleSnapshot($subscription);
        }

        $oldValues = $subscription->toArray();

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Deactivate the associated agent
        if ($subscription->agent && $subscription->agent->status === 'active') {
            $subscription->agent->update([
                'status' => 'inactive',
            ]);
            $this->auditService->log('agent_deactivated', $subscription->agent, ['reason' => 'Subscription cancelled']);
        }

        $this->auditService->log('subscription_cancelled', $subscription, $oldValues);

        $this->emailService->sendSubscriptionCancelled($subscription);
    }

    public function createBillingCycleSnapshot(Subscription $subscription): BillingCycle
    {
        $agent = $subscription->agent;
        $plan = $subscription->plan;

        $retellCost = $agent->callLogs()
            ->forPeriod($subscription->current_period_start, $subscription->current_period_end)
            ->sum('retell_cost');

        $totalCalls = $agent->callLogs()
            ->forPeriod($subscription->current_period_start, $subscription->current_period_end)
            ->count();

        $subscriptionAmount = $subscription->getEffectivePrice();
        $profit = $subscriptionAmount - $retellCost;
        $profitMargin = $subscriptionAmount > 0
            ? round(($profit / $subscriptionAmount) * 100, 2)
            : 0;

        return BillingCycle::create([
            'subscription_id' => $subscription->id,
            'agent_id' => $agent->id,
            'company_id' => $subscription->company_id,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'plan_name' => $plan->name,
            'subscription_amount' => $subscriptionAmount,
            'included_minutes' => $plan->included_minutes,
            'minutes_used' => $subscription->minutes_used,
            'total_calls' => $totalCalls,
            'retell_cost' => $retellCost,
            'profit' => $profit,
            'profit_margin' => $profitMargin,
        ]);
    }
}
