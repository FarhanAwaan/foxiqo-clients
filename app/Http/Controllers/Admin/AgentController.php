<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Company;
use App\Services\AuditService;
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
        $query = Agent::with(['company', 'subscription.plan']);

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

        $agents = $query->latest()->paginate(15)->withQueryString();
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
            'cost_per_minute' => ['required', 'numeric', 'min:0'],
        ]);

        $agent = Agent::create($validated);

        $this->auditService->log('agent_created', $agent);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Agent created successfully.');
    }

    public function show(Agent $agent): View
    {
        $agent->load(['company', 'subscription.plan', 'callLogs' => function ($q) {
            $q->latest()->take(10);
        }]);

        return view('admin.agents.show', compact('agent'));
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
}
