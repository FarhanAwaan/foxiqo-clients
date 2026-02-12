@extends('layouts.customer')

@section('title', 'My Assistants')

@section('page-pretitle')
    Dashboard
@endsection

@section('page-header')
    My AI Assistants
@endsection

@section('content')
    <!-- Agent Cards Grid -->
    @if($agents->count() > 0)
        <div class="row row-cards">
            @foreach($agents as $agent)
                <div class="col-md-6 col-lg-4">
                    <div class="card agent-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar avatar-lg bg-primary-lt me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-lg">
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
                                <div class="flex-fill" style="min-width: 0;">
                                    <h3 class="card-title mb-1 text-truncate" title="{{ $agent->name }}">{{ $agent->name }}</h3>
                                    @if($agent->phone_number)
                                        <div class="text-muted small">{{ $agent->phone_number }}</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Agent Status & Type -->
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

                                @switch($agent->agent_type)
                                    @case('inbound')
                                        <span class="badge bg-blue-lt">Inbound</span>
                                        @break
                                    @case('outbound')
                                        <span class="badge bg-cyan-lt">Outbound</span>
                                        @break
                                    @default
                                        <span class="badge bg-purple-lt">Both</span>
                                @endswitch

                                @if($agent->subscription)
                                    <span class="badge bg-primary-lt">{{ $agent->subscription->plan->name }}</span>
                                @else
                                    <span class="badge bg-red-lt">No Plan</span>
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
                                        @if($agent->subscription && $agent->subscription->plan)
                                            @php
                                                $remaining = max(0, $agent->subscription->plan->included_minutes - ($agent->subscription->minutes_used ?? 0));
                                            @endphp
                                            <div class="agent-stat-value text-green">{{ number_format($remaining) }}</div>
                                        @else
                                            <div class="agent-stat-value text-muted">-</div>
                                        @endif
                                        <div class="agent-stat-label">Remaining</div>
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
                                        <span>Usage</span>
                                        <span class="text-muted">{{ number_format($used, 0) }} / {{ number_format($included) }} min</span>
                                    </div>
                                    <div class="usage-bar">
                                        <div class="usage-bar-fill {{ $usageClass }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Action Button (pushed to bottom) -->
                            <div class="mt-auto">
                                <a href="{{ route('customer.agents.show', $agent) }}" class="btn btn-primary w-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z" /><path d="M9 9l0 6" /><path d="M15 9l0 6" /><path d="M9 12l6 0" /></svg>
                                    View Calls
                                </a>
                            </div>
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
                    <p class="empty-state-title">No assistants configured</p>
                    <p class="empty-state-description">
                        Your AI assistants will appear here once they're set up.<br>
                        Please contact your administrator to get started.
                    </p>
                </div>
            </div>
        </div>
    @endif

<style>
    .agent-card {
        height: 320px;
    }
    .agent-stat {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 8px 4px;
        text-align: center;
    }
    .agent-stat-value {
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .agent-stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 2px;
    }
    .usage-bar {
        height: 6px;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    .usage-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s ease;
    }
    .usage-bar-fill.usage-normal {
        background-color: #206bc4;
    }
    .usage-bar-fill.usage-warning {
        background-color: #f59f00;
    }
    .usage-bar-fill.usage-danger {
        background-color: #d63939;
    }
</style>
@endsection
