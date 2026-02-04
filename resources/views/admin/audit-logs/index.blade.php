@extends('layouts.admin')

@section('title', 'Activity Logs')

@section('page-pretitle')
    System
@endsection

@section('page-header')
    Activity Logs
@endsection

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 1.5rem;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e6e7e9;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -1.7rem;
        top: 0.5rem;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #206bc4;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #e6e7e9;
    }
    .timeline-item.success::before { background: #2fb344; }
    .timeline-item.danger::before { background: #d63939; }
    .timeline-item.warning::before { background: #f76707; }
    .timeline-item.info::before { background: #4299e1; }
    .timeline-date {
        position: sticky;
        top: 0;
        background: #f4f6fa;
        padding: 0.5rem 1rem;
        margin: 0 -1rem 1rem -1rem;
        border-radius: 4px;
        font-weight: 600;
        z-index: 10;
    }
    .log-details {
        background: #f8fafc;
        border-radius: 4px;
        padding: 0.75rem;
        font-size: 0.8125rem;
        max-height: 200px;
        overflow: auto;
    }
    .log-details pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-all;
    }
</style>
@endpush

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
            <div class="card-actions">
                @if(request()->hasAny(['company_id', 'action', 'entity_type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-ghost-secondary btn-sm">
                        Clear Filters
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.audit-logs.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', ucfirst($action)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Entity Type</label>
                        <select name="entity_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($entityTypes as $type)
                                <option value="{{ $type }}" {{ request('entity_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Logs</div>
                    </div>
                    <div class="h1 mb-0">{{ number_format($logs->total()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Date Range</div>
                    </div>
                    <div class="h3 mb-0">
                        @if($groupedLogs->isNotEmpty())
                            {{ $groupedLogs->keys()->last() }} to {{ $groupedLogs->keys()->first() }}
                        @else
                            No logs
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Order</div>
                    </div>
                    <div class="btn-group w-100">
                        <a href="{{ request()->fullUrlWithQuery(['order' => 'desc']) }}"
                           class="btn btn-sm {{ request('order', 'desc') == 'desc' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Newest First
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['order' => 'asc']) }}"
                           class="btn btn-sm {{ request('order') == 'asc' ? 'btn-primary' : 'btn-outline-primary' }}">
                            Oldest First
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @if(request('company_id'))
            @php $selectedCompany = $companies->find(request('company_id')); @endphp
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-primary-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Viewing Company</div>
                        </div>
                        <div class="h3 mb-0">{{ $selectedCompany?->name ?? 'Unknown' }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Timeline View -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Activity Timeline</h3>
        </div>
        <div class="card-body">
            @if($groupedLogs->isNotEmpty())
                @foreach($groupedLogs as $date => $dayLogs)
                    <div class="timeline-date">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M11 15h1" /><path d="M12 15v3" /></svg>
                        {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                        <span class="badge bg-secondary text-white ms-2">{{ $dayLogs->count() }} events</span>
                    </div>
                    <div class="timeline">
                        @foreach($dayLogs as $log)
                            @php
                                $itemClass = match(true) {
                                    str_contains($log->action, 'created') || str_contains($log->action, 'activated') || $log->action === 'payment_received' => 'success',
                                    str_contains($log->action, 'cancelled') || str_contains($log->action, 'deleted') => 'danger',
                                    str_contains($log->action, 'updated') => 'warning',
                                    default => 'info'
                                };
                            @endphp
                            <div class="timeline-item {{ $itemClass }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-fill">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge bg-{{ $itemClass }}-lt me-2">{{ $log->action_label }}</span>
                                            @if($log->entity_name)
                                                <span class="badge bg-secondary-lt me-2">{{ $log->entity_name }}</span>
                                            @endif
                                            @if($log->company)
                                                <a href="{{ route('admin.companies.show', $log->company) }}" class="text-muted small">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                                                    {{ $log->company->name }}
                                                </a>
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            @if($log->user)
                                                <span class="me-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" /></svg>
                                                    {{ $log->user->full_name }}
                                                </span>
                                            @endif
                                            @if($log->ip_address)
                                                <span class="me-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M3.6 9h16.8" /><path d="M3.6 15h16.8" /><path d="M11.5 3a17 17 0 0 0 0 18" /><path d="M12.5 3a17 17 0 0 1 0 18" /></svg>
                                                    {{ $log->ip_address }}
                                                </span>
                                            @endif
                                            <span class="text-primary">{{ $log->created_at->format('h:i:s A') }}</span>
                                        </div>

                                        @if($log->entity_id)
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-ghost-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#log-{{ $log->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                                    View Details
                                                </button>
                                                <span class="text-muted small">Entity ID: {{ $log->entity_id }}</span>
                                            </div>
                                            <div class="collapse mt-2" id="log-{{ $log->id }}">
                                                <div class="row">
                                                    @if($log->old_values)
                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Previous Values</div>
                                                            <div class="log-details">
                                                                <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if($log->new_values)
                                                        <div class="col-md-{{ $log->old_values ? '6' : '12' }}">
                                                            <div class="small text-muted mb-1">{{ $log->old_values ? 'New Values' : 'Details' }}</div>
                                                            <div class="log-details">
                                                                <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @else
                <div class="empty-state py-5">
                    <div class="empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5h2" /><path d="M5 4v2" /><path d="M11.5 4l-.5 2" /><path d="M18 5h2" /><path d="M19 4v2" /><path d="M15 9l-1 1" /><path d="M18 13l2 -.5" /><path d="M18 19h2" /><path d="M19 18v2" /><path d="M14 16.518l-6.518 -6.518l-4.39 9.58a1 1 0 0 0 1.329 1.329l9.579 -4.391z" /></svg>
                    </div>
                    <p class="empty-state-title">No activity logs found</p>
                    <p class="empty-state-description">
                        @if(request()->hasAny(['company_id', 'action', 'entity_type', 'date_from', 'date_to']))
                            Try adjusting your filters to see more results.
                        @else
                            Activity logs will appear here as actions are performed in the system.
                        @endif
                    </p>
                </div>
            @endif
        </div>
        @if($logs->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $logs->firstItem() }}</span> to <span>{{ $logs->lastItem() }}</span> of <span>{{ $logs->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
