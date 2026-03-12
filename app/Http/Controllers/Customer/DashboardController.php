<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $company  = auth()->user()->company;
        $agents   = $company->agents()->with(['subscription.plan'])->get();

        $totalMinutesUsed     = $agents->sum(fn ($a) => $a->subscription?->minutes_used ?? 0);
        $totalMinutesIncluded = $agents->sum(fn ($a) => $a->subscription?->plan?->included_minutes ?? 0);
        $activeSubscriptions  = $agents->filter(fn ($a) => $a->subscription?->status === 'active')->count();

        $recentCalls = CallLog::whereIn('agent_id', $agents->pluck('id'))
            ->with('agent')
            ->latest()
            ->take(10)
            ->get();

        return view('customer.dashboard.index', [
            'company'              => $company,
            'agents'               => $agents,
            'totalMinutesUsed'     => $totalMinutesUsed,
            'totalMinutesIncluded' => $totalMinutesIncluded,
            'activeSubscriptions'  => $activeSubscriptions,
            'recentCalls'          => $recentCalls,
        ]);
    }
}
