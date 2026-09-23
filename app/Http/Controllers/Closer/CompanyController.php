<?php

namespace App\Http\Controllers\Closer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

/**
 * Read-only Customer viewer for the closer/manager role, scoped to whatever an
 * admin has explicitly granted via company_user_access / agent_user_access
 * (Admin > Users > a closer's "Customer & Assistant Access" section). Built so an
 * admin can hand a manager visibility into exactly one demo customer/assistant to
 * show on a sales call, without exposing real customers' data or the financial
 * detail (invoices, margin) that the admin-facing company/agent screens show.
 *
 * Admin can also reach these routes (role_or_permission:admin|closer) and sees
 * every company unfiltered, but normally uses the full admin.companies.* screens
 * instead — this controller exists for closer/manager.
 */
class CompanyController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $companies = $user->isAdmin()
            ? Company::withCount('agents')->orderBy('name')->paginate(20)
            : $user->accessibleCompanies()->orderBy('name')->paginate(20);

        // For a scoped (non-admin) user, show only the count of agents THEY can
        // see — the company's real total would leak how many are hidden from them.
        if (!$user->isAdmin()) {
            $companies->getCollection()->transform(function ($company) use ($user) {
                $company->agents_count = $user->visibleAgentsIn($company)->count();
                return $company;
            });
        }

        return view('closer.companies.index', compact('companies'));
    }

    public function show(Company $company): View
    {
        $user = auth()->user();

        abort_unless($user->canAccessCompany($company), 403);

        $agents = $user->isAdmin() ? $company->agents()->get() : $user->visibleAgentsIn($company);

        return view('closer.companies.show', compact('company', 'agents'));
    }
}
