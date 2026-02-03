@extends('layouts.customer')

@section('title', 'Call Details')

@section('page-pretitle')
    Call History
@endsection

@section('page-header')
    Call Details
@endsection

@section('page-actions')
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
        Back to Call History
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <!-- Call Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Call Information</h3>
                    <div class="card-actions">
                        @switch($callLog->status)
                            @case('analyzed')
                                <span class="badge bg-green-lt">Complete</span>
                                @break
                            @case('ended')
                                <span class="badge bg-blue-lt">Ended</span>
                                @break
                            @default
                                <span class="badge bg-yellow-lt">{{ ucfirst($callLog->status) }}</span>
                        @endswitch
                    </div>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Agent</div>
                            <div class="datagrid-content">
                                <a href="{{ route('customer.agents.show', $callLog->agent) }}">
                                    {{ $callLog->agent->name }}
                                </a>
                            </div>
                        </div>

                        <div class="datagrid-item">
                            <div class="datagrid-title">Call ID</div>
                            <div class="datagrid-content">
                                <code class="small">{{ $callLog->retell_call_id }}</code>
                            </div>
                        </div>

                        <div class="datagrid-item">
                            <div class="datagrid-title">Duration</div>
                            <div class="datagrid-content">
                                @if($callLog->duration_seconds)
                                    {{ gmdate('H:i:s', $callLog->duration_seconds) }}
                                    <span class="text-muted">({{ $callLog->duration_seconds }}s)</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="datagrid-item">
                            <div class="datagrid-title">Cost</div>
                            <div class="datagrid-content">
                                @if($callLog->cost)
                                    <span class="text-money">${{ number_format($callLog->cost, 4) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="datagrid-item">
                            <div class="datagrid-title">Sentiment</div>
                            <div class="datagrid-content">
                                @switch($callLog->sentiment)
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
                                        <span class="text-muted">Not analyzed</span>
                                @endswitch
                            </div>
                        </div>

                        <div class="datagrid-item">
                            <div class="datagrid-title">Started At</div>
                            <div class="datagrid-content">
                                {{ $callLog->call_started_at ? $callLog->call_started_at->format('M d, Y h:i A') : $callLog->created_at->format('M d, Y h:i A') }}
                            </div>
                        </div>

                        @if($callLog->call_ended_at)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Ended At</div>
                                <div class="datagrid-content">
                                    {{ $callLog->call_ended_at->format('M d, Y h:i A') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recording -->
            @if($callLog->recording_url)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recording</h3>
                    </div>
                    <div class="card-body">
                        <audio controls class="w-100">
                            <source src="{{ $callLog->recording_url }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <!-- Transcript -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transcript</h3>
                </div>
                <div class="card-body">
                    @if($callLog->transcript)
                        <div class="transcript-container" style="max-height: 500px; overflow-y: auto;">
                            @php
                                $transcript = is_array($callLog->transcript) ? $callLog->transcript : json_decode($callLog->transcript, true);
                            @endphp

                            @if(is_array($transcript))
                                @foreach($transcript as $message)
                                    <div class="d-flex mb-3 {{ ($message['role'] ?? '') === 'agent' ? '' : 'flex-row-reverse' }}">
                                        <div class="avatar avatar-sm {{ ($message['role'] ?? '') === 'agent' ? 'bg-primary-lt' : 'bg-secondary-lt' }} me-2 ms-2">
                                            @if(($message['role'] ?? '') === 'agent')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" /></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                                            @endif
                                        </div>
                                        <div class="{{ ($message['role'] ?? '') === 'agent' ? 'bg-primary-lt' : 'bg-light' }} rounded p-3" style="max-width: 80%;">
                                            <div class="small text-muted mb-1">
                                                {{ ($message['role'] ?? '') === 'agent' ? 'AI Agent' : 'Customer' }}
                                            </div>
                                            <div>{{ $message['content'] ?? $message['text'] ?? '' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <pre class="mb-0" style="white-space: pre-wrap;">{{ $callLog->transcript }}</pre>
                            @endif
                        </div>
                    @else
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" /></svg>
                            </div>
                            <p class="empty-state-title">No transcript available</p>
                            <p class="empty-state-description">
                                The transcript for this call hasn't been generated yet.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
