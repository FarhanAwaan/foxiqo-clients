@extends('layouts.admin')

@section('title', 'Agents')

@section('page-pretitle')
    Management
@endsection

@section('page-header')
    AI Agents
@endsection

@section('page-actions')
    <a href="{{ route('admin.agents.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        Add Agent
    </a>
@endsection

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.agents.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, phone, agent ID..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ request('status') == 'paused' ? 'selected' : '' }}>Paused</option>
                            <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Agent Cards Grid -->
    @if($agents->count() > 0)
        <div class="row row-cards">
            @foreach($agents as $agent)
                <div class="col-md-6 col-lg-4">
                    <div class="card agent-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar avatar-lg bg-primary-lt me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M6 6a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -4"></path>
                                        <path d="M12 2v2"></path>
                                        <path d="M9 12v9"></path>
                                        <path d="M15 12v9"></path>
                                        <path d="M5 16l4 -2"></path>
                                        <path d="M15 14l4 2"></path>
                                        <path d="M9 18h6"></path>
                                        <path d="M10 8v.01"></path>
                                        <path d="M14 8v.01"></path>
                                    </svg>
                                </span>
                                <div class="flex-fill">
                                    <h3 class="card-title mb-1">{{ $agent->name }}</h3>
                                    <div class="text-muted small">
                                        <a href="{{ route('admin.companies.show', $agent->company) }}" class="text-reset">
                                            {{ $agent->company->name }}
                                        </a>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <a href="#" class="btn btn-icon btn-ghost-primary" data-bs-toggle="dropdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('admin.agents.show', $agent) }}" class="dropdown-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            View Details
                                        </a>
                                        <a href="{{ route('admin.agents.edit', $agent) }}" class="dropdown-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                            Edit Agent
                                        </a>
                                        @if($agent->subscription)
                                            <a href="{{ route('admin.subscriptions.show', $agent->subscription) }}" class="dropdown-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
                                                View Subscription
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Status & Phone -->
                            <div class="mb-3">
                                @switch($agent->status)
                                    @case('active')
                                        <span class="badge bg-green-lt">Active</span>
                                        @break
                                    @case('paused')
                                        <span class="badge bg-yellow-lt">Paused</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">Archived</span>
                                @endswitch

                                @if($agent->subscription)
                                    @switch($agent->subscription->status)
                                        @case('active')
                                            <span class="badge bg-green-lt">Subscribed</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-yellow-lt">Pending</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary-lt">{{ ucfirst($agent->subscription->status) }}</span>
                                    @endswitch
                                @else
                                    <span class="badge bg-red-lt">No Plan</span>
                                @endif

                                @if($agent->phone_number)
                                    <span class="text-muted ms-2 small">{{ $agent->phone_number }}</span>
                                @endif
                            </div>

                            <!-- Stats Row -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="agent-stat">
                                        <div class="agent-stat-value">{{ number_format($agent->call_logs_count ?? 0) }}</div>
                                        <div class="agent-stat-label">Total Calls</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="agent-stat">
                                        <div class="agent-stat-value">{{ number_format($agent->call_logs_sum_duration_minutes ?? 0, 1) }}</div>
                                        <div class="agent-stat-label">Minutes</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="agent-stat">
                                        <div class="agent-stat-value">${{ number_format($agent->cost_per_minute, 2) }}</div>
                                        <div class="agent-stat-label">Cost/Min</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Usage Bar (if subscription exists) -->
                            @if($agent->subscription && $agent->subscription->plan)
                                @php
                                    $used = $agent->subscription->minutes_used ?? 0;
                                    $included = $agent->subscription->plan->included_minutes ?? 1;
                                    $percent = min(100, ($used / $included) * 100);
                                    $usageClass = $percent > 90 ? 'usage-danger' : ($percent > 70 ? 'usage-warning' : 'usage-normal');
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $agent->subscription->plan->name }} Plan</span>
                                        <span class="text-muted">{{ number_format($used, 0) }} / {{ number_format($included) }} min</span>
                                    </div>
                                    <div class="usage-bar">
                                        <div class="usage-bar-fill {{ $usageClass }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Action Button -->
                            <a href="{{ route('admin.agents.show', $agent) }}" class="btn btn-primary w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z" /><path d="M9 9l0 6" /><path d="M15 9l0 6" /><path d="M9 12l6 0" /></svg>
                                View Calls
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $agents->links() }}
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="empty-state py-5">
                    <div class="empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" /></svg>
                    </div>
                    <p class="empty-state-title">No agents found</p>
                    <p class="empty-state-description">
                        @if(request()->hasAny(['search', 'company_id', 'status']))
                            No agents match your search criteria.
                        @else
                            Get started by adding your first AI agent.
                        @endif
                    </p>
                    <a href="{{ route('admin.agents.create') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        Add Agent
                    </a>
                </div>
            </div>
        </div>
    @endif
@endsection
