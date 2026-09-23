<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\SubscriptionHasPaidInvoiceException;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Deal;
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
        $agents = Agent::with(['company', 'deal'])
            ->whereDoesntHave('subscription')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.subscriptions.create', compact('companies', 'plans', 'agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id'    => ['required', 'exists:agents,id', 'unique:subscriptions,agent_id'],
            'plan_id'     => ['required', 'exists:plans,id'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'is_trial'    => ['nullable', 'boolean'],
            'trial_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);
        $plan = Plan::findOrFail($validated['plan_id']);
        $isTrial = !empty($validated['is_trial']);
        $trialDays = (int) ($validated['trial_days'] ?? 30);
        $customPrice = $validated['custom_price'] ?? null;
        $alreadyPaid = null;
        $paddleSubscriptionId = null;

        // A Paddle deal already committed to specific terms for this customer — the
        // portal's invoicing MUST match what Paddle actually charges, so this
        // overrides whatever was submitted rather than trusting it. Matched via the
        // Agent's own deal_id (set when it was created from a Deal's "Add Assistant"
        // link), not by company alone — a company can have more than one unclaimed
        // deal at once, which company-wide matching can't disambiguate.
        $deal = $agent->deal && !$agent->deal->isClaimed() ? $agent->deal : null;

        if ($deal) {
            $customPrice = (float) $deal->agreed_monthly_price;
            $isTrial = $deal->is_trial;
            $trialDays = $deal->trial_days ?? 30;
            $paddleSubscriptionId = $deal->paddle_subscription_id;

            // Non-trial: Paddle already collected the first month + activation at
            // checkout. Trial: only activation was collected — Paddle's own trial
            // clock (not this app's) governs when the first real charge happens,
            // reconciled later by the renewal webhook, so no invoice is created now.
            if (!$isTrial) {
                $alreadyPaid = ['provider' => 'paddle', 'transaction_id' => $deal->paddle_transaction_id];
            }
        }

        $subscription = $this->subscriptionService->create(
            $agent,
            $plan,
            $customPrice,
            $isTrial,
            $trialDays,
            $alreadyPaid,
            $paddleSubscriptionId
        );

        if ($deal) {
            $deal->update(['subscription_id' => $subscription->id]);
        }

        $message = match (true) {
            $deal && $isTrial => "Subscription created from Deal \"{$deal->business_name}\" — {$trialDays}-day Paddle trial, \${$customPrice}/mo after. Activation was already collected; the monthly charge and this subscription's invoice will reconcile automatically when Paddle bills it.",
            $deal => "Subscription created from Deal \"{$deal->business_name}\" — \${$customPrice}/mo, already paid via Paddle. No further action needed for this period.",
            $isTrial => "Free trial started. The assistant is now active for {$trialDays} days. A trial welcome email has been sent.",
            default => 'Subscription created. Invoice and payment link have been sent to the customer.',
        };

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', $message);
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['agent', 'company', 'plan']);

        // Load invoices and billing cycles separately to avoid MySQL compatibility issues
        $invoices = $subscription->invoices()->latest()->take(5)->get();
        $billingCycles = $subscription->billingCycles()->latest()->take(5)->get();

        // Set the relations manually
        $subscription->setRelation('invoices', $invoices);
        $subscription->setRelation('billingCycles', $billingCycles);

        $activity = AuditLog::where('entity_type', Subscription::class)
            ->where('entity_id', $subscription->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.subscriptions.show', compact('subscription', 'activity'));
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
            ->with('success', 'Subscription activated successfully. Billing period has started.');
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->status === 'cancelled') {
            return back()->with('error', 'Subscription is already cancelled.');
        }

        $reason = $request->input('reason');

        // Check if cancellation is allowed
        $cancelCheck = $this->subscriptionService->canCancel($subscription);

        if (!$cancelCheck['can_cancel']) {
            return back()->with('error', $cancelCheck['reason']);
        }

        try {
            $this->subscriptionService->cancel($subscription, $reason);

            $message = 'Subscription cancelled successfully.';
            if ($cancelCheck['unpaid_invoices'] > 0) {
                $message .= " {$cancelCheck['unpaid_invoices']} unpaid invoice(s) have been voided.";
            }

            return redirect()->route('admin.subscriptions.show', $subscription)
                ->with('success', $message);
        } catch (SubscriptionHasPaidInvoiceException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
