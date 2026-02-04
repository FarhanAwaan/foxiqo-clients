@extends('layouts.admin')

@section('title', $plan->name)

@section('page-pretitle')
    Plans
@endsection

@section('page-header')
    {{ $plan->name }}
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
            Edit Plan
        </a>
        <a href="{{ route('admin.subscriptions.create') }}?plan_id={{ $plan->id }}" class="btn btn-outline-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
            Create Subscription
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <!-- Plan Info Card -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl {{ $plan->is_custom ? 'bg-purple-lt' : 'bg-primary-lt' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                        </span>
                    </div>
                    <h2 class="mb-1">${{ number_format($plan->price, 2) }}<span class="text-muted fs-5">/mo</span></h2>
                    <p class="text-muted">{{ $plan->included_minutes }} minutes included</p>
                    <div class="mb-3">
                        @if($plan->is_custom)
                            <span class="badge bg-purple-lt">Custom Plan</span>
                        @else
                            <span class="badge bg-blue-lt">Standard Plan</span>
                        @endif
                        @if($plan->is_active)
                            <span class="badge bg-green-lt">Active</span>
                        @else
                            <span class="badge bg-secondary-lt">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-body border-top">
                    <div class="datagrid">
                        @if($plan->description)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Description</div>
                                <div class="datagrid-content">{{ $plan->description }}</div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Monthly Price</div>
                            <div class="datagrid-content text-money">${{ number_format($plan->price, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Included Minutes</div>
                            <div class="datagrid-content">{{ number_format($plan->included_minutes) }} min</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Overage Rate</div>
                            <div class="datagrid-content">
                                @if($plan->overage_rate)
                                    ${{ number_format($plan->overage_rate, 4) }}/min
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </div>
                        </div>
                        @if($plan->is_custom && $plan->company)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Company</div>
                                <div class="datagrid-content">
                                    <a href="{{ route('admin.companies.show', $plan->company) }}">
                                        {{ $plan->company->name }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $plan->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Revenue Stats</h3>
                </div>
                <div class="card-body">
                    @php
                        $activeSubscriptions = $plan->subscriptions->where('status', 'active');
                        $totalMRR = $activeSubscriptions->sum(fn($s) => $s->custom_price ?? $plan->price);
                    @endphp
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h1 mb-0">{{ $activeSubscriptions->count() }}</div>
                                <div class="text-muted small">Active Subscriptions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h1 mb-0 text-green">${{ number_format($totalMRR, 0) }}</div>
                                <div class="text-muted small">Monthly Revenue</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Subscriptions using this plan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Subscriptions</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.subscriptions.create') }}?plan_id={{ $plan->id }}" class="btn btn-ghost-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                            Add Subscription
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Company</th>
                                <th>Price</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plan->subscriptions as $subscription)
                                <tr>
                                    <td>
                                        @if($subscription->agent)
                                            <a href="{{ route('admin.agents.show', $subscription->agent) }}" class="text-reset">
                                                {{ $subscription->agent->name }}
                                            </a>
                                            @if($subscription->agent->phone_number)
                                                <div class="text-muted small">{{ $subscription->agent->phone_number }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($subscription->agent && $subscription->agent->company)
                                            <a href="{{ route('admin.companies.show', $subscription->agent->company) }}" class="text-reset">
                                                {{ $subscription->agent->company->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-money">
                                        @if($subscription->custom_price)
                                            <span class="text-primary">${{ number_format($subscription->custom_price, 2) }}</span>
                                            <span class="text-muted small">(custom)</span>
                                        @else
                                            ${{ number_format($plan->price, 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $used = $subscription->minutes_used ?? 0;
                                            $included = $plan->included_minutes ?: 1;
                                            $percent = min(100, ($used / $included) * 100);
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <div class="usage-bar flex-fill me-2" style="height: 6px; width: 60px;">
                                                <div class="usage-bar-fill {{ $percent > 80 ? 'usage-warning' : 'usage-normal' }}" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <span class="small text-muted">{{ number_format($used, 0) }}/{{ number_format($included) }}</span>
                                        </div>
                                    </td>
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
                                    <td>
                                        <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-icon btn-ghost-primary btn-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No subscriptions using this plan yet
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
