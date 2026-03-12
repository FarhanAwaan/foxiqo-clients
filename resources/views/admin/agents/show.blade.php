@extends('layouts.admin')

@section('title', $agent->name)

@section('page-pretitle')
    Assistants
@endsection

@section('page-header')
    {{ $agent->name }}
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
            Edit Assistant
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            Back to List
        </a>
    </div>
@endsection

@section('content')

    {{-- Row 1: Agent Info (left) + Stats & Subscription (right) --}}
    <div class="row g-3 mb-3">

        {{-- Left: Agent Info + Webhook URL --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="avatar avatar-md bg-primary-lt me-3 flex-shrink-0">
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
                        <div class="flex-fill" style="min-width: 0;">
                            <h3 class="card-title mb-1 text-truncate">{{ $agent->name }}</h3>
                            <div class="text-muted small">
                                <a href="{{ route('admin.companies.show', $agent->company) }}" class="text-reset">{{ $agent->company->name }}</a>
                            </div>
                        </div>
                    </div>
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
                        @if($agent->phone_number)
                            <span class="text-muted small ms-1">{{ $agent->phone_number }}</span>
                        @endif
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Cost / Min</div>
                                <div class="datagrid-content text-money">${{ number_format($agent->cost_per_minute, 4) }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Created</div>
                                <div class="datagrid-content">{{ $agent->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Retell Agent ID</div>
                                <div class="datagrid-content">
                                    <code class="small text-truncate" title="{{ $agent->retell_agent_id }}">
                                        {{ $agent->retell_agent_id }}
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Webhook URL --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Webhook URL</h3>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $agent->getWebhookUrl() }}" id="webhook-url" readonly>
                        <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('webhook-url', event)" title="Copy URL">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                        </button>
                    </div>
                    <div class="form-hint mt-2">Use this URL in Retell as the webhook endpoint for this assistant.</div>
                </div>
            </div>
        </div>

        {{-- Right: Statistics + Subscription stacked --}}
        <div class="col-lg-8">
            {{-- Statistics --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row row-cols-2 row-cols-sm-5 g-3 text-center">
                        <div class="col">
                            <div class="subheader">Total Calls</div>
                            <div class="h2 text-primary mb-0 mt-1">{{ number_format($totalCalls) }}</div>
                        </div>
                        <div class="col">
                            <div class="subheader">Total Minutes</div>
                            <div class="h2 text-green mb-0 mt-1">{{ number_format($totalMinutes, 1) }}</div>
                        </div>
                        <div class="col">
                            <div class="subheader">Inbound</div>
                            <div class="h2 text-blue mb-0 mt-1">{{ number_format($inboundCalls) }}</div>
                        </div>
                        <div class="col">
                            <div class="subheader">Outbound</div>
                            <div class="h2 text-cyan mb-0 mt-1">{{ number_format($outboundCalls) }}</div>
                        </div>
                        <div class="col">
                            <div class="subheader">Avg Duration</div>
                            <div class="h2 mb-0 mt-1">{{ gmdate("i:s", (int)$avgDuration) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subscription --}}
            @if($agent->subscription)
                @php
                    $used = $agent->subscription->minutes_used ?? 0;
                    $included = $agent->subscription->plan->included_minutes ?? 1;
                    $percent = min(100, ($used / $included) * 100);
                    $barColor = $percent > 90 ? 'bg-red' : ($percent > 70 ? 'bg-yellow' : 'bg-green');
                @endphp
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Subscription</h3>
                        <div class="card-actions">
                            <a href="{{ route('admin.subscriptions.show', $agent->subscription) }}" class="btn btn-ghost-primary btn-sm">
                                View
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="card-title mb-1">{{ $agent->subscription->plan->name }}</h3>
                                <div class="text-secondary small">
                                    @if($agent->subscription->isTrial())
                                        <span class="badge bg-purple-lt me-1">
                                            Trial — {{ $agent->subscription->trialDaysRemaining() }}d left
                                        </span>
                                        Ends {{ $agent->subscription->trial_ends_at?->format('M d, Y') ?? '-' }}
                                    @else
                                        @switch($agent->subscription->status)
                                            @case('active')
                                                <span class="badge bg-green-lt me-1">Active</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-yellow-lt me-1">Pending</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary-lt me-1">{{ ucfirst($agent->subscription->status) }}</span>
                                        @endswitch
                                        Renews {{ $agent->subscription->current_period_end?->format('M d, Y') ?? '-' }}
                                    @endif
                                </div>
                                <div class="mt-3">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto text-secondary small">
                                            {{ number_format($used, 0) }} / {{ number_format($included) }} min
                                        </div>
                                        <div class="col">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar {{ $barColor }}" style="width: {{ $percent }}%" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                                    <span class="visually-hidden">{{ round($percent) }}% Used</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto text-secondary small">
                                            {{ round($percent) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card bg-yellow-lt">
                    <div class="card-body">
                        <h4 class="mb-2">No Subscription</h4>
                        <p class="text-muted mb-2">This assistant doesn't have an active subscription plan.</p>
                        <a href="{{ route('admin.subscriptions.create') }}?agent_id={{ $agent->uuid }}" class="btn btn-warning btn-sm">
                            Add Subscription
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Row 2: Charts ──────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Date Range Picker --}}
        <div class="col-12" id="chartRangeContainer">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="text-muted medium fw-medium me-1">Performance Range:</span>
                        <div class="btn-group btn-group-xs" role="group">
                            <button type="button" class="btn btn-outline-secondary chart-range-btn" data-range="today">Today</button>
                            <button type="button" class="btn btn-outline-secondary chart-range-btn" data-range="yesterday">Yesterday</button>
                            <button type="button" class="btn btn-primary chart-range-btn" data-range="last7">Last 7 Days</button>
                            <button type="button" class="btn btn-outline-secondary chart-range-btn" data-range="last30">Last 30 Days</button>
                            <button type="button" class="btn btn-outline-secondary chart-range-btn" data-range="custom">Custom</button>
                        </div>
                        <div class="chart-custom-range d-none d-flex align-items-center gap-2">
                            <input type="date" class="form-control form-control-sm chart-from-date" style="width:145px;">
                            <span class="text-muted small">to</span>
                            <input type="date" class="form-control form-control-sm chart-to-date" style="width:145px;">
                            <button class="btn btn-sm btn-success chart-apply-range">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart cards with loading overlay --}}
        <div class="col-12">
            <div id="agentChartsContainer" style="position:relative;min-height:240px;">

                {{-- Loading overlay (covers only the chart cards) --}}
                <div id="agentChartsOverlay"
                     style="position:absolute;inset:0;z-index:20;background:rgba(248,250,252,0.93);
                            display:flex;flex-direction:column;align-items:center;justify-content:center;
                            gap:8px;border-radius:6px;">
                    <div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted small">Loading performance data&hellip;</span>
                </div>

                <div class="row g-3">
                    {{-- Call Volume --}}
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">Call Volume</h3>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height:200px;position:relative;">
                                    <canvas id="callVolumeChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sentiment --}}
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">Sentiment Breakdown</h3>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height:200px;position:relative;">
                                    <canvas id="sentimentChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- Row 3: Recent Calls --}}
    <div class="row">
        <div class="col-12">
            <div class="card position-relative" id="recentCallsCard">

                {{-- Refresh overlay --}}
                <div id="recentCallsOverlay" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.82); z-index: 10; border-radius: var(--tblr-card-border-radius, 4px);">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-2" style="width: 2.5rem; height: 2.5rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="text-muted small fw-medium">Fetching latest calls...</div>
                    </div>
                </div>

                <div class="card-header">
                    <h3 class="card-title">Recent Calls</h3>
                    <div class="card-actions d-flex align-items-center gap-2">
                        <button type="button" id="refreshRecentCallsBtn" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" id="refreshRecentBtnIcon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                            <span id="refreshRecentBtnText">Fetch Latest</span>
                        </button>
                        <a href="{{ route('admin.agents.calls.index', $agent) }}" class="btn btn-outline-secondary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                            All Calls
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th class="d-none d-sm-table-cell">Direction</th>
                                <th>From / To</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody id="recentCallsTableBody">
                            @include('admin.agents._recent_call_rows')
                        </tbody>
                    </table>
                </div>
                @if($callLogs->isNotEmpty())
                    <div class="card-footer text-center">
                        <a href="{{ route('admin.agents.calls.index', $agent) }}" class="btn btn-link text-muted">
                            View all calls &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Call Details Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="callDetailsOffcanvas" style="width: min(500px, 100vw);">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Call Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" id="callDetailsContent">
            <div class="text-center py-5" id="callDetailsLoader">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2">Loading call details...</p>
            </div>
            <div id="callDetailsData" class="d-none">
                <!-- Content loaded via JS -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader        = document.getElementById('callDetailsLoader');
    const dataContainer = document.getElementById('callDetailsData');
    const tbody         = document.getElementById('recentCallsTableBody');
    const refreshBtn    = document.getElementById('refreshRecentCallsBtn');
    const refreshText   = document.getElementById('refreshRecentBtnText');
    const refreshIcon   = document.getElementById('refreshRecentBtnIcon');
    const overlay       = document.getElementById('recentCallsOverlay');

    const MIN_SPINNER_MS = 1500;

    // --- Refresh recent calls ---
    refreshBtn.addEventListener('click', async function() {
        const started = Date.now();
        refreshBtn.disabled = true;
        refreshText.textContent = 'Fetching...';
        refreshIcon.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l0 -3" /><path d="M16.25 7.75l2.15 -2.15" /><path d="M18 12l3 0" /><path d="M16.25 16.25l2.15 2.15" /><path d="M12 18l0 3" /><path d="M7.75 16.25l-2.15 2.15" /><path d="M6 12l-3 0" /><path d="M7.75 7.75l-2.15 -2.15" />';
        overlay.classList.remove('d-none');

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('refresh', '1');
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Request failed');
            const data = await response.json();

            const elapsed = Date.now() - started;
            if (elapsed < MIN_SPINNER_MS) await new Promise(r => setTimeout(r, MIN_SPINNER_MS - elapsed));

            tbody.innerHTML = data.rows_html;
            bindViewCallButtons();
        } catch (e) {
            const elapsed = Date.now() - started;
            if (elapsed < MIN_SPINNER_MS) await new Promise(r => setTimeout(r, MIN_SPINNER_MS - elapsed));
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to fetch calls. Please try again.</td></tr>';
        } finally {
            overlay.classList.add('d-none');
            refreshBtn.disabled = false;
            refreshText.textContent = 'Fetch Latest';
            refreshIcon.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />';
        }
    });

    // --- Call details offcanvas ---
    function bindViewCallButtons() {
        document.querySelectorAll('.view-call-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                loadCallDetails(this.dataset.callUuid);
            });
        });
    }

    bindViewCallButtons();

    // --- Stop audio when offcanvas closes ---
    document.getElementById('callDetailsOffcanvas').addEventListener('hide.bs.offcanvas', function() {
        const audio = this.querySelector('audio');
        if (audio) { audio.pause(); audio.currentTime = 0; }
    });

    function loadCallDetails(callUuid) {
        loader.classList.remove('d-none');
        dataContainer.classList.add('d-none');

        fetch(`/calls/${callUuid}`)
            .then(response => response.json())
            .then(data => {
                renderCallDetails(data);
                loader.classList.add('d-none');
                dataContainer.classList.remove('d-none');
            })
            .catch(error => {
                console.error('Error:', error);
                dataContainer.innerHTML = '<div class="alert alert-danger">Failed to load call details</div>';
                loader.classList.add('d-none');
                dataContainer.classList.remove('d-none');
            });
    }

    function renderCallDetails(call) {
        let sentimentBadge = '';
        if (call.sentiment) {
            const sentimentColors = {
                'positive': 'bg-green-lt',
                'negative': 'bg-red-lt',
                'neutral': 'bg-secondary-lt'
            };
            sentimentBadge = `<span class="badge ${sentimentColors[call.sentiment] || 'bg-secondary-lt'}">${call.sentiment}</span>`;
        }

        let transcriptHtml = '';
        if (call.transcript && call.transcript.length > 0) {
            transcriptHtml = '<div class="transcript-container mt-3">';
            call.transcript.forEach(item => {
                const isAgent = item.speaker === 'agent';
                transcriptHtml += `
                    <div class="transcript-item ${isAgent ? 'transcript-agent' : 'transcript-user'}">
                        <div class="transcript-role">${isAgent ? 'Agent' : 'Customer'}</div>
                        <div class="transcript-text">${item.message || item.text || ''}</div>
                    </div>
                `;
            });
            transcriptHtml += '</div>';
        }

        let recordingHtml = '';
        if (call.recording_url) {
            recordingHtml = `
                <div class="mb-3">
                    <label class="form-label">Recording</label>
                    <audio controls class="w-100">
                        <source src="${call.recording_url}" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                </div>
            `;
        }

        dataContainer.innerHTML = `
            <div class="mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Direction</div>
                            <div class="datagrid-content">
                                <span class="badge ${call.direction === 'inbound' ? 'bg-blue-lt' : 'bg-green-lt'}">
                                    ${call.direction === 'inbound' ? 'Inbound' : 'Outbound'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Status</div>
                            <div class="datagrid-content">
                                <span class="badge bg-green-lt">${call.call_status || 'Unknown'}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">From</div>
                            <div class="datagrid-content">${call.from_number || '-'}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">To</div>
                            <div class="datagrid-content">${call.to_number || '-'}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Started</div>
                            <div class="datagrid-content">${call.started_at || '-'}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Duration</div>
                            <div class="datagrid-content text-money">${call.duration_formatted}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Cost</div>
                            <div class="datagrid-content text-money">$${parseFloat(call.retell_cost || 0).toFixed(4)}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Sentiment</div>
                            <div class="datagrid-content">${sentimentBadge || '-'}</div>
                        </div>
                    </div>
                </div>
            </div>

            ${recordingHtml}

            ${call.summary ? `
                <div class="mb-3">
                    <label class="form-label">Summary</label>
                    <div class="bg-light rounded p-3">
                        ${call.summary}
                    </div>
                </div>
            ` : ''}

            ${transcriptHtml ? `
                <div class="mb-3">
                    <label class="form-label">Transcript</label>
                    ${transcriptHtml}
                </div>
            ` : ''}

            <div class="text-muted small mt-4">
                <strong>Retell Call ID:</strong> <code>${call.retell_call_id || '-'}</code>
            </div>
        `;
    }
});
</script>

<script>
$(function () {
    var MIN_MS       = 1500;
    var $overlay     = $('#agentChartsOverlay');
    var urlVolume    = '{{ route("admin.agents.charts.call-volume", $agent) }}';
    var urlSentiment = '{{ route("admin.agents.charts.sentiment", $agent) }}';

    function loadCharts(params) {
        var loadStarted = Date.now();
        $overlay.stop(true, true).show();

        var p1 = DashboardCharts.loadCallVolumeChart(urlVolume,    'callVolumeChart', params);
        var p2 = DashboardCharts.loadSentimentChart(urlSentiment,  'sentimentChart',  params);

        $.when(p1, p2).always(function () {
            var elapsed = Date.now() - loadStarted;
            var delay   = Math.max(0, MIN_MS - elapsed);
            setTimeout(function () { $overlay.fadeOut(400); }, delay);
        });
    }

    DashboardCharts.initDateRangePicker({
        containerSelector: '#chartRangeContainer',
        onRefresh: function (params) { loadCharts(params); },
    });

    loadCharts({ range: 'last7' });
});
</script>
@endpush
