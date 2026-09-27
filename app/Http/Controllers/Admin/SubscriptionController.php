<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\SubscriptionHasPaidInvoiceException;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaddleLifecycleService;
use App\Services\SubscriptionService;
use App\Support\BillingTime;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaddleLifecycleService $lifecycle
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

        // A Paddle deal already committed to specific terms for this customer — the portal's
        // invoicing MUST match what Paddle actually charges, so those terms win over whatever
        // was submitted. The agent's own deal_id (set by "Add Assistant" on a deal) finds it: the
        // deal itself, or — for a setup-only deal — the paid retainer deal created from it.
        $deal = $agent->deal?->fundingDeal();

        if (!$deal && $agent->deal?->isSetupOnly()) {
            return back()->withInput()->with('error',
                "This assistant came from the setup-only deal \"{$agent->deal->business_name}\", which has no paid monthly retainer yet — "
                . 'there is nothing to bill a subscription against. Add the monthly retainer on that deal first, and once it is paid it will fund this subscription.'
            );
        }

        if ($deal) {
            try {
                // The webhook normally filled in Paddle's dates already; if it hasn't (or missed),
                // read them from Paddle now — a subscription built on guessed dates could charge
                // on a different day than the portal says.
                if ($deal->paddle_subscription_id && $deal->paddle_status === null) {
                    $this->lifecycle->refreshDeal($deal);
                    $deal->refresh();
                }
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $subscription = $this->subscriptionService->createFromDeal($agent, $plan, $deal);
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            $price = number_format((float) $deal->agreed_monthly_price, 2);

            $message = $deal->isTrialing()
                ? "Subscription created from deal \"{$deal->business_name}\" — free trial until " . BillingTime::dateTime($deal->trial_ends_at) . ". Paddle then charges \${$price}/mo automatically and the invoice reconciles here on its own."
                : "Subscription created from deal \"{$deal->business_name}\" — \${$price}/mo, billed by Paddle. Its billing periods follow Paddle's, so nothing needs invoicing by hand.";
        } else {
            $isTrial = !empty($validated['is_trial']);
            $trialDays = (int) ($validated['trial_days'] ?? 30);

            $subscription = $this->subscriptionService->create(
                $agent,
                $plan,
                $validated['custom_price'] ?? null,
                $isTrial,
                $trialDays
            );

            $message = $isTrial
                ? "Free trial started. The assistant is now active for {$trialDays} days. A trial welcome email has been sent."
                : 'Subscription created. Invoice and payment link have been sent to the customer.';
        }

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

        $note = '';

        // The retainer of a Paddle-managed subscription is what Paddle charges (the deal's price).
        // Changing it here would make the portal's invoices disagree with the card charges, so the
        // price is kept; the plan can still change (included minutes, per-minute rate).
        if ($subscription->isPaddleManaged()) {
            if ((string) ($validated['custom_price'] ?? '') !== (string) $subscription->custom_price
                && (float) ($validated['custom_price'] ?? 0) !== (float) $subscription->custom_price) {
                $note = ' The monthly price stays $' . number_format((float) $subscription->custom_price, 2) . ' because Paddle bills it — change it by creating a new deal.';
            }

            $validated['custom_price'] = $subscription->custom_price;
        }

        $subscription->update($validated);

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.' . $note);
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

        // A Paddle-managed subscription must be cancelled IN PADDLE — cancelling only the portal
        // record would leave Paddle charging the customer's card every month. Cancelling there
        // cancels the portal subscription too (PaddleLifecycleService::syncSubscription), voids its
        // unpaid invoices, deactivates the assistant and emails the customer.
        $deal = $subscription->isPaddleManaged() ? $subscription->deal : null;

        if ($deal && $deal->paddle_status !== 'canceled') {
            try {
                $this->lifecycle->cancelSubscription($deal, immediately: true, reason: $reason);

                return redirect()->route('admin.subscriptions.show', $subscription)
                    ->with('success', 'Subscription cancelled in Paddle and here — nothing further will be charged, and the customer has been emailed.');
            } catch (\InvalidArgumentException|\App\Exceptions\PaddleApiException $e) {
                return back()->with('error', $e->getMessage());
            }
        }

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
