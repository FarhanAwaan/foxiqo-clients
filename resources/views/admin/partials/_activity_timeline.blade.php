{{-- Expects $activity: a collection of AuditLog rows for one entity, latest first --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Activity</h3>
    </div>
    <div class="list-group list-group-flush">
        @forelse($activity as $entry)
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="badge {{ str_replace('text-', 'bg-', $entry->action_icon) }}-lt"></span>
                    </div>
                    <div class="col text-truncate">
                        <div class="{{ $entry->action_icon }}">{{ $entry->action_label }}</div>
                        <div class="text-muted small">
                            {{ $entry->created_at->format('M d, Y h:i A') }}
                            @if($entry->user)
                                &middot; {{ $entry->user->full_name }}
                            @else
                                &middot; System
                            @endif
                        </div>
                        @php
                            $extra = collect($entry->new_values ?? [])->only(['paddle_transaction_id', 'invoice_id', 'action', 'status', 'error'])->filter();
                        @endphp
                        @if($extra->isNotEmpty())
                            <div class="text-muted small mt-1">
                                @foreach($extra as $key => $value)
                                    <span class="me-2">{{ str_replace('_', ' ', ucfirst($key)) }}: <code>{{ $value }}</code></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-3">No activity recorded yet</div>
        @endforelse
    </div>
</div>
