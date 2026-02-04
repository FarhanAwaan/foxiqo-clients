<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the customer's subscriptions.
     */
    public function index(Request $request): View
    {
        $company = auth()->user()->company;

        $query = Subscription::where('company_id', $company->id)
            ->with(['agent', 'plan'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        // Get summary statistics
        $stats = [
            'total' => Subscription::where('company_id', $company->id)->count(),
            'active' => Subscription::where('company_id', $company->id)->where('status', 'active')->count(),
            'pending' => Subscription::where('company_id', $company->id)->where('status', 'pending')->count(),
            'cancelled' => Subscription::where('company_id', $company->id)->where('status', 'cancelled')->count(),
            'monthly_total' => Subscription::where('company_id', $company->id)
                ->where('status', 'active')
                ->get()
                ->sum(fn($sub) => $sub->getEffectivePrice()),
        ];

        return view('customer.subscriptions.index', compact('subscriptions', 'stats'));
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription): View
    {
        $company = auth()->user()->company;

        // Ensure the subscription belongs to the customer's company
        if ($subscription->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this subscription.');
        }

        $subscription->load(['agent', 'plan']);

        // Load invoices separately to avoid MySQL compatibility issues
        $invoices = $subscription->invoices()
            ->latest()
            ->take(10)
            ->get();

        // Load billing cycles separately
        $billingCycles = $subscription->billingCycles()
            ->latest()
            ->take(5)
            ->get();

        return view('customer.subscriptions.show', compact('subscription', 'invoices', 'billingCycles'));
    }
}
