@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-pretitle')
    Overview
@endsection

@section('page-header')
    Dashboard
@endsection

@section('content')
    <div class="row row-deck row-cards">
        <!-- Stats Cards -->
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Active Subscriptions</div>
                    </div>
                    <div class="h1 mb-3">{{ number_format($activeSubscriptions) }}</div>
                    <div class="d-flex mb-2">
                        <div>
                            <span class="text-green d-inline-flex align-items-center lh-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                                Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Active Companies</div>
                    </div>
                    <div class="h1 mb-3">{{ number_format($activeCompanies) }}</div>
                    <div class="d-flex mb-2">
                        <div>
                            <span class="text-blue d-inline-flex align-items-center lh-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                                Registered
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Pending Payments</div>
                    </div>
                    <div class="h1 mb-3">${{ number_format($pendingPayments, 2) }}</div>
                    <div class="d-flex mb-2">
                        <div>
                            <span class="text-yellow d-inline-flex align-items-center lh-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 8v4l2 2" /></svg>
                                Awaiting
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">This Month Revenue</div>
                    </div>
                    <div class="h1 mb-3">${{ number_format($thisMonth['revenue'] ?? 0, 2) }}</div>
                    <div class="d-flex mb-2">
                        <div>
                            @php
                                $lastMonthRevenue = $lastMonth['revenue'] ?? 0;
                                $thisMonthRevenue = $thisMonth['revenue'] ?? 0;
                                $change = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
                            @endphp
                            @if($change >= 0)
                                <span class="text-green d-inline-flex align-items-center lh-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                                    {{ number_format(abs($change), 1) }}%
                                </span>
                            @else
                                <span class="text-red d-inline-flex align-items-center lh-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7l6 6l4 -4l8 8" /><path d="M21 10l0 7l-7 0" /></svg>
                                    {{ number_format(abs($change), 1) }}%
                                </span>
                            @endif
                            <span class="text-muted ms-1">vs last month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Summary Cards -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">This Month Summary</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Revenue</div>
                            <div class="datagrid-content">${{ number_format($thisMonth['revenue'] ?? 0, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Retell Cost</div>
                            <div class="datagrid-content">${{ number_format($thisMonth['cost'] ?? 0, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Profit</div>
                            <div class="datagrid-content">
                                <span class="{{ ($thisMonth['profit'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">
                                    ${{ number_format($thisMonth['profit'] ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Profit Margin</div>
                            <div class="datagrid-content">
                                <span class="{{ ($thisMonth['margin'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">
                                    {{ number_format($thisMonth['margin'] ?? 0, 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Last Month Summary</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Revenue</div>
                            <div class="datagrid-content">${{ number_format($lastMonth['revenue'] ?? 0, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Retell Cost</div>
                            <div class="datagrid-content">${{ number_format($lastMonth['cost'] ?? 0, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Profit</div>
                            <div class="datagrid-content">
                                <span class="{{ ($lastMonth['profit'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">
                                    ${{ number_format($lastMonth['profit'] ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Profit Margin</div>
                            <div class="datagrid-content">
                                <span class="{{ ($lastMonth['margin'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">
                                    {{ number_format($lastMonth['margin'] ?? 0, 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Invoices</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Company</th>
                                <th>Amount</th>
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
                                    <td class="text-muted">{{ $invoice->company->name ?? 'N/A' }}</td>
                                    <td>${{ number_format($invoice->amount, 2) }}</td>
                                    <td>
                                        @switch($invoice->status)
                                            @case('paid')
                                                <span class="badge bg-green-lt">Paid</span>
                                                @break
                                            @case('sent')
                                                <span class="badge bg-blue-lt">Sent</span>
                                                @break
                                            @case('overdue')
                                                <span class="badge bg-red-lt">Overdue</span>
                                                @break
                                            @default
                                                <span class="badge bg-yellow-lt">Draft</span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No invoices yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Subscriptions</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Company</th>
                                <th>Plan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSubscriptions as $subscription)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.subscriptions.show', $subscription) }}">
                                            {{ $subscription->agent->name ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td class="text-muted">{{ $subscription->company->name ?? 'N/A' }}</td>
                                    <td>{{ $subscription->plan->name ?? 'N/A' }}</td>
                                    <td>
                                        @switch($subscription->status)
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
                                                <span class="badge bg-secondary-lt">{{ ucfirst($subscription->status) }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No subscriptions yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
