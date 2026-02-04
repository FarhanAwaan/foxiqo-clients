@extends('layouts.customer')

@section('title', 'My Subscriptions')

@section('page-header')
    My Subscriptions
@endsection

@section('content')
<!-- Stats Cards -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Subscriptions</div>
                </div>
                <div class="h1 mb-0">{{ $stats['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active</div>
                </div>
                <div class="h1 mb-0 text-success">{{ $stats['active'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending</div>
                </div>
                <div class="h1 mb-0 text-warning">{{ $stats['pending'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Monthly Total</div>
                </div>
                <div class="h1 mb-0">${{ number_format($stats['monthly_total'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('customer.subscriptions.index') }}" class="row g-3 align-items-end">
            <div class="col-auto">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" style="min-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['status']))
                    <a href="{{ route('customer.subscriptions.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Subscriptions List -->
<div class="row row-cards">
    @forelse($subscriptions as $subscription)
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $subscription->agent->name ?? 'Unknown Agent' }}
                    </h3>
                    <div class="card-actions">
                        @switch($subscription->status)
                            @case('active')
                                <span class="badge bg-primary text-white">Active</span>
                                @break
                            @case('pending')
                                <span class="badge bg-warning text-white">Pending</span>
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
                            <div class="datagrid-title">Plan</div>
                            <div class="datagrid-content">{{ $subscription->plan->name ?? 'N/A' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Monthly Price</div>
                            <div class="datagrid-content fw-bold">${{ number_format($subscription->getEffectivePrice(), 2) }}</div>
                        </div>
                        @if($subscription->status === 'active')
                            <div class="datagrid-item">
                                <div class="datagrid-title">Current Period</div>
                                <div class="datagrid-content">
                                    {{ $subscription->current_period_start?->format('M d') }} - {{ $subscription->current_period_end?->format('M d') }}
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Minutes Used</div>
                                <div class="datagrid-content">
                                    {{ number_format($subscription->minutes_used ?? 0, 1) }} / {{ number_format($subscription->plan->included_minutes ?? 0) }} min
                                </div>
                            </div>
                        @endif
                        @if($subscription->activated_at)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Started</div>
                                <div class="datagrid-content">{{ $subscription->activated_at->format('M d, Y') }}</div>
                            </div>
                        @endif
                    </div>

                    @if($subscription->status === 'active' && $subscription->plan)
                        <div class="mt-3">
                            <div class="progress progress-sm">
                                @php
                                    $usagePercent = $subscription->plan->included_minutes > 0
                                        ? min(100, ($subscription->minutes_used / $subscription->plan->included_minutes) * 100)
                                        : 0;
                                @endphp
                                <div class="progress-bar {{ $usagePercent > 80 ? 'bg-danger' : ($usagePercent > 50 ? 'bg-warning' : 'bg-success') }}"
                                     style="width: {{ $usagePercent }}%"
                                     role="progressbar"
                                     aria-valuenow="{{ $usagePercent }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">{{ round($usagePercent, 1) }}% of minutes used</small>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('customer.subscriptions.show', $subscription) }}" class="btn btn-outline-primary w-100">
                        View Details
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                    <h3>No subscriptions found</h3>
                    <p class="text-muted">You don't have any subscriptions yet.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

@if($subscriptions->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $subscriptions->links() }}
    </div>
@endif
@endsection
