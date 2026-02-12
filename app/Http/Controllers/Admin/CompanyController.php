<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CompanyController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = Company::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $companies = $query->withCount(['agents', 'users'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:companies'],
            'billing_email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $company = Company::create($validated);

        $this->auditService->log('company_created', $company);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Company created successfully.');
    }

    public function show(Company $company): View
    {
        $company->load(['users', 'agents.subscription.plan']);

        $recentInvoices = $company->invoices()->latest()->take(5)->get();

        return view('admin.companies.show', compact('company', 'recentInvoices'));
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:companies,email,' . $company->id],
            'billing_email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,suspended,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldValues = $company->toArray();
        $company->update($validated);

        $this->auditService->log('company_updated', $company, $oldValues);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->auditService->log('company_deleted', $company);

        $company->delete();

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    public function regenerateWebhook(Company $company): RedirectResponse
    {
        $company->regenerateWebhookSignature();

        $this->auditService->log('webhook_signature_regenerated', $company);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Webhook signature regenerated successfully.');
    }
}
