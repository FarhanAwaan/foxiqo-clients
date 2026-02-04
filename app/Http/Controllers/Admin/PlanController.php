<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PlanController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = Plan::with('company')->withCount('subscriptions');

        if ($request->filled('type')) {
            if ($request->type === 'standard') {
                $query->where('is_custom', false);
            } elseif ($request->type === 'custom') {
                $query->where('is_custom', true);
            }
        }

        $plans = $query->latest()->paginate(15)->withQueryString();

        return view('admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return view('admin.plans.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'included_minutes' => ['required', 'integer', 'min:0'],
            'overage_rate' => ['nullable', 'numeric', 'min:0'],
            'is_custom' => ['boolean'],
            'company_id' => ['nullable', 'required_if:is_custom,1', 'exists:companies,id'],
        ]);

        $validated['is_custom'] = $request->boolean('is_custom');

        if (!$validated['is_custom']) {
            $validated['company_id'] = null;
        }

        $plan = Plan::create($validated);

        $this->auditService->log('plan_created', $plan);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function show(Plan $plan): View
    {
        $plan->load(['company', 'subscriptions.agent']);

        return view('admin.plans.show', compact('plan'));
    }

    public function edit(Plan $plan): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return view('admin.plans.edit', compact('plan', 'companies'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'included_minutes' => ['required', 'integer', 'min:0'],
            'overage_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $plan->toArray();
        $plan->update($validated);

        $this->auditService->log('plan_updated', $plan, $oldValues);

        return redirect()->route('admin.plans.show', $plan)
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscriptions.');
        }

        $this->auditService->log('plan_deleted', $plan);

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
