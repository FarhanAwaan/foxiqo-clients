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
    <div class="card-body px-2 py-3">
        <form method="GET" action="{{ route('customer.subscriptions.index') }}" class="row g-3 align-items-end">
            <div class="col-auto">
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
        @php
            $usagePercent = ($subscription->plan && $subscription->plan->included_minutes > 0)
                ? min(100, ($subscription->minutes_used / $subscription->plan->included_minutes) * 100)
                : 0;
            $remaining = max(0, ($subscription->plan->included_minutes ?? 0) - ($subscription->minutes_used ?? 0));
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card subscription-card">
                <div class="card-body d-flex flex-column">
                    <!-- Header: Avatar + Name + Price -->
                    <div class="d-flex align-items-center mb-3">
                        <span class="avatar avatar-lg bg-primary-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                        </span>
                        <div class="flex-fill" style="min-width: 0;">
                            <h3 class="card-title mb-1 text-truncate" title="{{ $subscription->agent->name ?? 'Unknown' }}">{{ $subscription->agent->name ?? 'Unknown' }}</h3>
                            <div class="text-muted small">${{ number_format($subscription->getEffectivePrice(), 2) }}/mo</div>
                        </div>
                    </div>

                    <!-- Badges: Status + Plan -->
                    <div class="mb-3">
                        @switch($subscription->status)
                            @case('active')
                                <span class="badge bg-green-lt">Active</span>
                                @break
                            @case('pending')
                                <span class="badge bg-yellow-lt">Pending</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-secondary-lt">Cancelled</span>
                                @break
                            @default
                                <span class="badge bg-secondary-lt">{{ ucfirst($subscription->status) }}</span>
                        @endswitch
                        <span class="badge bg-primary-lt">{{ $subscription->plan->name ?? 'N/A' }}</span>
                    </div>

                    <!-- Stats Row with Background -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="subscription-stat">
                                <div class="subscription-stat-value">
                                    <span class="text-primary" style="font-size: 1.25rem; font-weight: 600;">{{ number_format($subscription->minutes_used ?? 0, 0) }}</span><span class="text-muted" style="font-size: 0.8rem;">/{{ number_format($subscription->plan->included_minutes ?? 0) }}</span>
                                </div>
                                <div class="subscription-stat-label">Used</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="subscription-stat">
                                <div class="subscription-stat-value">
                                    <span class="text-green" style="font-size: 1.25rem; font-weight: 600;">{{ number_format($remaining) }}</span><span class="text-muted" style="font-size: 0.8rem;"> min</span>
                                </div>
                                <div class="subscription-stat-label">Remaining</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="subscription-stat">
                                <div class="subscription-stat-value">
                                    <span style="font-size: 1.25rem; font-weight: 600;">{{ round($usagePercent, 0) }}</span><span class="text-muted" style="font-size: 0.8rem;">%</span>
                                </div>
                                <div class="subscription-stat-label">Usage</div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Bar -->
                    @if($subscription->plan)
                        <div class="mb-3">
                            <div class="progress progress-sm" style="height: 6px;">
                                <div class="progress-bar {{ $usagePercent > 80 ? 'bg-danger' : ($usagePercent > 50 ? 'bg-warning' : 'bg-primary') }}"
                                     style="width: {{ $usagePercent }}%"
                                     role="progressbar">
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Period Dates -->
                    @if($subscription->status === 'active' && $subscription->current_period_start && $subscription->current_period_end)
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span>{{ $subscription->current_period_start->format('M d, Y') }} — {{ $subscription->current_period_end->format('M d, Y') }}</span>
                        </div>
                    @endif

                    <!-- Action Button (pushed to bottom) -->
                    <div class="mt-auto">
                        <a href="{{ route('customer.subscriptions.show', $subscription) }}" class="btn btn-primary w-100">
                            View Details
                        </a>
                    </div>
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

<style>
    .subscription-card {
        height: 320px;
    }
    .subscription-stat {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 8px 4px;
        text-align: center;
    }
    .subscription-stat-value {
        line-height: 1.2;
    }
    .subscription-stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 2px;
    }

    .subscription-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--tblr-primary);
    }
</style>
@endsection
