<?php

namespace App\Http\Controllers\Closer;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Same customer-facing stats view a client sees for their own assistant (call
 * volume, sentiment, transcripts) — deliberately NOT the admin view, which shows
 * FOXIQO's internal Retell cost/margin data (see agents/_show.blade.php's
 * $isAdmin-gated "Cost / Min" and per-call cost fields). A closer/manager
 * screen-sharing with a prospect must never have that in frame.
 */
class AgentController extends Controller
{
    public function show(Agent $agent, Request $request): View|JsonResponse
    {
        $this->authorizeAgent($agent);

        $agent->load(['subscription.plan', 'company']);

        $callLogs = $agent->callLogs()->latest('started_at')->limit(10)->get();

        if ($request->boolean('refresh')) {
            return response()->json([
                'rows_html' => view('customer.agents._recent_call_rows', compact('callLogs'))->render(),
            ]);
        }

        $totalCalls = $agent->callLogs()->count();
        $totalMinutes = $agent->callLogs()->sum('duration_minutes');
        $avgDuration = $totalCalls > 0 ? $agent->callLogs()->avg('duration_seconds') : 0;
        $inboundCalls = $agent->callLogs()->where('direction', 'inbound')->count();
        $outboundCalls = $agent->callLogs()->where('direction', 'outbound')->count();
        $upcomingAppointments = $agent->appointments()->upcoming()->orderBy('starts_at')->limit(10)->get();

        $viewData = [
            'isAdmin' => false,
            'recentCallRowsPartial' => 'customer.agents._recent_call_rows',
            // No dedicated calls index for this role — the last 10 shown inline are
            // enough for a demo; this just keeps the shared partial's link harmless.
            'callsIndexUrl' => route('closer.agents.show', $agent),
            'callVolumeUrl' => route('closer.agents.charts.call-volume', $agent),
            'sentimentUrl' => route('closer.agents.charts.sentiment', $agent),
            'companyUrl' => null,
            'subscriptionUrl' => null,
            'createSubscriptionUrl' => null,
        ];

        return view('closer.agents.show', compact(
            'agent', 'callLogs', 'totalCalls', 'totalMinutes', 'avgDuration',
            'inboundCalls', 'outboundCalls', 'upcomingAppointments'
        ) + $viewData);
    }

    public function chartCallVolume(Agent $agent, Request $request): JsonResponse
    {
        $this->authorizeAgent($agent);
        [$start, $end] = $this->resolveRange($request);

        $rows = CallLog::where('agent_id', $agent->id)
            ->whereBetween('started_at', [$start, $end])
            ->selectRaw('DATE(started_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $labels = [];
        $values = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $day = $cursor->toDateString();
            $labels[] = $cursor->format('M j');
            $values[] = (int) ($rows[$day] ?? 0);
            $cursor->addDay();
        }

        return response()->json(['labels' => $labels, 'values' => $values]);
    }

    public function chartSentiment(Agent $agent, Request $request): JsonResponse
    {
        $this->authorizeAgent($agent);
        [$start, $end] = $this->resolveRange($request);

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
            'neutral' => (int) ($row->neutral ?? 0),
            'negative' => (int) ($row->negative ?? 0),
        ]);
    }

    protected function authorizeAgent(Agent $agent): void
    {
        abort_unless(auth()->user()->canAccessAgent($agent), 403);
    }

    private function resolveRange(Request $request): array
    {
        $now = Carbon::now();
        $range = $request->input('range', 'last7');

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                Carbon::parse($request->input('from', $now->copy()->subDays(6)->toDateString()))->startOfDay(),
                Carbon::parse($request->input('to', $now->toDateString()))->endOfDay(),
            ],
            default => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
