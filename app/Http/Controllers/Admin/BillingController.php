<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Deal;
use App\Services\RevenueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Landing page for the "Billing & Usage" nav group — merges what used to be two
 * separate pages (a live MRR/usage-focused customer list, and the historical
 * Revenue/margin report) into one, since both were really the same question
 * ("how is each customer doing, financially") viewed from different angles:
 * MRR/usage are live, forward-looking numbers; revenue/cost/margin are
 * backward-looking, scoped to whatever date range is selected.
 */
class BillingController extends Controller
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
        $mrr = null;
        if ($request->filled('company_id')) {
            $selectedCompany = Company::with('agents.subscription.plan')->findOrFail($request->company_id);
            $companyStats = $this->revenueService->getCompanyStats($selectedCompany, $startDate, $endDate);
            $mrr = $selectedCompany->agents->pluck('subscription')->filter(fn ($s) => $s?->status === 'active')
                ->sum(fn ($s) => $s->getEffectivePrice());
        }

        $systemStats = $this->revenueService->getSystemStats($startDate, $endDate);
        $mrr ??= $systemStats['current_mrr'];

        // Paddle subscriptions whose charge date passed with no charge — a banner up top and a badge per
        // customer. Scoped to the selected customer when one is chosen, like everything else on the page.
        $overdueCharges = Deal::chargeOverdue()
            ->when($selectedCompany, fn ($q) => $q->where('company_id', $selectedCompany->id))
            ->with('company')
            ->orderBy('next_billed_at')
            ->get();
        $overdueChargeCompanyIds = $overdueCharges->pluck('company_id')->filter()->unique();

        $sort = $request->input('sort', 'name');

        $companyRows = collect();
        if (!$selectedCompany) {
            $companyRows = $companies->load('agents.subscription.plan')->map(function (Company $company) use ($startDate, $endDate, $overdueChargeCompanyIds) {
                $revenueStats = $this->revenueService->getCompanyStats($company, $startDate, $endDate);
                $activeSubscriptions = $company->agents->pluck('subscription')->filter(fn ($s) => $s?->status === 'active');

                return (object) [
                    'company' => $company,
                    'agents_count' => $company->agents->count(),
                    'mrr' => $activeSubscriptions->sum(fn ($s) => $s->getEffectivePrice()),
                    'minutes_used' => $activeSubscriptions->sum('minutes_used'),
                    'usage_cost' => $activeSubscriptions->sum(
                        fn ($s) => $s->minutes_used * (float) ($s->plan->per_minute_rate ?? 0)
                    ),
                    'revenue' => $revenueStats['revenue'],
                    'retell_cost' => $revenueStats['retell_cost'],
                    'profit' => $revenueStats['profit'],
                    'margin' => $revenueStats['margin'],
                    'has_overdue' => $company->invoices()->overdue()->exists(),
                    'charge_overdue' => $overdueChargeCompanyIds->contains($company->id),
                    'has_trial' => $activeSubscriptions->contains('is_trial', true),
                ];
            });

            $sort = $request->input('sort', 'name');
            $companyRows = (match ($sort) {
                'mrr' => $companyRows->sortByDesc('mrr'),
                'usage' => $companyRows->sortByDesc('usage_cost'),
                'margin' => $companyRows->sortBy('margin'),
                'overdue' => $companyRows->sortByDesc(fn ($row) => $row->has_overdue || $row->charge_overdue),
                default => $companyRows->sortBy(fn ($row) => $row->company->name),
            })->values();
        }

        $perPage = 20;
        $page = $request->input('page', 1);
        $paginatedRows = new LengthAwarePaginator(
            $companyRows->forPage($page, $perPage),
            $companyRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.billing.index', compact(
            'paginatedRows', 'sort', 'systemStats', 'selectedCompany', 'companyStats', 'mrr',
            'companies', 'startDate', 'endDate', 'overdueCharges'
        ));
    }
}
