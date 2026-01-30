<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\RevenueService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected RevenueService $revenueService
    ) {}

    public function index(): View
    {
        $stats = $this->revenueService->getDashboardStats();

        $data = [
            'activeSubscriptions' => Subscription::active()->count(),
            'activeCompanies' => Company::where('status', 'active')->count(),
            'pendingPayments' => Invoice::unpaid()->sum('amount'),
            'thisMonth' => $stats['this_month'],
            'lastMonth' => $stats['last_month'],
            'recentInvoices' => Invoice::with(['company', 'subscription.agent'])
                ->latest()
                ->take(5)
                ->get(),
            'recentSubscriptions' => Subscription::with(['company', 'agent', 'plan'])
                ->latest()
                ->take(5)
                ->get(),
        ];

        return view('admin.dashboard.index', $data);
    }
}
