<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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

        return view('customer.agents.show', compact('agent', 'callLogs', 'totalCalls', 'totalMinutes', 'avgDuration'));
    }

    public function callDetails(Agent $agent, CallLog $callLog): JsonResponse
    {
        $this->authorizeAgent($agent);

        if ($callLog->agent_id !== $agent->id) {
            return response()->json(['error' => 'Call not found'], 404);
        }

        return response()->json([
            'id' => $callLog->id,
            'uuid' => $callLog->uuid,
            'call_status' => $callLog->call_status,
            'direction' => $callLog->direction,
            'from_number' => $callLog->from_number,
            'to_number' => $callLog->to_number,
            'started_at' => $callLog->started_at?->format('M d, Y h:i A'),
            'ended_at' => $callLog->ended_at?->format('M d, Y h:i A'),
            'duration_formatted' => $callLog->duration_formatted,
            'duration_seconds' => $callLog->duration_seconds,
            'duration_minutes' => $callLog->duration_minutes,
            'retell_cost' => $callLog->retell_cost,
            'sentiment' => $callLog->sentiment,
            'summary' => $callLog->summary,
            'transcript' => $callLog->transcript_array,
            'recording_url' => $callLog->recording_url,
        ]);
    }

    protected function authorizeAgent(Agent $agent): void
    {
        if ($agent->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}
