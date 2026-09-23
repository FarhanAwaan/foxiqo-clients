<?php

namespace App\Services;

use App\Events\SubscriptionActivated;
use App\Exceptions\SubscriptionHasPaidInvoiceException;
use App\Exceptions\SubscriptionRenewalBlockedException;
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

    /**
     * @param array{provider: string, transaction_id: ?string}|null $alreadyPaid Pass this
     *   when the first period's payment was already collected outside this flow (e.g. a
     *   Paddle transaction created from a closer's deal, before this Agent existed). Creates
     *   the subscription active immediately and records the invoice as already paid instead
     *   of generating a fresh unpaid one.
     * @param ?string $paddleSubscriptionId Set when this subscription originated from a Paddle
     *   deal (trial or already-paid) so the renewal webhook can reconcile future Paddle
     *   charges against it — see PaddleWebhookController::handleSubscriptionRenewalPayment().
     */
    public function create(Agent $agent, Plan $plan, ?float $customPrice = null, bool $isTrial = false, int $trialDays = 30, ?array $alreadyPaid = null, ?string $paddleSubscriptionId = null): Subscription
    {
        if ($alreadyPaid) {
            $startDate = Carbon::today();
            $endDate = $startDate->copy()->addDays(30);

            $subscription = Subscription::create([
                'agent_id'              => $agent->id,
                'company_id'            => $agent->company_id,
                'plan_id'               => $plan->id,
                'status'                => 'active',
                'custom_price'          => $customPrice,
                'paddle_subscription_id' => $paddleSubscriptionId,
                'current_period_start'  => $startDate,
                'current_period_end'    => $endDate,
                'minutes_used'          => 0,
                'activated_at'          => now(),
                'expires_at'            => $endDate->endOfDay(),
            ]);

            $this->auditService->log('subscription_created', $subscription);

            $invoice = $this->invoiceService->createForSubscription($subscription);

            if (!empty($alreadyPaid['transaction_id'])) {
                $invoice->update(['paddle_transaction_id' => $alreadyPaid['transaction_id']]);
            }

            $this->invoiceService->markAsPaid(
                $invoice,
                $alreadyPaid['provider'] ?? 'paddle',
                $alreadyPaid['transaction_id'] ?? null
            );

            $this->emailService->sendSubscriptionActivated($subscription, $invoice);

            return $subscription;
        }

        if ($isTrial) {
            $startDate = Carbon::today();
            $trialEndsAt = $startDate->copy()->addDays($trialDays);

            $subscription = Subscription::create([
                'agent_id'              => $agent->id,
                'company_id'            => $agent->company_id,
                'plan_id'               => $plan->id,
                'status'                => 'active',
                'custom_price'          => $customPrice,
                'paddle_subscription_id' => $paddleSubscriptionId,
                'is_trial'              => true,
                'trial_days'            => $trialDays,
                'trial_ends_at'         => $trialEndsAt,
                'trial_ending_warned'   => false,
                'current_period_start'  => $startDate,
                'current_period_end'    => $trialEndsAt->toDateString(),
                'minutes_used'          => 0,
                'activated_at'          => now(),
                'expires_at'            => $trialEndsAt->endOfDay(),
            ]);

            $this->auditService->log('subscription_trial_started', $subscription);
            $this->emailService->sendTrialStarted($subscription);

            return $subscription;
        }

        $subscription = Subscription::create([
            'agent_id'    => $agent->id,
            'company_id'  => $agent->company_id,
            'plan_id'     => $plan->id,
            'status'      => 'pending',
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

    /**
     * Convert an expired trial into a paid subscription.
     * Creates the first invoice, sends payment link email, removes trial flag.
     */
    public function expireTrial(Subscription $subscription): void
    {
        $this->createBillingCycleSnapshot($subscription);

        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays(30);

        $subscription->update([
            'is_trial'              => false,
            'trial_ends_at'         => null,
            'current_period_start'  => $startDate,
            'current_period_end'    => $endDate,
            'minutes_used'          => 0,
            'circuit_breaker_triggered' => false,
            'circuit_breaker_triggered_at' => null,
            'expires_at'            => $endDate->endOfDay(),
        ]);

        $invoice = $this->invoiceService->createForSubscription($subscription);
        $paymentLink = $this->invoiceService->createPaymentLink($invoice, false, false);

        $this->auditService->log('subscription_trial_expired', $subscription);
        $this->emailService->sendTrialExpired($subscription, $invoice, $paymentLink);
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

    /**
     * @throws SubscriptionRenewalBlockedException if the current period's invoice hasn't been paid
     */
    public function renew(Subscription $subscription): void
    {
        if (!$this->currentPeriodIsPaid($subscription)) {
            throw new SubscriptionRenewalBlockedException(
                "Subscription {$subscription->uuid} cannot be renewed — the invoice for the current billing period has not been paid."
            );
        }

        $this->createBillingCycleSnapshot($subscription);

        // Capture the just-closed period before it's overwritten below — this is
        // what the usage invoice bills for, in arrears, now that minutes are known.
        $closedPeriodStart = $subscription->current_period_start;
        $closedPeriodEnd = $subscription->current_period_end;
        $closedMinutesUsed = $subscription->minutes_used;

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

        $this->billUsageForClosedPeriod($subscription, $closedPeriodStart, $closedPeriodEnd, $closedMinutesUsed);
    }

    /**
     * Bill the metered usage for a period that just closed, if this plan has a
     * per-minute rate configured and any minutes were actually used. Skipped
     * entirely for plans with no rate set, so flat-fee-only subscriptions are
     * completely unaffected.
     */
    protected function billUsageForClosedPeriod(Subscription $subscription, $periodStart, $periodEnd, int $minutesUsed): void
    {
        $rate = (float) ($subscription->plan->per_minute_rate ?? 0);

        if ($rate <= 0 || $minutesUsed <= 0) {
            return;
        }

        $usageInvoice = $this->invoiceService->createUsageInvoice($subscription, $periodStart, $periodEnd, $minutesUsed);
        $this->invoiceService->createPaymentLink($usageInvoice);
    }

    /**
     * Whether the invoice for the subscription's current billing period has been paid.
     * Renewal must never proceed while this is false.
     */
    public function currentPeriodIsPaid(Subscription $subscription): bool
    {
        return $subscription->invoices()
            ->where('status', 'paid')
            ->where('billing_period_start', $subscription->current_period_start)
            ->exists();
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
