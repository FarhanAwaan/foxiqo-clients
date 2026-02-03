<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        $agents = $company->agents()->with(['subscription.plan'])->get();

        $totalMinutesUsed = $agents->sum(fn ($agent) => $agent->subscription?->minutes_used ?? 0);
        $totalMinutesIncluded = $agents->sum(fn ($agent) => $agent->subscription?->plan?->included_minutes ?? 0);

        $recentCalls = CallLog::whereIn('agent_id', $agents->pluck('id'))
            ->with('agent')
            ->latest()
            ->take(10)
            ->get();

        $activeSubscriptions = $agents->filter(fn ($agent) => $agent->subscription?->status === 'active')->count();

        return view('customer.dashboard.index', [
            'company' => $company,
            'agents' => $agents,
            'totalMinutesUsed' => $totalMinutesUsed,
            'totalMinutesIncluded' => $totalMinutesIncluded,
            'recentCalls' => $recentCalls,
            'activeSubscriptions' => $activeSubscriptions,
        ]);
    }
}
