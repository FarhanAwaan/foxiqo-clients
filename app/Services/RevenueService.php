<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;

class RevenueService
{
    public function getAgentStats(Agent $agent, Carbon $start, Carbon $end): array
    {
        $subscription = $agent->subscription;

        $retellCost = $agent->callLogs()
            ->forPeriod($start, $end)
            ->sum('retell_cost');

        $totalCalls = $agent->callLogs()
            ->forPeriod($start, $end)
            ->count();

        $totalMinutes = $agent->callLogs()
            ->forPeriod($start, $end)
            ->sum('duration_minutes');

        // Sums actual invoiced amounts for periods starting in this window, rather
        // than reading the subscription's current price — getEffectivePrice()
        // reflects today's plan/custom_price, so using it for a past period would
        // misreport revenue whenever the price changed since then. Invoices already
        // snapshot the price that was actually in effect at billing time.
        $revenue = $subscription
            ? $subscription->invoices()
                ->whereBetween('billing_period_start', [$start, $end])
                ->sum('amount')
            : 0;
        $profit = $revenue - $retellCost;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

        return [
            'revenue' => $revenue,
            'retell_cost' => $retellCost,
            'profit' => $profit,
            'margin' => $margin,
            'total_calls' => $totalCalls,
            'total_minutes' => round($totalMinutes, 2),
            'minutes_used' => $subscription?->minutes_used ?? 0,
            'minutes_included' => $subscription?->plan?->included_minutes ?? 0,
        ];
    }

    public function getCompanyStats(Company $company, Carbon $start, Carbon $end): array
    {
        $totals = [
            'revenue' => 0,
            'retell_cost' => 0,
            'profit' => 0,
            'total_calls' => 0,
            'total_minutes' => 0,
            'agents' => [],
        ];

        foreach ($company->agents as $agent) {
            $stats = $this->getAgentStats($agent, $start, $end);
            $totals['revenue'] += $stats['revenue'];
            $totals['retell_cost'] += $stats['retell_cost'];
            $totals['profit'] += $stats['profit'];
            $totals['total_calls'] += $stats['total_calls'];
            $totals['total_minutes'] += $stats['total_minutes'];
            $totals['agents'][$agent->id] = $stats;
        }

        $totals['margin'] = $totals['revenue'] > 0
            ? round(($totals['profit'] / $totals['revenue']) * 100, 2)
            : 0;

        return $totals;
    }

    public function getSystemStats(Carbon $start, Carbon $end): array
    {
        // Aggregates the same live call/subscription data getCompanyStats() uses per
        // company, rather than BillingCycle snapshots — those are only written at
        // subscription lifecycle events (create/renew/cancel), so a company sitting
        // mid-cycle (the normal state most of the time) would contribute nothing to
        // a snapshot-based total for the period, even though it has real activity.
        // This also keeps these totals reconcilable with the per-company breakdown
        // table shown alongside them on the revenue page.
        $companies = Company::where('status', 'active')->get();

        $totals = [
            'revenue' => 0,
            'cost' => 0,
            'profit' => 0,
            'total_minutes' => 0,
            'total_calls' => 0,
        ];

        foreach ($companies as $company) {
            $stats = $this->getCompanyStats($company, $start, $end);
            $totals['revenue'] += $stats['revenue'];
            $totals['cost'] += $stats['retell_cost'];
            $totals['profit'] += $stats['profit'];
            $totals['total_minutes'] += $stats['total_minutes'];
            $totals['total_calls'] += $stats['total_calls'];
        }

        $currentRevenue = Subscription::active()->get()->sum(fn($s) => $s->getEffectivePrice());

        return [
            'revenue' => $totals['revenue'],
            'cost' => $totals['cost'],
            'profit' => $totals['profit'],
            'margin' => $totals['revenue'] > 0 ? round(($totals['profit'] / $totals['revenue']) * 100, 2) : 0,
            'total_minutes' => round($totals['total_minutes'], 2),
            'total_calls' => $totals['total_calls'],
            'active_subscriptions' => Subscription::active()->count(),
            'active_companies' => $companies->count(),
            'current_mrr' => $currentRevenue,
            'pending_payments' => Invoice::unpaid()->sum('amount'),
        ];
    }

    public function getDashboardStats(): array
    {
        $now = Carbon::now();

        return [
            'this_month' => $this->getSystemStats($now->copy()->startOfMonth(), $now),
            'last_month' => $this->getSystemStats(
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ),
        ];
    }
}
