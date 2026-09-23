@extends('layouts.admin')

@section('title', $user->full_name)

@section('page-pretitle')
    Users
@endsection

@section('page-header')
    {{ $user->full_name }}
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
            Edit User
        </a>
        @if($user->status === 'pending')
            <form action="{{ route('admin.users.resend-invitation', $user) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                    Resend Invitation
                </button>
            </form>
        @else
            <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Send a password reset link to {{ $user->email }}?');">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 11m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0" /><path d="M12 8v4l2 2" /></svg>
                    Reset Password
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <!-- User Profile Card -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl {{ $user->role === 'admin' ? 'bg-red-lt' : ($user->role === 'closer' ? 'bg-orange-lt' : 'bg-primary-lt') }}">
                            {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                        </span>
                    </div>
                    <h3 class="card-title mb-1">{{ $user->full_name }}</h3>
                    <p class="text-muted">{{ $user->email }}</p>
                    <div class="mb-3">
                        @if($user->role === 'admin')
                            <span class="badge bg-red-lt">{{ $user->role_label }}</span>
                        @elseif($user->role === 'closer')
                            <span class="badge bg-orange-lt">{{ $user->role_label }}</span>
                        @else
                            <span class="badge bg-blue-lt">{{ $user->role_label }}</span>
                        @endif
                        @switch($user->status)
                            @case('active')
                                <span class="badge bg-green-lt">Active</span>
                                @break
                            @case('pending')
                                <span class="badge bg-yellow-lt">Pending</span>
                                @break
                            @case('suspended')
                                <span class="badge bg-red-lt">Suspended</span>
                                @break
                        @endswitch
                    </div>
                </div>
                <div class="card-body border-top">
                    <div class="datagrid">
                        @if($user->phone)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Phone</div>
                                <div class="datagrid-content">{{ $user->phone }}</div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Customer</div>
                            <div class="datagrid-content">
                                @if($user->company)
                                    <a href="{{ route('admin.companies.show', $user->company) }}">
                                        {{ $user->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">No customer</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $user->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Last Login</div>
                            <div class="datagrid-content">
                                {{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : 'Never' }}
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Email Verified</div>
                            <div class="datagrid-content">
                                @if($user->email_verified_at)
                                    <span class="badge bg-green-lt">
                                        {{ $user->email_verified_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="badge bg-yellow-lt">Not Verified</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Direct Permissions (in addition to whatever the role grants) -->
            @if($user->role !== 'admin')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Permissions</h3>
                    </div>
                    <form action="{{ route('admin.users.permissions.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            @if($rolePermissions->isNotEmpty())
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Via {{ $user->role_label }} role (edit in Roles & Permissions)</div>
                                    @foreach($rolePermissions as $name)
                                        <span class="badge bg-blue-lt me-1 mb-1">{{ $name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="text-muted small mb-1">Granted directly to this user</div>
                            @forelse($permissions as $permission)
                                @php $viaRole = $rolePermissions->contains($permission->name); @endphp
                                {{-- Disabled checkboxes aren't submitted — a role-derived permission here
                                     is display-only (already true via the role) and never gets written
                                     into this user's own direct-permission set on save. --}}
                                <label class="form-check form-switch {{ $viaRole ? 'opacity-50' : '' }}">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                        {{ $directPermissions->contains($permission->name) || $viaRole ? 'checked' : '' }}
                                        {{ $viaRole ? 'disabled' : '' }}>
                                    <span class="form-check-label">{{ $permission->name }}</span>
                                </label>
                            @empty
                                <p class="text-muted mb-0">No permissions defined yet.</p>
                            @endforelse
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Customer & Assistant Access (closer/manager only) -->
            @if($user->isCloser())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Customer & Assistant Access</h3>
                    </div>
                    <form action="{{ route('admin.users.access.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                {{ $user->role_label }} sees nothing here by default. Check a customer to grant
                                visibility to it (read-only — no invoices, no cost data). Leave "assistants" unchecked
                                to allow all of that customer's assistants, or check specific ones to limit it to
                                just those — e.g. one demo assistant to show on a call.
                            </p>

                            @forelse($accessibleCompanies as $company)
                                @php $companyChecked = $accessibleCompanyIds->contains($company->id); @endphp
                                <div class="mb-2 border rounded p-2">
                                    <label class="form-check mb-0">
                                        <input class="form-check-input company-access-checkbox" type="checkbox"
                                               name="companies[]" value="{{ $company->id }}"
                                               {{ $companyChecked ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $company->name }}</span>
                                    </label>

                                    @if($company->agents->isNotEmpty())
                                        <details class="ms-4 mt-1" {{ $companyChecked ? 'open' : '' }}>
                                            <summary class="text-muted small">Assistants ({{ $company->agents->count() }})</summary>
                                            @foreach($company->agents as $agent)
                                                <label class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="agents[]" value="{{ $agent->id }}"
                                                           {{ $accessibleAgentIds->contains($agent->id) ? 'checked' : '' }}>
                                                    <span class="form-check-label">{{ $agent->name }}</span>
                                                </label>
                                            @endforeach
                                        </details>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted mb-0">No active customers to grant access to yet.</p>
                            @endforelse
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Save Access</button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Invitation Token (for pending users) -->
            @if($user->status === 'pending' && $user->signup_token)
                <div class="card bg-yellow-lt">
                    <div class="card-body">
                        <h4 class="mb-2">Invitation Link</h4>
                        <p class="text-muted small mb-2">Share this link with the user to complete their registration:</p>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" value="{{ route('signup.form', $user->signup_token) }}" readonly id="inviteLink">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInviteLink()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
                            </button>
                        </div>
                        @if($user->signup_token_expires_at)
                            <div class="small text-muted mt-2">
                                Expires: {{ $user->signup_token_expires_at->format('M d, Y h:i A') }}
                                @if($user->signup_token_expires_at->isPast())
                                    <span class="badge bg-red-lt">Expired</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            @if($user->company)
                <!-- Customer's Agents -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Customer's Agents</h3>
                        <div class="card-actions">
                            <a href="{{ route('admin.companies.show', $user->company) }}" class="btn btn-ghost-primary btn-md">
                                View Customer
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Plan</th>
                                    <th>Usage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($companyAgents as $agent)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.agents.show', $agent) }}" class="text-reset">
                                                {{ $agent->name }}
                                            </a>
                                            @if($agent->phone_number)
                                                <div class="text-muted small">{{ $agent->phone_number }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $agent->subscription?->plan?->name ?? '-' }}</td>
                                        <td>
                                            @if($agent->subscription && $agent->subscription->plan)
                                                @php
                                                    $used = $agent->subscription->minutes_used ?? 0;
                                                    $included = $agent->subscription->plan->included_minutes ?? 1;
                                                    $percent = min(100, ($used / $included) * 100);
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="usage-bar flex-fill me-2" style="height: 6px; width: 60px;">
                                                        <div class="usage-bar-fill {{ $percent > 80 ? 'usage-warning' : 'usage-normal' }}" style="width: {{ $percent }}%"></div>
                                                    </div>
                                                    <span class="small text-muted">{{ number_format($used, 0) }}/{{ number_format($included) }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($agent->subscription)
                                                @switch($agent->subscription->status)
                                                    @case('active')
                                                        <span class="badge bg-green-lt">Active</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-yellow-lt">Pending</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary-lt">{{ ucfirst($agent->subscription->status) }}</span>
                                                @endswitch
                                            @else
                                                <span class="text-muted">No plan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No agents configured for this company
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Customer's Invoices -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Customer's Invoices</h3>
                        <div class="card-actions">
                            <a href="{{ route('admin.invoices.index') }}?company={{ $user->company->uuid }}" class="btn btn-ghost-primary btn-md">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($companyInvoices as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $invoice) }}">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="text-money">${{ number_format($invoice->amount, 2) }}</td>
                                        <td>
                                            @if($invoice->due_date)
                                                <span class="{{ $invoice->due_date->isPast() && $invoice->status !== 'paid' ? 'text-danger' : '' }}">
                                                    {{ $invoice->due_date->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($invoice->status)
                                                @case('paid')
                                                    <span class="badge bg-green-lt">Paid</span>
                                                    @break
                                                @case('sent')
                                                    <span class="badge bg-primary-lt">Sent</span>
                                                    @break
                                                @case('overdue')
                                                    <span class="badge bg-red-lt">Overdue</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary-lt">Draft</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-icon btn-ghost-primary btn-md">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No invoices yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($companyInvoices->count() > 0)
                        <div class="card-footer">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="text-muted">
                                        Total Pending:
                                        <strong class="text-money">${{ number_format($companyInvoices->where('status', '!=', 'paid')->sum('amount'), 2) }}</strong>
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <span class="text-muted">
                                        Total Paid:
                                        <strong class="text-money text-green">${{ number_format($companyInvoices->where('status', 'paid')->sum('amount'), 2) }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <!-- No Company Info -->
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                            </div>
                            <p class="empty-state-title">No customer assigned</p>
                            <p class="empty-state-description">
                                This user is not associated with any customer.<br>
                                Assign a customer to view billing and agent information.
                            </p>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                                Edit User
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function copyInviteLink() {
        const input = document.getElementById('inviteLink');
        input.select();
        document.execCommand('copy');
        alert('Invitation link copied to clipboard!');
    }
</script>
@endpush
