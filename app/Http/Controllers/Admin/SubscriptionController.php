<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): View
    {
        $query = Subscription::with(['agent', 'company', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.subscriptions.index', compact('subscriptions', 'companies'));
    }

    public function create(Request $request): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $plans = Plan::active()->orderBy('name')->get();

        // Get agents without subscriptions
        $agents = Agent::whereDoesntHave('subscription')
            ->where('status', 'active');

        if ($request->filled('company_id')) {
            $agents->where('company_id', $request->company_id);
        }

        $agents = $agents->get();

        return view('admin.subscriptions.create', compact('companies', 'plans', 'agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => ['required', 'exists:agents,id', 'unique:subscriptions,agent_id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);
        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription = $this->subscriptionService->create(
            $agent,
            $plan,
            $validated['custom_price'] ?? null
        );

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription created. Click Activate to start the billing period.');
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['agent', 'company', 'plan', 'invoices' => function ($q) {
            $q->latest()->take(5);
        }, 'billingCycles' => function ($q) {
            $q->latest()->take(5);
        }]);

        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function edit(Subscription $subscription): View
    {
        $plans = Plan::active()->orderBy('name')->get();

        return view('admin.subscriptions.edit', compact('subscription', 'plans'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $subscription->update($validated);

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        if ($subscription->status === 'active') {
            return back()->with('error', 'Cannot delete active subscription. Cancel it first.');
        }

        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    public function activate(Subscription $subscription): RedirectResponse
    {
        if ($subscription->status !== 'pending') {
            return back()->with('error', 'Only pending subscriptions can be activated.');
        }

        $this->subscriptionService->activate($subscription);

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription activated. Invoice has been created.');
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->status === 'cancelled') {
            return back()->with('error', 'Subscription is already cancelled.');
        }

        $reason = $request->input('reason');

        $this->subscriptionService->cancel($subscription, $reason);

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription cancelled successfully.');
    }
}
