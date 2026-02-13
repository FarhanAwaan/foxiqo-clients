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
    <div class="row">
        <!-- Agent Info Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl bg-primary-lt">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    </div>
                    <h3 class="card-title mb-1">{{ $agent->name }}</h3>
                    @if($agent->phone_number)
                        <p class="text-muted mb-2">{{ $agent->phone_number }}</p>
                    @endif
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
                                <span class="badge bg-blue-lt">Inbound Only</span>
                                @break
                            @case('outbound')
                                <span class="badge bg-cyan-lt">Outbound Only</span>
                                @break
                            @default
                                <span class="badge bg-purple-lt">Inbound & Outbound</span>
                        @endswitch
                    </div>
                </div>
                @if($agent->description)
                    <div class="card-body border-top">
                        <p class="text-muted mb-0">{{ $agent->description }}</p>
                    </div>
                @endif
            </div>

            <!-- Stats Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="agent-stat text-center">
                                <div class="agent-stat-value text-primary">{{ number_format($totalCalls) }}</div>
                                <div class="agent-stat-label">Total Calls</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="agent-stat text-center">
                                <div class="agent-stat-value text-green">{{ number_format($totalMinutes, 1) }}</div>
                                <div class="agent-stat-label">Total Minutes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="agent-stat text-center">
                                <div class="agent-stat-value text-blue">{{ number_format($inboundCalls) }}</div>
                                <div class="agent-stat-label">Inbound Calls</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="agent-stat text-center">
                                <div class="agent-stat-value text-cyan">{{ number_format($outboundCalls) }}</div>
                                <div class="agent-stat-label">Outbound Calls</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="agent-stat text-center">
                                <div class="agent-stat-value">{{ gmdate("i:s", (int)$avgDuration) }}</div>
                                <div class="agent-stat-label">Avg Call Duration</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Card -->
            @if($agent->subscription)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Plan</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="h3">{{ $agent->subscription->plan->name }}</span>
                            <span class="text-muted ms-2">${{ number_format($agent->subscription->custom_price ?? $agent->subscription->plan->price, 2) }}/mo</span>
                        </div>
                        @php
                            $used = $agent->subscription->minutes_used ?? 0;
                            $included = $agent->subscription->plan->included_minutes ?? 1;
                            $percent = min(100, ($used / $included) * 100);
                            $usageClass = $percent > 90 ? 'usage-danger' : ($percent > 70 ? 'usage-warning' : 'usage-normal');
                        @endphp
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Usage</span>
                            <span class="text-muted">{{ number_format($used, 0) }} / {{ number_format($included) }} min</span>
                        </div>
                        <div class="usage-bar mb-3">
                            <div class="usage-bar-fill {{ $usageClass }}" style="width: {{ $percent }}%"></div>
                        </div>

                        @if($percent > 90)
                            <div class="alert alert-warning alert-sm mb-3">
                                <div class="d-flex align-items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                                    <span class="small">Approaching limit</span>
                                </div>
                            </div>
                        @endif

                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Status</div>
                                <div class="datagrid-content">
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
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Period End</div>
                                <div class="datagrid-content">{{ $agent->subscription->current_period_end?->format('M d, Y') ?? '-' }}</div>
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

        <!-- Calls List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Call History</h3>
                    <div class="card-actions">
                        <span class="text-muted">{{ $callLogs->total() }} calls</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Direction</th>
                                <th>From / To</th>
                                <th>Duration</th>
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
                                    <td>
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
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No calls recorded yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($callLogs->hasPages())
                    <div class="card-footer">
                        {{ $callLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Call Details Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="callDetailsOffcanvas" style="width: 500px;">
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
    const agentId = {{ $agent->id }};
    const viewCallBtns = document.querySelectorAll('.view-call-btn');
    const loader = document.getElementById('callDetailsLoader');
    const dataContainer = document.getElementById('callDetailsData');

    viewCallBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const callId = this.dataset.callId;
            loadCallDetails(callId);
        });
    });

    function loadCallDetails(callId) {
        loader.classList.remove('d-none');
        dataContainer.classList.add('d-none');

        fetch(`/customer/agents/${agentId}/calls/${callId}`)
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
                const isAgent = item.role === 'agent';
                transcriptHtml += `
                    <div class="transcript-item ${isAgent ? 'transcript-agent' : 'transcript-user'}">
                        <div class="transcript-role">${isAgent ? 'Agent' : 'Customer'}</div>
                        <div class="transcript-text">${item.content || item.text || ''}</div>
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
