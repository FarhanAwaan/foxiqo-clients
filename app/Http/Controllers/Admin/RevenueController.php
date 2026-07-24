<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\RevenueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function __construct(
        protected RevenueService $revenueService
    ) {}

    public function index(Request $request): View
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $companies = Company::where('status', 'active')->orderBy('name')->get();

        $selectedCompany = null;
        $companyStats = [];
        if ($request->filled('company_id')) {
            $selectedCompany = Company::findOrFail($request->company_id);
            $companyStats = $this->revenueService->getCompanyStats($selectedCompany, $startDate, $endDate);
        }

        // The four summary cards scope to the selected company when one is chosen,
        // rather than always showing the portfolio-wide total regardless of the filter.
        $summaryStats = $selectedCompany
            ? [
                'revenue' => $companyStats['revenue'],
                'cost' => $companyStats['retell_cost'],
                'profit' => $companyStats['profit'],
                'margin' => $companyStats['margin'],
            ]
            : $this->revenueService->getSystemStats($startDate, $endDate);

        // Per-company margin breakdown for the whole portfolio, sorted worst-margin-first
        // so underperforming accounts surface immediately. Skipped when a single company
        // is selected — the per-agent breakdown below covers that view instead.
        $companyBreakdown = $selectedCompany
            ? collect()
            : $companies
                ->map(fn (Company $company) => array_merge(
                    ['company' => $company],
                    $this->revenueService->getCompanyStats($company, $startDate, $endDate)
                ))
                ->sortBy('margin')
                ->values();

        return view('admin.revenue.index', compact(
            'summaryStats',
            'selectedCompany',
            'companyStats',
            'companyBreakdown',
            'companies',
            'startDate',
            'endDate'
        ));
    }
}
