@extends('layouts.admin')

@section('title', 'All Calls - ' . $agent->name)

@section('page-pretitle')
    <a href="{{ route('admin.agents.show', $agent) }}">{{ $agent->name }}</a>
@endsection

@section('page-header')
    All Calls
@endsection

@section('page-actions')
    <a href="{{ route('admin.agents.show', $agent) }}" class="btn btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
        Back to Assistant
    </a>
@endsection

@section('content')

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.agents.calls.index', $agent) }}">
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
                                <a href="{{ route('admin.agents.calls.index', $agent) }}" class="btn btn-outline-secondary">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Calls Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Calls</h3>
            <div class="card-actions">
                <span class="text-muted">{{ $callLogs->total() }} total</span>
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
                        <th class="d-none d-md-table-cell">Cost</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($callLogs as $call)
                        <tr>
                            <td>
                                <div>{{ $call->started_at?->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $call->started_at?->format('h:i A') }}</div>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                @if($call->direction === 'inbound')
                                    <span class="badge bg-blue-lt">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 6l-11 11" /><path d="M20 17v-11h-11" /></svg>
                                        Inbound
                                    </span>
                                @else
                                    <span class="badge bg-green-lt">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 18l11 -11" /><path d="M4 7v11h11" /></svg>
                                        Outbound
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $call->from_number ?? '-' }}</div>
                                <div class="text-muted small">{{ $call->to_number ?? '-' }}</div>
                            </td>
                            <td class="text-money">{{ $call->duration_formatted }}</td>
                            <td class="text-money d-none d-md-table-cell">
                                @if($call->retell_cost)
                                    ${{ number_format($call->retell_cost, 4) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @switch($call->call_status)
                                    @case('completed')
                                    @case('analyzed')
                                        <span class="badge bg-green-lt">Completed</span>
                                        @break
                                    @case('in_progress')
                                        <span class="badge bg-blue-lt">In Progress</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-red-lt">Failed</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($call->call_status ?? 'Unknown') }}</span>
                                @endswitch
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-icon btn-ghost-primary btn-sm view-call-btn"
                                        data-call-uuid="{{ $call->uuid }}"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#callDetailsOffcanvas"
                                        title="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No calls found{{ request()->hasAny(['from_date', 'to_date', 'phone']) ? ' for the selected filters' : '' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($callLogs->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing {{ $callLogs->firstItem() }} to {{ $callLogs->lastItem() }} of {{ $callLogs->total() }} calls
                </p>
                <div class="ms-auto">
                    {{ $callLogs->links() }}
                </div>
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
    const viewCallBtns = document.querySelectorAll('.view-call-btn');
    const loader = document.getElementById('callDetailsLoader');
    const dataContainer = document.getElementById('callDetailsData');

    viewCallBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            loadCallDetails(this.dataset.callUuid);
        });
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
                    <div class="transcript-role">${isAgent ? 'Agent' : 'Customer'}</div>
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
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Cost</div>
                        <div class="datagrid-content text-money">$${parseFloat(call.retell_cost || 0).toFixed(4)}</div></div></div>
                    <div class="col-6"><div class="datagrid-item"><div class="datagrid-title">Sentiment</div>
                        <div class="datagrid-content">${sentimentBadge}</div></div></div>
                </div>
            </div>
            ${recordingHtml}
            ${call.summary ? `<div class="mb-3"><label class="form-label">Summary</label><div class="bg-light rounded p-3">${call.summary}</div></div>` : ''}
            ${transcriptHtml ? `<div class="mb-3"><label class="form-label">Transcript</label>${transcriptHtml}</div>` : ''}
            <div class="text-muted small mt-4"><strong>Retell Call ID:</strong> <code>${call.retell_call_id || '-'}</code></div>
        `;
    }
});
</script>
@endpush
