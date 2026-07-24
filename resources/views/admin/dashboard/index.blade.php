@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-pretitle')
    Overview
@endsection

@section('page-header')
    Dashboard
@endsection

@section('content')
    @php
        $lastMonthRevenue = $lastMonth['revenue'] ?? 0;
        $thisMonthRevenue = $thisMonth['revenue'] ?? 0;
        $change = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
        $margin = $thisMonth['margin'] ?? 0;
    @endphp

    <!-- Hero Metric: This Month Revenue -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-auto">
                    <div class="subheader mb-1">This Month Revenue</div>
                    <div class="hero-metric">${{ number_format($thisMonthRevenue, 2) }}</div>
                    <div class="d-flex align-items-center mt-2">
                        @if($change >= 0)
                            <span class="text-green d-inline-flex align-items-center lh-1 fw-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                                {{ number_format(abs($change), 1) }}%
                            </span>
                        @else
                            <span class="text-red d-inline-flex align-items-center lh-1 fw-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7l6 6l4 -4l8 8" /><path d="M21 10l0 7l-7 0" /></svg>
                                {{ number_format(abs($change), 1) }}%
                            </span>
                        @endif
                        <span class="text-muted ms-2">vs last month</span>
                    </div>
                </div>

                <div class="col-12 col-lg-auto d-none d-lg-block">
                    <div class="hero-metric-divider"></div>
                </div>

                <div class="col-12 col-lg">
                    <div class="row g-4">
                        <div class="col-6 col-md-3">
                            <div class="subheader">Profit Margin</div>
                            <div class="hero-metric-secondary {{ $margin >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($margin, 1) }}%</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="subheader">Active Subscriptions</div>
                            <div class="hero-metric-secondary">{{ number_format($activeSubscriptions) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="subheader">Active Companies</div>
                            <div class="hero-metric-secondary">{{ number_format($activeCompanies) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="subheader">Pending Payments</div>
                            <div class="hero-metric-secondary {{ $pendingPayments > 0 ? 'text-warning' : '' }}">${{ number_format($pendingPayments, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-deck row-cards">
        <!-- This Month vs Last Month (secondary detail — progressive disclosure) -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">This Month vs Last Month</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Revenue</th>
                                <th>Retell Cost</th>
                                <th>Profit</th>
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-muted">This Month</td>
                                <td>${{ number_format($thisMonth['revenue'] ?? 0, 2) }}</td>
                                <td>${{ number_format($thisMonth['cost'] ?? 0, 2) }}</td>
                                <td class="{{ ($thisMonth['profit'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">${{ number_format($thisMonth['profit'] ?? 0, 2) }}</td>
                                <td>{{ number_format($thisMonth['margin'] ?? 0, 1) }}%</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Month</td>
                                <td>${{ number_format($lastMonth['revenue'] ?? 0, 2) }}</td>
                                <td>${{ number_format($lastMonth['cost'] ?? 0, 2) }}</td>
                                <td class="{{ ($lastMonth['profit'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">${{ number_format($lastMonth['profit'] ?? 0, 2) }}</td>
                                <td>{{ number_format($lastMonth['margin'] ?? 0, 1) }}%</td>
                            </tr>
                        </tbody>
                    </table>
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
