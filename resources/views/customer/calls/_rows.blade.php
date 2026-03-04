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
        <td class="d-none d-md-table-cell">
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
                    class="btn btn-icon btn-ghost-primary view-call-btn"
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
        <td colspan="7">
            <div class="empty-state py-5">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                </div>
                <p class="empty-state-title">No calls found</p>
                <p class="empty-state-description">
                    {{ request()->hasAny(['from_date', 'to_date', 'phone']) ? 'No calls match your filters.' : 'This assistant has no calls yet.' }}
                </p>
            </div>
        </td>
    </tr>
@endforelse
