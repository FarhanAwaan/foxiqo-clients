<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallLog;
use App\Models\Company;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = Agent::with(['company', 'subscription.plan'])
            ->withCount('callLogs')
            ->withSum('callLogs', 'duration_minutes');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('retell_agent_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $agents = $query->latest()->paginate(12)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.agents.index', compact('agents', 'companies'));
    }

    public function create(): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return view('admin.agents.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'retell_agent_id' => ['required', 'string', 'max:100', 'unique:agents'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'agent_type' => ['required', 'string', 'in:inbound,outbound,both'],
            'cost_per_minute' => ['required', 'numeric', 'min:0'],
        ]);

        $agent = Agent::create($validated);

        $this->auditService->log('agent_created', $agent);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Agent created successfully.');
    }

    public function show(Agent $agent, Request $request): View|JsonResponse
    {
        $agent->load(['company', 'subscription.plan']);

        // Get latest 10 calls for the overview (full list available via agents.calls.index)
        $callLogs = $agent->callLogs()
            ->latest('started_at')
            ->limit(10)
            ->get();

        if ($request->boolean('refresh')) {
            return response()->json([
                'rows_html' => view('admin.agents._recent_call_rows', compact('callLogs'))->render(),
            ]);
        }

        // Calculate stats
        $totalCalls = $agent->callLogs()->count();
        $totalMinutes = $agent->callLogs()->sum('duration_minutes');
        $avgDuration = $totalCalls > 0 ? $agent->callLogs()->avg('duration_seconds') : 0;
        $inboundCalls = $agent->callLogs()->where('direction', 'inbound')->count();
        $outboundCalls = $agent->callLogs()->where('direction', 'outbound')->count();

        return view('admin.agents.show', compact('agent', 'callLogs', 'totalCalls', 'totalMinutes', 'avgDuration', 'inboundCalls', 'outboundCalls'));
    }

    public function edit(Agent $agent): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return view('admin.agents.edit', compact('agent', 'companies'));
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'retell_agent_id' => ['required', 'string', 'max:100', 'unique:agents,retell_agent_id,' . $agent->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'agent_type' => ['required', 'string', 'in:inbound,outbound,both'],
            'cost_per_minute' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,paused,archived'],
        ]);

        $oldValues = $agent->toArray();
        $agent->update($validated);

        $this->auditService->log('agent_updated', $agent, $oldValues);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        if ($agent->subscription) {
            return back()->with('error', 'Cannot delete agent with active subscription.');
        }

        $this->auditService->log('agent_deleted', $agent);

        $agent->delete();

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent deleted successfully.');
    }

    // ── AJAX: Call Volume for this agent ──────────────────────────────
    public function chartCallVolume(Agent $agent, Request $request): JsonResponse
    {
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
