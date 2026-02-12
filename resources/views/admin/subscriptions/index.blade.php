@extends('layouts.admin')

@section('title', 'Subscriptions')

@section('page-pretitle')
    Billing
@endsection

@section('page-header')
    Subscriptions
@endsection

@section('page-actions')
    <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        Add Subscription
    </a>
@endsection

@section('content')
    <!-- Stats Cards -->
    @php
        $activeCount = $subscriptions->where('status', 'active')->count();
        $pendingCount = $subscriptions->where('status', 'pending')->count();
        $cancelledCount = $subscriptions->where('status', 'cancelled')->count();
    @endphp
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Subscriptions</div>
                    </div>
                    <div class="h1 mb-0">{{ $subscriptions->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Active</div>
                    </div>
                    <div class="h1 mb-0 text-green">{{ $activeCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Pending Activation</div>
                    </div>
                    <div class="h1 mb-0 text-yellow">{{ $pendingCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Cancelled</div>
                    </div>
                    <div class="h1 mb-0 text-red">{{ $cancelledCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Subscriptions</h3>
            <div class="card-actions">
                <form action="{{ route('admin.subscriptions.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="company_id" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    @if(request('company_id') || request('status'))
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Assistant</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Usage</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
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
                                    <span class="text-muted">No agent</span>
                                @endif
                            </td>
                            <td>
                                @if($subscription->company)
                                    <a href="{{ route('admin.companies.show', $subscription->company) }}" class="text-reset">
                                        {{ $subscription->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($subscription->plan)
                                    <a href="{{ route('admin.plans.show', $subscription->plan) }}" class="text-reset">
                                        {{ $subscription->plan->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-money">
                                @if($subscription->custom_price)
                                    <span class="text-primary">${{ number_format($subscription->custom_price, 2) }}</span>
                                    <span class="text-muted small">(custom)</span>
                                @elseif($subscription->plan)
                                    ${{ number_format($subscription->plan->price, 2) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($subscription->plan)
                                    @php
                                        $used = $subscription->minutes_used ?? 0;
                                        $included = $subscription->plan->included_minutes ?: 1;
                                        $percent = min(100, ($used / $included) * 100);
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <div class="usage-bar flex-fill me-2" style="height: 6px; width: 60px;">
                                            <div class="usage-bar-fill {{ $percent > 90 ? 'usage-danger' : ($percent > 70 ? 'usage-warning' : 'usage-normal') }}" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span class="small text-muted">{{ number_format($used, 0) }}/{{ number_format($included) }}</span>
                                    </div>
                                    @if($subscription->circuit_breaker_triggered)
                                        <span class="badge bg-red-lt mt-1">Circuit Breaker</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($subscription->current_period_start && $subscription->current_period_end)
                                    <span class="small">
                                        {{ $subscription->current_period_start->format('M d') }} - {{ $subscription->current_period_end->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-muted small">Not started</span>
                                @endif
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
                                    @case('expired')
                                        <span class="badge bg-secondary-lt">Expired</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($subscription->status) }}</span>
                                @endswitch
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-ghost-primary btn-md" data-bs-toggle="dropdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.subscriptions.show', $subscription) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            View Details
                                        </a>
                                        @if($subscription->status === 'pending')
                                            <form action="{{ route('admin.subscriptions.activate', $subscription) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-green">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-green" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                    Activate
                                                </button>
                                            </form>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('admin.subscriptions.edit', $subscription) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                            Edit
                                        </a>
                                        @if($subscription->status === 'active')
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $subscription->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                                Cancel Subscription
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>
                                    </div>
                                    <p class="empty-state-title">No subscriptions found</p>
                                    <p class="empty-state-description">Get started by creating a subscription for an agent.</p>
                                    <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        Add Subscription
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subscriptions->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $subscriptions->firstItem() }}</span> to <span>{{ $subscriptions->lastItem() }}</span> of <span>{{ $subscriptions->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $subscriptions->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Cancel Modals -->
    @foreach($subscriptions->where('status', 'active') as $subscription)
        <div class="modal modal-blur fade" id="cancelModal{{ $subscription->id }}" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Cancel Subscription</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel the subscription for <strong>{{ $subscription->agent?->name }}</strong>?</p>
                            <div class="mb-3">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Enter cancellation reason..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                                Keep Subscription
                            </button>
                            <button type="submit" class="btn btn-danger ms-auto">
                                Cancel Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
