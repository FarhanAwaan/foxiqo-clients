<?php

namespace App\Services;

use App\Events\SubscriptionActivated;
use App\Exceptions\SubscriptionHasPaidInvoiceException;
use App\Exceptions\SubscriptionRenewalBlockedException;
use App\Models\Agent;
use App\Models\BillingCycle;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\BillingTime;
use App\Support\PaddleMoney;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected AuditService $auditService,
        protected EmailService $emailService
    ) {}

    /**
     * A subscription this app bills itself (Nsave / manual / internal payment page).
     * Subscriptions funded by a Paddle deal go through createFromDeal() instead — Paddle
     * owns their billing dates.
     */
    public function create(Agent $agent, Plan $plan, ?float $customPrice = null, bool $isTrial = false, int $trialDays = 30): Subscription
    {
        if ($isTrial) {
            $startDate = Carbon::today();
            $trialEndsAt = $startDate->copy()->addDays($trialDays);

            $subscription = Subscription::create([
                'agent_id'              => $agent->id,
                'company_id'            => $agent->company_id,
                'plan_id'               => $plan->id,
                'status'                => 'active',
                'custom_price'          => $customPrice,
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
     * Create the portal Subscription for an assistant whose billing a Paddle deal already
     * set up. The dates come from Paddle (mirrored on the deal), never from "today + trial
     * days" — the trial clock started at checkout, which may be days before the assistant
     * exists here, and the portal must agree with the day Paddle actually charges.
     *
     *  - Trialing → a trial subscription ending on Paddle's first-charge date. No client
     *    email: the deal-paid email already told them, and the generic trial-started email
     *    promises an invoice where Paddle in fact charges the card automatically.
     *  - Already billing (a non-trial deal, or a trial that ended before the assistant was
     *    attached) → an active subscription on Paddle's current period, with the retainer
     *    invoice(s) Paddle already collected adopted onto it.
     *
     * @throws \InvalidArgumentException when the deal's Paddle subscription is canceled
     */
    public function createFromDeal(Agent $agent, Plan $plan, Deal $deal): Subscription
    {
        if ($deal->paddle_status === 'canceled') {
            throw new \InvalidArgumentException("The Paddle subscription for deal \"{$deal->business_name}\" is canceled, so it can't fund a new assistant.");
        }

        $price = (float) $deal->agreed_monthly_price;
        $trialing = $deal->paddle_status === 'trialing' || ($deal->paddle_status === null && $deal->is_trial);

        $periodStart = $deal->paddle_period_start ?? now();

        if ($trialing) {
            $trialEndsAt = $deal->trial_ends_at ?? Carbon::today()->addDays($deal->trial_days ?? 30)->endOfDay();

            $subscription = Subscription::create([
                'agent_id'               => $agent->id,
                'company_id'             => $agent->company_id,
                'plan_id'                => $plan->id,
                'status'                 => 'active',
                'custom_price'           => $price,
                'paddle_subscription_id' => $deal->paddle_subscription_id,
                'is_trial'               => true,
                'trial_days'             => $deal->trial_days,
                'trial_ends_at'          => $trialEndsAt,
                'trial_ending_warned'    => false,
                'current_period_start'   => BillingTime::businessDay($periodStart),
                'current_period_end'     => BillingTime::businessDay($trialEndsAt),
                'minutes_used'           => 0,
                'activated_at'           => now(),
                'expires_at'             => $trialEndsAt,
            ]);

            $this->auditService->log('subscription_trial_started', $subscription);
        } else {
            $periodEnd = $deal->paddle_period_end ?? $periodStart->copy()->addMonthNoOverflow();

            $subscription = Subscription::create([
                'agent_id'               => $agent->id,
                'company_id'             => $agent->company_id,
                'plan_id'                => $plan->id,
                'status'                 => 'active',
                'custom_price'           => $price,
                'paddle_subscription_id' => $deal->paddle_subscription_id,
                'current_period_start'   => BillingTime::businessDay($periodStart),
                'current_period_end'     => BillingTime::businessDay($periodEnd),
                'minutes_used'           => 0,
                'activated_at'           => now(),
                'expires_at'             => BillingTime::endOfBusinessDay($periodEnd),
            ]);

            $this->auditService->log('subscription_created', $subscription);

            // Retainer charges Paddle already collected before this assistant existed — the first month
            // of a non-trial deal, or a trial that converted first — were recorded against the deal
            // (deal_id, no subscription) at the time. Adopt them.
            $adopted = Invoice::where('deal_id', $deal->id)
                ->where('invoice_type', 'subscription')
                ->whereNull('subscription_id')
                ->update(['subscription_id' => $subscription->id]);

            // Legacy: a non-trial deal paid before its first month was recorded at payment time has no
            // such invoice, so create it now (on the current period) rather than leave it unrecorded.
            if ($adopted === 0 && !$deal->is_trial && !Invoice::where('subscription_id', $subscription->id)->exists()) {
                $legacy = $this->invoiceService->createForSubscription($subscription);
                $legacy->update(['deal_id' => $deal->id, 'paddle_transaction_id' => $deal->paddle_transaction_id]);
                $this->invoiceService->markAsPaid($legacy, 'paddle', $deal->paddle_transaction_id, fireEvent: false);
            }

            // Their assistant is now live and billing — the client-facing milestone of this step.
            if ($latest = $subscription->invoices()->latest('id')->first()) {
                $this->emailService->sendSubscriptionActivated($subscription, $latest);
            }
        }

        $deal->update(['subscription_id' => $subscription->id]);

        return $subscription;
    }

    /**
     * Paddle just charged this subscription's retainer for [$periodStart, $periodEnd].
     *
     * For a Paddle-managed subscription Paddle — not this app's renewal cron — decides when
     * a period rolls, so this is its equivalent of renew() / expireTrial(): snapshot the
     * period that just closed, move the subscription onto Paddle's new period, and record
     * that period's invoice as already paid. Metered usage for the closed period is billed
     * as its own invoice, except after a trial (trial minutes are free).
     *
     * An unpaid retainer invoice already open on the subscription is settled rather than
     * duplicated. Idempotency is the caller's job (PaddleLifecycleService checks the
     * transaction id first).
     *
     * Only ever moves the subscription FORWARD (paddlePeriodAdvances): a charge for the period it is
     * already in, or an earlier one — delivered late, or found by the poll after a newer charge was
     * recorded — is still recorded as a paid invoice, but the period is not rolled back, this period's
     * minutes are not reset and usage is not billed for a period that isn't closing.
     *
     * Period dates are business-timezone days (BillingTime::businessDay), so they match the dates
     * customers are shown.
     */
    public function recordPaddleCharge(Subscription $subscription, string $transactionId, ?string $paddleStatus, Carbon $periodStart, Carbon $periodEnd, ?array $paddleTotals = null): Invoice
    {
        $start = BillingTime::businessDay($periodStart);
        $end = BillingTime::businessDay($periodEnd);

        $advances = $this->paddlePeriodAdvances($subscription, $periodStart);

        $wasTrial = (bool) $subscription->is_trial;
        $closedStart = $subscription->current_period_start;
        $closedEnd = $subscription->current_period_end;
        $closedMinutes = (int) $subscription->minutes_used;

        if ($advances) {
            if ($closedStart && $closedEnd) {
                $this->createBillingCycleSnapshot($subscription);
            }

            $subscription->update([
                'status'                       => 'active',
                'is_trial'                     => false,
                'trial_ends_at'                => null,
                'trial_ending_warned'          => false,
                'current_period_start'         => $start,
                'current_period_end'           => $end,
                'minutes_used'                 => 0,
                'circuit_breaker_triggered'    => false,
                'circuit_breaker_triggered_at' => null,
                'expires_at'                   => BillingTime::endOfBusinessDay($periodEnd),
            ]);
        }

        // On a Paddle subscription an unpaid retainer invoice can only be a leftover from before it was
        // linked (the renewal cron no longer touches it), and it stands for this very charge — so it is
        // settled, and re-dated to the charge's own period, instead of leaving it open beside a second one.
        $invoice = $subscription->invoices()
            ->where('invoice_type', 'subscription')
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->oldest('billing_period_start')
            ->first();

        if ($invoice) {
            $invoice->update([
                'billing_period_start' => $start,
                'billing_period_end'   => $end,
            ]);
        } else {
            $invoice = $this->invoiceService->createForSubscription($subscription, $start, $end);
        }

        $invoice->update([
            'paddle_transaction_id' => $transactionId,
            'paddle_status'         => $paddleStatus,
        ]);

        if ($paddleTotals) {
            $this->invoiceService->recordPaddleTotals($invoice, $paddleTotals);
        }

        // Paddle emails its own receipt for every charge; the portal's "Payment Confirmed" would duplicate it.
        $this->invoiceService->markAsPaid($invoice, 'paddle', $transactionId, fireEvent: false);

        // Only ever the RECORD, never the payment link or an automatic Paddle charge — this
        // subscription is Paddle-managed by construction here, so PaddleLifecycleService (which owns
        // Paddle API calls; SubscriptionService deliberately doesn't depend on it) decides how the
        // amount actually gets collected, right after this call returns.
        if ($advances && !$wasTrial && $closedStart && $closedEnd) {
            $this->createUsageInvoiceForClosedPeriod($subscription, $closedStart, $closedEnd, $closedMinutes);
        }

        return $invoice;
    }

    /**
     * Whether a Paddle charge whose billing period starts at $periodStart moves this subscription onto
     * a NEWER period. False for the period it is already in or an earlier one — a charge delivered
     * late, or one the poll finds after a newer charge was already recorded. Compared as
     * business-timezone days, the way the period is stored.
     */
    public function paddlePeriodAdvances(Subscription $subscription, Carbon $periodStart): bool
    {
        // A trial has no paid period yet, so nothing can be "earlier": its first charge always converts it,
        // even when "Start billing now" lands on the very day the trial began.
        if ($subscription->is_trial) {
            return true;
        }

        $current = $subscription->current_period_start;

        return $current === null || BillingTime::businessDay($periodStart)->gt($current);
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
     * Bill the metered usage for a period that just closed, if this plan has a per-minute rate
     * configured and any minutes were actually used, THEN send the customer a payment link for it.
     * For a subscription this app bills itself (Nsave/manual — never Paddle-managed, see
     * dueForInternalRenewal()): the only way that amount gets collected. Used by renew().
     */
    public function billUsageForClosedPeriod(Subscription $subscription, $periodStart, $periodEnd, int $minutesUsed): void
    {
        $usageInvoice = $this->createUsageInvoiceForClosedPeriod($subscription, $periodStart, $periodEnd, $minutesUsed);

        if ($usageInvoice) {
            $this->invoiceService->createPaymentLink($usageInvoice);
        }
    }

    /**
     * Just the record — no payment link, no attempt to collect it. Skipped entirely for plans with
     * no rate set or a period with no usage, so flat-fee-only subscriptions are unaffected.
     *
     * Paddle-managed subscriptions use this directly (from recordPaddleCharge()) rather than
     * billUsageForClosedPeriod(): PaddleLifecycleService — which owns every Paddle API call —
     * decides how THIS amount actually gets collected (an automatic one-time charge to the card
     * already on file), right after recordPaddleCharge() returns.
     */
    public function createUsageInvoiceForClosedPeriod(Subscription $subscription, $periodStart, $periodEnd, int $minutesUsed): ?Invoice
    {
        $rate = (float) ($subscription->plan->per_minute_rate ?? 0);

        if ($rate <= 0 || $minutesUsed <= 0) {
            return null;
        }

        return $this->invoiceService->createUsageInvoice($subscription, $periodStart, $periodEnd, $minutesUsed);
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
     * All the database work happens in one transaction: a failure part-way (this used to leave a
     * cancelled subscription whose assistant was never deactivated and whose customer was never
     * emailed) now rolls back cleanly instead. The email goes out afterwards.
     *
     * @throws SubscriptionHasPaidInvoiceException
     */
    public function cancel(Subscription $subscription, ?string $reason = null, bool $force = false): void
    {
        $cancelCheck = $this->canCancel($subscription);

        if (!$cancelCheck['can_cancel'] && !$force) {
            throw new SubscriptionHasPaidInvoiceException($cancelCheck['reason']);
        }

        DB::transaction(function () use ($subscription, $reason) {
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

            // Take the assistant out of service. `paused` (not `inactive` — that isn't a value of
            // agents.status, and MySQL rejected it with "Data truncated") because it's reversible:
            // if the customer comes back, set it Active again from the assistant's edit page.
            if ($subscription->agent && $subscription->agent->status === 'active') {
                $subscription->agent->update([
                    'status' => 'paused',
                ]);
                $this->auditService->log('agent_deactivated', $subscription->agent, ['reason' => 'Subscription cancelled']);
            }

            $this->auditService->log('subscription_cancelled', $subscription, $oldValues);
        });

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
