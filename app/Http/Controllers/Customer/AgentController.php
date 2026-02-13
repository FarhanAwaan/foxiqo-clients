<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = auth()->user()->company->agents()
            ->with(['subscription.plan'])
            ->withCount('callLogs')
            ->withSum('callLogs', 'duration_minutes')
            ->paginate(12);

        return view('customer.agents.index', compact('agents'));
    }

    public function show(Agent $agent, Request $request): View
    {
        $this->authorizeAgent($agent);

        $agent->load(['subscription.plan']);

        // Get call logs with pagination
        $callLogs = $agent->callLogs()
            ->orderBy('started_at', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Calculate stats
        $totalCalls = $agent->callLogs()->count();
        $totalMinutes = $agent->callLogs()->sum('duration_minutes');
        $avgDuration = $totalCalls > 0 ? $agent->callLogs()->avg('duration_seconds') : 0;
        $inboundCalls = $agent->callLogs()->where('direction', 'inbound')->count();
        $outboundCalls = $agent->callLogs()->where('direction', 'outbound')->count();

        return view('customer.agents.show', compact('agent', 'callLogs', 'totalCalls', 'totalMinutes', 'avgDuration', 'inboundCalls', 'outboundCalls'));
    }

    protected function authorizeAgent(Agent $agent): void
    {
        if ($agent->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}
