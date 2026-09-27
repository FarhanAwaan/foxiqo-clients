<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Invoice;
use App\Services\DealService;
use App\Services\EmailService;
use App\Services\PaymentProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DealController extends Controller
{
    public function __construct(
        protected DealService $dealService,
        protected EmailService $emailService
    ) {}

    public function index(Request $request): View
    {
        $query = auth()->user()->can('deals.view-all')
            ? Deal::with(['closer', 'company'])
            : Deal::with(['closer', 'company'])->where('closer_id', auth()->id());

        $deals = $query->latest()->paginate(15)->withQueryString();

        return view('deals.index', compact('deals'));
    }

    /**
     * New deal — or, with ?parent={uuid}, the monthly retainer for a paid setup-only deal
     * (customer details are inherited; only the price and trial are asked for).
     */
    public function create(Request $request): View|RedirectResponse
    {
        $parent = null;

        if ($request->filled('parent')) {
            $parent = Deal::where('uuid', $request->query('parent'))->firstOrFail();
            $this->authorizeAccess($parent);

            try {
                $this->dealService->assertCanAddRetainer($parent);
            } catch (\InvalidArgumentException $e) {
                return redirect()->route('deals.show', $parent)->with('error', $e->getMessage());
            }
        }

        return view('deals.create', [
            'paddleAvailable' => PaymentProviderService::isConfigured('paddle'),
            'canOverrideFloor' => auth()->user()->can('deals.override-price-floor'),
            'parent' => $parent,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!PaymentProviderService::isConfigured('paddle')) {
            return back()->withInput()->with('error', 'Paddle is currently disabled or not configured — deals can\'t be created right now. Contact an admin.');
        }

        // A retainer deal inherits the customer from its parent, so those fields aren't asked for.
        $customerRule = $request->filled('parent_deal') ? 'nullable' : 'required';

        $validated = $request->validate([
            'parent_deal' => ['nullable', 'string', 'exists:deals,uuid'],
            'billing_mode' => ['nullable', 'in:recurring,setup_only'],
            'customer_name' => [$customerRule, 'string', 'max:255'],
            'business_name' => [$customerRule, 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [$customerRule, 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            // The business rules ("must be more than $0", the price floor) live in DealService.
            'agreed_monthly_price' => ['nullable', 'numeric', 'min:0'],
            'activation_price' => ['nullable', 'numeric', 'min:0'],
            'is_trial' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'email_link' => ['nullable', 'boolean'],
        ]);

        $parent = null;

        if (!empty($validated['parent_deal'])) {
            $parent = Deal::where('uuid', $validated['parent_deal'])->firstOrFail();
            $this->authorizeAccess($parent);
        }

        $validated['parent_deal_id'] = $parent?->id;
        $validated['email_link'] = $request->boolean('email_link');

        try {
            $deal = $this->dealService->create(auth()->user(), $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create the Paddle transaction: ' . $e->getMessage());
        }

        return redirect()->route('deals.show', $deal)
            ->with('success', $validated['email_link']
                ? "Deal created. The checkout link was emailed to {$deal->email} and you'll be told when they pay."
                : 'Deal created. Send the checkout link below to the customer.');
    }

    public function show(Deal $deal): View
    {
        $this->authorizeAccess($deal);

        $deal->load(['closer', 'company', 'parentDeal', 'childDeals', 'subscription.agent']);

        $isAdmin = auth()->user()->isAdmin();

        // Admin only: what the portal has recorded for this deal, next to what Paddle holds.
        $invoices = $isAdmin
            ? Invoice::where(function ($q) use ($deal) {
                $q->where('deal_id', $deal->id);

                if ($deal->subscription_id) {
                    $q->orWhere('subscription_id', $deal->subscription_id);
                }
            })->latest('id')->get()
            : collect();

        $activity = $isAdmin
            ? AuditLog::where('entity_type', Deal::class)->where('entity_id', $deal->id)->with('user')->latest('id')->get()
            : collect();

        return view('deals.show', compact('deal', 'invoices', 'activity'));
    }

    /** Re-send the checkout link email — the customer lost it, or it went to spam. */
    public function emailLink(Deal $deal): RedirectResponse
    {
        $this->authorizeAccess($deal);

        if ($deal->status !== 'sent') {
            return back()->with('error', 'Only a deal that is still waiting for payment has a checkout link to send.');
        }

        $this->emailService->sendDealCheckoutLink($deal);

        return back()->with('success', "Checkout link emailed to {$deal->email}.");
    }

    /** Close an unpaid deal (wrong price, wrong customer…); its checkout link stops working. */
    public function void(Request $request, Deal $deal): RedirectResponse
    {
        $this->authorizeAccess($deal);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $this->dealService->void($deal, $request->input('reason'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('deals.show', $deal)->with('success', 'Deal voided. Its checkout link no longer works.');
    }

    protected function authorizeAccess(Deal $deal): void
    {
        if ($deal->closer_id !== auth()->id() && !auth()->user()->can('deals.view-all')) {
            abort(403, 'Unauthorized');
        }
    }
}
