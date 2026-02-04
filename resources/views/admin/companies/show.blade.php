@extends('layouts.admin')

@section('title', $company->name)

@section('page-pretitle')
    Companies
@endsection

@section('page-header')
    {{ $company->name }}
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
            Edit Company
        </a>
        <a href="{{ route('admin.users.create') }}?company_id={{ $company->uuid }}" class="btn btn-outline-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M16 19h6" /><path d="M19 16v6" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4" /></svg>
            Add User
        </a>
        <a href="{{ route('admin.agents.create') }}?company_id={{ $company->uuid }}" class="btn btn-outline-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
            Add Agent
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <!-- Company Details Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Company Details</h3>
                    <div class="card-actions">
                        @switch($company->status)
                            @case('active')
                                <span class="badge bg-green-lt">Active</span>
                                @break
                            @case('suspended')
                                <span class="badge bg-red-lt">Suspended</span>
                                @break
                            @default
                                <span class="badge bg-secondary-lt">Inactive</span>
                        @endswitch
                    </div>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Primary Email</div>
                            <div class="datagrid-content">
                                <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                            </div>
                        </div>
                        @if($company->billing_email)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Billing Email</div>
                                <div class="datagrid-content">
                                    <a href="mailto:{{ $company->billing_email }}">{{ $company->billing_email }}</a>
                                </div>
                            </div>
                        @endif
                        @if($company->phone)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Phone</div>
                                <div class="datagrid-content">{{ $company->phone }}</div>
                            </div>
                        @endif
                        @if($company->full_address)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Address</div>
                                <div class="datagrid-content">{{ $company->full_address }}</div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $company->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>
                @if($company->notes)
                    <div class="card-footer">
                        <div class="text-muted small">
                            <strong>Notes:</strong><br>
                            {{ $company->notes }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Stats</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <span class="avatar bg-primary-lt me-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                                </span>
                                <div>
                                    <div class="h3 mb-0">{{ $company->users->count() }}</div>
                                    <div class="text-muted small">Users</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <span class="avatar bg-green-lt me-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                                </span>
                                <div>
                                    <div class="h3 mb-0">{{ $company->agents->count() }}</div>
                                    <div class="text-muted small">Agents</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Users -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Users</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.users.create') }}?company_id={{ $company->uuid }}" class="btn btn-primary btn-sm">
                            Add User
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($company->users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm bg-primary-lt me-2">
                                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                            </span>
                                            <span>{{ $user->full_name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @switch($user->status)
                                            @case('active')
                                                <span class="badge bg-green-lt">Active</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-yellow-lt">Pending</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary-lt">{{ ucfirst($user->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-muted">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-icon btn-ghost-primary btn-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No users yet. <a href="{{ route('admin.users.create') }}?company_id={{ $company->uuid }}">Add the first user</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Agents -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Agents</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.agents.create') }}?company_id={{ $company->uuid }}" class="btn btn-primary btn-sm">
                            Add Agent
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Phone</th>
                                <th>Plan</th>
                                <th>Subscription</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($company->agents as $agent)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.agents.show', $agent) }}" class="text-reset">
                                            {{ $agent->name }}
                                        </a>
                                        @if($agent->description)
                                            <div class="text-muted small text-truncate" style="max-width: 200px;">
                                                {{ $agent->description }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $agent->phone_number ?? '-' }}</td>
                                    <td>{{ $agent->subscription?->plan?->name ?? '-' }}</td>
                                    <td>
                                        @if($agent->subscription)
                                            @switch($agent->subscription->status)
                                                @case('active')
                                                    <span class="badge bg-green-lt">Active</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge bg-yellow-lt">Pending</span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="badge bg-red-lt">Cancelled</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary-lt">{{ ucfirst($agent->subscription->status) }}</span>
                                            @endswitch
                                        @else
                                            <span class="text-muted">No subscription</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-icon btn-ghost-primary btn-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No agents yet. <a href="{{ route('admin.agents.create') }}?company_id={{ $company->uuid }}">Add the first agent</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Invoices</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.invoices.index') }}?company={{ $company->uuid }}" class="btn btn-ghost-primary btn-md">
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.invoices.show', $invoice) }}">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="text-money">${{ number_format($invoice->amount, 2) }}</td>
                                    <td>{{ $invoice->due_date?->format('M d, Y') ?? '-' }}</td>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No invoices yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
