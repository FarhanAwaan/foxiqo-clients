@extends('layouts.customer')

@section('title', 'All Calls - ' . $agent->name)

@section('page-pretitle')
    <a href="{{ route('customer.agents.show', $agent) }}">{{ $agent->name }}</a>
@endsection

@section('page-header')
    All Calls
@endsection

@section('page-actions')
    <a href="{{ route('customer.agents.show', $agent) }}" class="btn btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
        Back to Assistant
    </a>
@endsection

@section('content')

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('customer.calls.index', $agent) }}" id="filtersForm">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="Search number..." value="{{ request('phone') }}">
                    </div>
                    <div class="col-sm-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                                Filter
                            </button>
                            @if(request()->hasAny(['from_date', 'to_date', 'phone']))
                                <a href="{{ route('customer.calls.index', $agent) }}" class="btn btn-outline-secondary">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Calls Table --}}
    <div class="card position-relative" id="callsCard">

        {{-- Refresh overlay --}}
        <div id="callsRefreshOverlay" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.82); z-index: 10; border-radius: var(--tblr-card-border-radius, 4px);">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" style="width: 2.5rem; height: 2.5rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-muted small fw-medium">Fetching latest calls...</div>
            </div>
        </div>

        <div class="card-header">
            <h3 class="card-title">Calls</h3>
            <div class="card-actions d-flex align-items-center gap-3">
                <span class="text-muted" id="callsTotalCount">{{ $callLogs->total() }} total</span>
                <button type="button" id="refreshCallsBtn" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" id="refreshBtnIcon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                    <span id="refreshBtnText">Fetch Latest</span>
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th class="d-none d-sm-table-cell">Direction</th>
                        <th>From / To</th>
                        <th>Duration</th>
                        <th class="d-none d-md-table-cell">Sentiment</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody id="callsTableBody">
                    @include('customer.calls._rows')
                </tbody>
            </table>
        </div>
        @if($callLogs->hasPages())
            <div class="card-footer d-flex align-items-center" id="callsPaginationFooter">
                <p class="m-0 text-muted" id="callsShowingText">
                    Showing {{ $callLogs->firstItem() }} to {{ $callLogs->lastItem() }} of {{ $callLogs->total() }} calls
                </p>
                <div class="ms-auto" id="callsPaginationLinks">
                    {{ $callLogs->links() }}
                </div>
            </div>
        @else
            <div class="card-footer d-flex align-items-center d-none" id="callsPaginationFooter">
                <p class="m-0 text-muted" id="callsShowingText"></p>
                <div class="ms-auto" id="callsPaginationLinks"></div>
            </div>
        @endif
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
            <div id="callDetailsData" class="d-none"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const callsTableBody   = document.getElementById('callsTableBody');
    const refreshBtn       = document.getElementById('refreshCallsBtn');
    const refreshBtnText   = document.getElementById('refreshBtnText');
    const refreshBtnIcon   = document.getElementById('refreshBtnIcon');
    const overlay          = document.getElementById('callsRefreshOverlay');
    const totalCount       = document.getElementById('callsTotalCount');
    const paginationFooter = document.getElementById('callsPaginationFooter');
    const showingText      = document.getElementById('callsShowingText');
    const paginationLinks  = document.getElementById('callsPaginationLinks');

    const MIN_SPINNER_MS = 1500;

    // --- Refresh table via AJAX ---
    refreshBtn.addEventListener('click', async function() {
        const started = Date.now();

        refreshBtn.disabled = true;
        refreshBtnText.textContent = 'Fetching...';
        refreshBtnIcon.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l0 -3" /><path d="M16.25 7.75l2.15 -2.15" /><path d="M18 12l3 0" /><path d="M16.25 16.25l2.15 2.15" /><path d="M12 18l0 3" /><path d="M7.75 16.25l-2.15 2.15" /><path d="M6 12l-3 0" /><path d="M7.75 7.75l-2.15 -2.15" />';
        overlay.classList.remove('d-none');

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('refresh', '1');
            url.searchParams.delete('page'); // reset to page 1

            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('Request failed');
            const data = await response.json();

            // Enforce minimum spinner duration
            const elapsed = Date.now() - started;
            if (elapsed < MIN_SPINNER_MS) {
                await new Promise(r => setTimeout(r, MIN_SPINNER_MS - elapsed));
            }

            // Update table body
            callsTableBody.innerHTML = data.rows_html;

            // Update count
            totalCount.textContent = data.total + ' total';

            // Update pagination footer
            if (data.pagination_html && data.showing_text) {
                showingText.textContent = data.showing_text;
                paginationLinks.innerHTML = data.pagination_html;
                paginationFooter.classList.remove('d-none');
            } else {
                paginationFooter.classList.add('d-none');
            }

            // Re-bind view-call buttons in new rows
            bindViewCallButtons();

        } catch (e) {
            const elapsed = Date.now() - started;
            if (elapsed < MIN_SPINNER_MS) {
                await new Promise(r => setTimeout(r, MIN_SPINNER_MS - elapsed));
            }
            callsTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Failed to fetch calls. Please try again.</td></tr>';
        } finally {
            overlay.classList.add('d-none');
            refreshBtn.disabled = false;
            refreshBtnText.textContent = 'Fetch Latest';
            refreshBtnIcon.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />';
        }
    });

    // --- Call details offcanvas ---
    const loader        = document.getElementById('callDetailsLoader');
    const dataContainer = document.getElementById('callDetailsData');

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
            .then(r => r.json())
            .then(data => {
                renderCallDetails(data);
                loader.classList.add('d-none');
                dataContainer.classList.remove('d-none');
            })
            .catch(() => {
                dataContainer.innerHTML = '<div class="alert alert-danger">Failed to load call details</div>';
                loader.classList.add('d-none');
                dataContainer.classList.remove('d-none');
            });
    }

    function renderCallDetails(call) {
        const sentimentColors = { 'positive': 'bg-green-lt', 'negative': 'bg-red-lt', 'neutral': 'bg-secondary-lt' };
        const sentimentBadge = call.sentiment
            ? `<span class="badge ${sentimentColors[call.sentiment] || 'bg-secondary-lt'}">${call.sentiment}</span>`
            : '-';

        let transcriptHtml = '';
        if (call.transcript && call.transcript.length > 0) {
            transcriptHtml = '<div class="transcript-container mt-3">';
            call.transcript.forEach(item => {
                const isAgent = item.speaker === 'agent';
                transcriptHtml += `<div class="transcript-item ${isAgent ? 'transcript-agent' : 'transcript-user'}">
                    <div class="transcript-role">${isAgent ? 'Assistant' : 'Customer'}</div>
                    <div class="transcript-text">${item.message || item.text || ''}</div>
                </div>`;
            });
            transcriptHtml += '</div>';
        }

        const recordingHtml = call.recording_url ? `
            <div class="mb-3">
                <label class="form-label">Recording</label>
                <audio controls class="w-100">
                    <source src="${call.recording_url}" type="audio/mpeg">
                </audio>
            </div>` : '';

        dataContainer.innerHTML = `
            <div class="mb-4">
                <div class="row g-3">
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Direction</div>
                        <div class="datagrid-content"><span class="badge ${call.direction === 'inbound' ? 'bg-blue-lt' : 'bg-green-lt'}">${call.direction === 'inbound' ? 'Inbound' : 'Outbound'}</span></div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Status</div>
                        <div class="datagrid-content"><span class="badge bg-green-lt">${call.call_status || 'Unknown'}</span></div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">From</div>
                        <div class="datagrid-content">${call.from_number || '-'}</div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">To</div>
                        <div class="datagrid-content">${call.to_number || '-'}</div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Started</div>
                        <div class="datagrid-content">${call.started_at || '-'}</div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Duration</div>
                        <div class="datagrid-content text-money">${call.duration_formatted}</div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Sentiment</div>
                        <div class="datagrid-content">${sentimentBadge}</div></div></div>
                </div>
            </div>
            ${recordingHtml}
            ${call.summary ? `<div class="mb-3"><label class="form-label">Summary</label><div class="bg-light rounded p-3">${call.summary}</div></div>` : ''}
            ${transcriptHtml ? `<div class="mb-3"><label class="form-label">Transcript</label>${transcriptHtml}</div>` : ''}
        `;
    }
});
</script>
@endpush
