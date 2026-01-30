<?php

namespace App\Services;

use App\Events\SubscriptionActivated;
use App\Models\Agent;
use App\Models\BillingCycle;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected AuditService $auditService
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

        $invoice = $this->invoiceService->createForSubscription($subscription);

        $this->auditService->log('subscription_activated', $subscription);

        event(new SubscriptionActivated($subscription, $invoice));
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

        $this->invoiceService->createForSubscription($subscription);

        $this->auditService->log('subscription_renewed', $subscription);
    }

    public function cancel(Subscription $subscription, ?string $reason = null): void
    {
        $this->createBillingCycleSnapshot($subscription);

        $oldValues = $subscription->toArray();

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->auditService->log('subscription_cancelled', $subscription, $oldValues);
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
