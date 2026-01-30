<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\BillingCycle;
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

        $revenue = $subscription ? $subscription->getEffectivePrice() : 0;
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
        $billingCycles = BillingCycle::whereBetween('period_start', [$start, $end])->get();

        $currentRevenue = Subscription::active()->get()->sum(fn($s) => $s->getEffectivePrice());

        return [
            'total_revenue' => $billingCycles->sum('subscription_amount'),
            'total_cost' => $billingCycles->sum('retell_cost'),
            'total_profit' => $billingCycles->sum('profit'),
            'average_margin' => round($billingCycles->avg('profit_margin') ?? 0, 2),
            'total_minutes' => $billingCycles->sum('minutes_used'),
            'total_calls' => $billingCycles->sum('total_calls'),
            'active_subscriptions' => Subscription::active()->count(),
            'active_companies' => Company::where('status', 'active')->count(),
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
