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

        $systemStats = $this->revenueService->getSystemStats($startDate, $endDate);

        $companyStats = [];
        if ($request->filled('company_id')) {
            $company = Company::findOrFail($request->company_id);
            $companyStats = $this->revenueService->getCompanyStats($company, $startDate, $endDate);
        }

        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return view('admin.revenue.index', compact(
            'systemStats',
            'companyStats',
            'companies',
            'startDate',
            'endDate'
        ));
    }
}
