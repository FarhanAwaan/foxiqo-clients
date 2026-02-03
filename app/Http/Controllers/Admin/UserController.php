<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = User::with('company');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'companies'));
    }

    public function create(): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        $selectedCompanyId = null;
        if (request('company_id')) {
            $selectedCompany = Company::where('uuid', request('company_id'))->first();
            $selectedCompanyId = $selectedCompany?->id;
        }

        return view('admin.users.create', compact('companies', 'selectedCompanyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company_id' => ['required', 'exists:companies,id'],
            'role' => ['required', 'in:admin,customer'],
        ]);

        $validated['status'] = 'pending';

        $user = User::create($validated);
        $token = $user->generateSignupToken();

        $this->auditService->log('user_created', $user);

        // TODO: Send invitation email
        // Mail::to($user->email)->send(new UserInvitationMail($user, $token));

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created. Invitation email will be sent.');
    }

    public function show(User $user): View
    {
        $user->load('company');

        $companyInvoices = collect();
        $companyAgents = collect();

        if ($user->company) {
            $companyInvoices = $user->company->invoices()
                ->latest()
                ->take(10)
                ->get();

            $companyAgents = $user->company->agents()
                ->with(['subscription.plan'])
                ->get();
        }

        return view('admin.users.show', compact('user', 'companyInvoices', 'companyAgents'));
    }

    public function edit(User $user): View
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $selectedCompanyId = $user->company_id;

        return view('admin.users.edit', compact('user', 'companies', 'selectedCompanyId'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'role' => ['required', 'in:admin,customer'],
            'status' => ['required', 'in:pending,active,suspended'],
        ]);

        $oldValues = $user->toArray();
        $user->update($validated);

        $this->auditService->log('user_updated', $user, $oldValues);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $this->auditService->log('user_deleted', $user);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function resendInvitation(User $user): RedirectResponse
    {
        if ($user->status !== 'pending') {
            return back()->with('error', 'Invitation can only be resent to pending users.');
        }

        $token = $user->generateSignupToken();

        // TODO: Send invitation email
        // Mail::to($user->email)->send(new UserInvitationMail($user, $token));

        return back()->with('success', 'Invitation resent successfully.');
    }
}
