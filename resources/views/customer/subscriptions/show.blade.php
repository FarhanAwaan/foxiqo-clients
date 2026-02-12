@extends('layouts.customer')

@section('title', 'Subscription - ' . ($subscription->agent->name ?? 'Details'))

@section('page-header')
    Subscription Details
@endsection

@section('content')
<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Subscription Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Subscription Information</h3>
                <div class="card-actions">
                    @switch($subscription->status)
                        @case('active')
                            <span class="badge bg-primary text-white">Active</span>
                            @break
                        @case('pending')
                            <span class="badge bg-warning text-white">Pending Activation</span>
                            @break
                        @case('cancelled')
                            <span class="badge bg-secondary text-white">Cancelled</span>
                            @break
                        @default
                            <span class="badge bg-secondary text-white">{{ ucfirst($subscription->status) }}</span>
                    @endswitch
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Assistant</div>
                        <div class="datagrid-content">
                            <a href="{{ route('customer.agents.show', $subscription->agent) }}">
                                {{ $subscription->agent->name ?? 'N/A' }}
                            </a>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Plan</div>
                        <div class="datagrid-content">{{ $subscription->plan->name ?? 'N/A' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Monthly Price</div>
                        <div class="datagrid-content">
                            <span class="h4 mb-0">${{ number_format($subscription->getEffectivePrice(), 2) }}</span>
                            @if($subscription->custom_price)
                                <span class="text-muted small">(Custom)</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Included Minutes</div>
                        <div class="datagrid-content">{{ number_format($subscription->plan->included_minutes ?? 0) }} min/month</div>
                    </div>
                    @if($subscription->activated_at)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Started</div>
                            <div class="datagrid-content">{{ $subscription->activated_at->format('M d, Y') }}</div>
                        </div>
                    @endif
                    @if($subscription->cancelled_at)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Cancelled</div>
                            <div class="datagrid-content text-danger">{{ $subscription->cancelled_at->format('M d, Y') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Current Period (Active subscriptions only) -->
        @if($subscription->status === 'active')
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Current Billing Period</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <div class="text-muted small">Period</div>
                            <div class="fw-bold">
                                {{ $subscription->current_period_start?->format('M d, Y') }} - {{ $subscription->current_period_end?->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Next Renewal</div>
                            <div class="fw-bold">{{ $subscription->current_period_end?->addDay()->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <!-- Usage Progress -->
                    <h4 class="mb-3">Minutes Usage</h4>
                    @php
                        $usagePercent = $subscription->plan && $subscription->plan->included_minutes > 0
                            ? min(100, ($subscription->minutes_used / $subscription->plan->included_minutes) * 100)
                            : 0;
                    @endphp
                    <div class="progress progress-lg mb-2">
                        <div class="progress-bar {{ $usagePercent > 80 ? 'bg-danger' : ($usagePercent > 50 ? 'bg-warning' : 'bg-success') }}"
                             style="width: {{ $usagePercent }}%"
                             role="progressbar">
                            {{ round($usagePercent, 1) }}%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>{{ number_format($subscription->minutes_used ?? 0, 1) }} minutes used</span>
                        <span>{{ number_format($subscription->plan->included_minutes ?? 0) }} minutes included</span>
                    </div>

                    @if($subscription->circuit_breaker_triggered)
                        <div class="alert alert-warning mt-3 mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                            <strong>Usage Limit Reached</strong> - You've used all your included minutes for this period. Please contact support to increase your plan limits.
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Recent Invoices -->
        @if($invoices->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Recent Invoices</h3>
                    <div class="card-actions">
                        <a href="{{ route('customer.invoices.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Period</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->billing_period_start?->format('M d') }} - {{ $invoice->billing_period_end?->format('M d, Y') }}</td>
                                    <td>${{ number_format($invoice->amount, 2) }}</td>
                                    <td>
                                        @switch($invoice->status)
                                            @case('paid')
                                                <span class="badge bg-primary text-white">Paid</span>
                                                @break
                                            @case('sent')
                                                <span class="badge bg-info text-white">Pending</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary text-white">{{ ucfirst($invoice->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <a href="{{ route('customer.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Billing History -->
        @if($billingCycles->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Usage History</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Plan</th>
                                <th>Minutes Used</th>
                                <th>Calls</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($billingCycles as $cycle)
                                <tr>
                                    <td>{{ $cycle->period_start?->format('M d') }} - {{ $cycle->period_end?->format('M d, Y') }}</td>
                                    <td>{{ $cycle->plan_name }}</td>
                                    <td>{{ number_format($cycle->minutes_used, 1) }} / {{ number_format($cycle->included_minutes) }}</td>
                                    <td>{{ number_format($cycle->total_calls) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Plan Details -->
        @if($subscription->plan)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Plan Details</h3>
                </div>
                <div class="card-body">
                    <h3 class="mb-3">{{ $subscription->plan->name }}</h3>
                    <ul class="list-unstyled space-y-2">
                        <li class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-success me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                            {{ number_format($subscription->plan->included_minutes) }} minutes/month
                        </li>
                        @if($subscription->plan->description)
                            <li class="d-flex align-items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-success me-2 mt-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                <span class="text-muted">{{ $subscription->plan->description }}</span>
                            </li>
                        @endif
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Monthly</span>
                        <span class="h3 mb-0">${{ number_format($subscription->getEffectivePrice(), 2) }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Assistant Quick Link -->
        @if($subscription->agent)
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="avatar avatar-md bg-primary-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 6a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -4"></path>
                                <path d="M12 2v2"></path>
                                <path d="M9 12v9"></path>
                                <path d="M15 12v9"></path>
                            </svg>
                        </span>
                        <div>
                            <div class="fw-bold">{{ $subscription->agent->name }}</div>
                            <div class="text-muted small">Voice AI Assistant</div>
                        </div>
                    </div>
                    <a href="{{ route('customer.agents.show', $subscription->agent) }}" class="btn btn-outline-primary w-100">
                        View Assistant Details
                    </a>
                </div>
            </div>
        @endif

        <!-- Need Help -->
        <div class="card">
            <div class="card-body text-center">
                <h4>Need Help?</h4>
                <p class="text-muted small">Questions about your subscription? Contact our support team.</p>
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}" class="btn btn-outline-primary">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
