<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Services\DealService;
use App\Services\PaymentProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DealController extends Controller
{
    public function __construct(
        protected DealService $dealService
    ) {}

    public function index(Request $request): View
    {
        $query = auth()->user()->can('deals.view-all')
            ? Deal::with(['closer', 'company'])
            : Deal::with(['closer', 'company'])->where('closer_id', auth()->id());

        $deals = $query->latest()->paginate(15)->withQueryString();

        return view('deals.index', compact('deals'));
    }

    public function create(): View
    {
        return view('deals.create', [
            'paddleAvailable' => PaymentProviderService::isConfigured('paddle'),
            'canOverrideFloor' => auth()->user()->can('deals.override-price-floor'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!PaymentProviderService::isConfigured('paddle')) {
            return back()->withInput()->with('error', 'Paddle is currently disabled or not configured — deals can\'t be created right now. Contact an admin.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'agreed_monthly_price' => ['required', 'numeric', 'min:0'],
            'activation_price' => ['nullable', 'numeric', 'min:0'],
            'is_trial' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        try {
            $deal = $this->dealService->create(auth()->user(), $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create the Paddle transaction: ' . $e->getMessage());
        }

        return redirect()->route('deals.show', $deal)
            ->with('success', 'Deal created. Send the checkout link below to the customer.');
    }

    public function show(Deal $deal): View
    {
        $this->authorizeAccess($deal);

        $deal->load(['closer', 'company']);

        return view('deals.show', compact('deal'));
    }

    protected function authorizeAccess(Deal $deal): void
    {
        if ($deal->closer_id !== auth()->id() && !auth()->user()->can('deals.view-all')) {
            abort(403, 'Unauthorized');
        }
    }
}
