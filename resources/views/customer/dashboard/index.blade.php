@extends('layouts.customer')

@section('title', 'Dashboard')

@section('page-pretitle')
    Welcome back, {{ auth()->user()->first_name }}
@endsection

@section('page-header')
    Dashboard
@endsection

@section('content')
    @php
        $usagePercent = $totalMinutesIncluded > 0 ? min(100, ($totalMinutesUsed / $totalMinutesIncluded) * 100) : null;
    @endphp

    <!-- Hero Metric: Total Calls Handled -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-auto">
                    <div class="subheader mb-1">Total Calls Handled</div>
                    <div class="hero-metric">{{ number_format($totalCallsCount) }}</div>
                    <div class="text-muted mt-2">Across {{ $agents->count() }} {{ Str::plural('assistant', $agents->count()) }}</div>
                </div>

                <div class="col-12 col-lg-auto d-none d-lg-block">
                    <div class="hero-metric-divider"></div>
                </div>

                <div class="col-12 col-lg">
                    <div class="row g-4">
                        <div class="col-6 col-md-4">
                            <div class="subheader">Active Assistants</div>
                            <div class="hero-metric-secondary">{{ $activeSubscriptions }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="subheader">Minutes Used</div>
                            <div class="hero-metric-secondary">{{ number_format($totalMinutesUsed, 1) }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="subheader">Plan Usage</div>
                            @if($usagePercent !== null)
                                <div class="hero-metric-secondary {{ $usagePercent > 80 ? 'text-warning' : '' }}">{{ number_format($usagePercent, 0) }}%</div>
                                <div class="progress progress-sm mt-1">
                                    <div class="progress-bar {{ $usagePercent > 80 ? 'bg-warning' : 'bg-primary' }}" style="width: {{ $usagePercent }}%"></div>
                                </div>
                            @else
                                <div class="hero-metric-secondary text-muted">--</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-deck row-cards">
        <!-- Agents Overview -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My Agents</h3>
                    <div class="card-actions">
                        <a href="{{ route('customer.agents.index') }}" class="btn btn-primary btn-sm">View All</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Plan</th>
                                <th>Usage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agents->take(5) as $agent)
                                <tr>
                                    <td>
                                        <a href="{{ route('customer.agents.show', $agent) }}" class="text-reset">
                                            {{ $agent->name }}
                                        </a>
                                    </td>
                                    <td class="text-muted">{{ $agent->subscription?->plan?->name ?? '-' }}</td>
                                    <td>
                                        @if($agent->subscription && $agent->subscription->plan)
                                            @php
                                                $used = $agent->subscription->minutes_used ?? 0;
                                                $included = $agent->subscription->plan->included_minutes ?? 1;
                                                $percent = min(100, ($used / $included) * 100);
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <div class="usage-bar flex-fill me-2" style="height: 6px;">
                                                    <div class="usage-bar-fill {{ $percent > 80 ? 'usage-warning' : 'usage-normal' }}" style="width: {{ $percent }}%"></div>
                                                </div>
                                                <span class="small text-muted">{{ number_format($percent, 0) }}%</span>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($agent->subscription)
                                            @switch($agent->subscription->status)
                                                @case('active')
                                                    <span class="badge bg-green-lt">Active</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge bg-yellow-lt">Pending</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary-lt">{{ ucfirst($agent->subscription->status) }}</span>
                                            @endswitch
                                        @else
                                            <span class="badge bg-secondary-lt">No Plan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No agents configured yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Calls -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Calls</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Duration</th>
                                <th>Sentiment</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCalls as $call)
                                <tr>
                                    <td>
                                        <a href="{{ route('customer.agents.show', $call->agent) }}" class="text-reset">
                                            {{ $call->agent->name ?? 'Unknown' }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($call->duration_seconds)
                                            {{ gmdate('i:s', $call->duration_seconds) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($call->sentiment)
                                            @case('positive')
                                                <span class="badge bg-green-lt">Positive</span>
                                                @break
                                            @case('negative')
                                                <span class="badge bg-red-lt">Negative</span>
                                                @break
                                            @case('neutral')
                                                <span class="badge bg-secondary-lt">Neutral</span>
                                                @break
                                            @default
                                                <span class="text-muted">-</span>
                                        @endswitch
                                    </td>
                                    <td class="text-muted">
                                        {{ $call->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No calls yet
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
