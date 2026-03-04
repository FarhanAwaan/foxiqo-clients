@extends('layouts.customer')

@section('title', $agent->name)

@section('page-pretitle')
    Assistants
@endsection

@section('page-header')
    {{ $agent->name }}
@endsection

@section('page-actions')
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
        Back to Assistants
    </a>
@endsection

@section('content')

    {{-- Row 1: Agent Info (left) + Stats & Subscription (right) --}}
    <div class="row g-3 mb-3">

        {{-- Left: Agent Info --}}
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
                            @if($agent->phone_number)
                                <div class="text-muted small">{{ $agent->phone_number }}</div>
                            @endif
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
                    </div>
                    @if($agent->description)
                        <p class="text-muted small mb-0">{{ $agent->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Statistics + Current Plan stacked --}}
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

            {{-- Current Plan --}}
            @if($agent->subscription)
                @php
                    $used = $agent->subscription->minutes_used ?? 0;
                    $included = $agent->subscription->plan->included_minutes ?? 1;
                    $percent = min(100, ($used / $included) * 100);
                    $barColor = $percent > 90 ? 'bg-red' : ($percent > 70 ? 'bg-yellow' : 'bg-green');
                @endphp
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="card-title mb-1">
                                    {{ $agent->subscription->plan->name }}
                                    <span class="text-muted fw-normal fs-5 ms-1">${{ number_format($agent->subscription->custom_price ?? $agent->subscription->plan->price, 2) }}/mo</span>
                                </h3>
                                <div class="text-secondary small">
                                    @if($agent->subscription->isTrial())
                                        <span class="badge bg-purple-lt me-1">
                                            Free Trial — {{ $agent->subscription->trialDaysRemaining() }}d left
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
                        <h4 class="mb-2">No Active Plan</h4>
                        <p class="text-muted small mb-0">Contact your administrator to set up a subscription plan for this assistant.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Row 2: Recent Calls --}}
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
                        <a href="{{ route('customer.calls.index', $agent) }}" class="btn btn-outline-secondary btn-sm">
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
                            @include('customer.agents._recent_call_rows')
                        </tbody>
                    </table>
                </div>
                @if($callLogs->isNotEmpty())
                    <div class="card-footer text-center">
                        <a href="{{ route('customer.calls.index', $agent) }}" class="btn btn-link text-muted">
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
                        <div class="transcript-role">${isAgent ? 'Assistant' : 'Customer'}</div>
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
        `;
    }
});
</script>
@endpush
