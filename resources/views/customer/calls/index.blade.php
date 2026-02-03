@extends('layouts.customer')

@section('title', 'Call History - ' . $agent->name)

@section('page-pretitle')
    {{ $agent->name }}
@endsection

@section('page-header')
    Call History
@endsection

@section('page-actions')
    <a href="{{ route('customer.agents.show', $agent) }}" class="btn btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
        Back to Agent
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Calls</h3>
            <div class="card-actions">
                <span class="text-muted">{{ $calls->total() }} total calls</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Call ID</th>
                        <th>Duration</th>
                        <th>Cost</th>
                        <th>Sentiment</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($calls as $call)
                        <tr>
                            <td>
                                <a href="{{ route('customer.calls.show', $call) }}" class="text-reset">
                                    {{ Str::limit($call->retell_call_id, 16) }}
                                </a>
                            </td>
                            <td>
                                @if($call->duration_seconds)
                                    <span class="text-money">{{ gmdate('i:s', $call->duration_seconds) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($call->cost)
                                    <span class="text-money">${{ number_format($call->cost, 4) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @switch($call->sentiment)
                                    @case('positive')
                                        <span class="badge bg-green-lt">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 10l.01 0" /><path d="M15 10l.01 0" /><path d="M9.5 15a3.5 3.5 0 0 0 5 0" /></svg>
                                            Positive
                                        </span>
                                        @break
                                    @case('negative')
                                        <span class="badge bg-red-lt">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 10l.01 0" /><path d="M15 10l.01 0" /><path d="M9.5 15.25a3.5 3.5 0 0 1 5 0" /></svg>
                                            Negative
                                        </span>
                                        @break
                                    @case('neutral')
                                        <span class="badge bg-secondary-lt">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 10l.01 0" /><path d="M15 10l.01 0" /><path d="M9 15l6 0" /></svg>
                                            Neutral
                                        </span>
                                        @break
                                    @default
                                        <span class="text-muted">-</span>
                                @endswitch
                            </td>
                            <td>
                                @switch($call->status)
                                    @case('analyzed')
                                        <span class="badge bg-green-lt">Complete</span>
                                        @break
                                    @case('ended')
                                        <span class="badge bg-blue-lt">Ended</span>
                                        @break
                                    @case('started')
                                        <span class="badge bg-yellow-lt">In Progress</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($call->status) }}</span>
                                @endswitch
                            </td>
                            <td class="text-muted">
                                <div>{{ $call->created_at->format('M d, Y') }}</div>
                                <div class="small">{{ $call->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                <a href="{{ route('customer.calls.show', $call) }}" class="btn btn-icon btn-ghost-primary btn-sm" title="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state py-5">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                                    </div>
                                    <p class="empty-state-title">No calls recorded</p>
                                    <p class="empty-state-description">
                                        This agent hasn't received any calls yet.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($calls->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $calls->firstItem() }}</span> to <span>{{ $calls->lastItem() }}</span> of <span>{{ $calls->total() }}</span> calls
                </p>
                <div class="ms-auto">
                    {{ $calls->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
