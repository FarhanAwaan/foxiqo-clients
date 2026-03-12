<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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

    public function show(Agent $agent, Request $request): View|JsonResponse
    {
        $this->authorizeAgent($agent);

        $agent->load(['subscription.plan']);

        // Get latest 10 calls for the overview (full list available via calls.index)
        $callLogs = $agent->callLogs()
            ->latest('started_at')
            ->limit(10)
            ->get();

        if ($request->boolean('refresh')) {
            return response()->json([
                'rows_html' => view('customer.agents._recent_call_rows', compact('callLogs'))->render(),
            ]);
        }

        // Calculate stats
        $totalCalls = $agent->callLogs()->count();
        $totalMinutes = $agent->callLogs()->sum('duration_minutes');
        $avgDuration = $totalCalls > 0 ? $agent->callLogs()->avg('duration_seconds') : 0;
        $inboundCalls = $agent->callLogs()->where('direction', 'inbound')->count();
        $outboundCalls = $agent->callLogs()->where('direction', 'outbound')->count();

        return view('customer.agents.show', compact('agent', 'callLogs', 'totalCalls', 'totalMinutes', 'avgDuration', 'inboundCalls', 'outboundCalls'));
    }

    // ── AJAX: Call Volume for this agent ──────────────────────────────
    public function chartCallVolume(Agent $agent, Request $request): JsonResponse
    {
        $this->authorizeAgent($agent);
        [$start, $end] = $this->_resolveRange($request);

        $rows = CallLog::where('agent_id', $agent->id)
            ->whereBetween('started_at', [$start, $end])
            ->selectRaw("DATE(started_at) as day, COUNT(*) as cnt")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $labels = [];
        $values = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $day      = $cursor->toDateString();
            $labels[] = $cursor->format('M j');
            $values[] = (int) ($rows[$day] ?? 0);
            $cursor->addDay();
        }

        return response()->json(['labels' => $labels, 'values' => $values]);
    }

    // ── AJAX: Sentiment for this agent ────────────────────────────────
    public function chartSentiment(Agent $agent, Request $request): JsonResponse
    {
        $this->authorizeAgent($agent);
        [$start, $end] = $this->_resolveRange($request);

        $row = CallLog::where('agent_id', $agent->id)
            ->whereBetween('started_at', [$start, $end])
            ->whereNotNull('sentiment')
            ->selectRaw("
                SUM(sentiment = 'positive') as positive,
                SUM(sentiment = 'neutral')  as neutral,
                SUM(sentiment = 'negative') as negative
            ")
            ->first();

        return response()->json([
            'positive' => (int) ($row->positive ?? 0),
            'neutral'  => (int) ($row->neutral  ?? 0),
            'negative' => (int) ($row->negative  ?? 0),
        ]);
    }

    protected function authorizeAgent(Agent $agent): void
    {
        if ($agent->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }

    private function _resolveRange(Request $request): array
    {
        $now   = Carbon::now();
        $range = $request->input('range', 'last7');

        return match ($range) {
            'today'     => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last30'    => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'custom'    => [
                Carbon::parse($request->input('from', $now->copy()->subDays(6)->toDateString()))->startOfDay(),
                Carbon::parse($request->input('to', $now->toDateString()))->endOfDay(),
            ],
            default     => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
