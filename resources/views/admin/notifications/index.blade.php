@extends('layouts.admin')

@section('title', 'Emails')

@section('page-pretitle')
    System
@endsection

@section('page-header')
    Emails
@endsection

@section('content')
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Sent</div>
                    <div class="h1 mb-0 text-green">{{ number_format($counts['sent']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Queued</div>
                    <div class="h1 mb-0 text-yellow">{{ number_format($counts['queued']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Failed</div>
                    <div class="h1 mb-0 {{ $counts['failed'] > 0 ? 'text-red' : '' }}">{{ number_format($counts['failed']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Emails</h3>
            <div class="card-actions">
                <form method="GET" action="{{ route('admin.notifications.index') }}" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Search subject..." value="{{ request('search') }}">
                    <select name="type" class="form-select form-select-sm" style="width: 200px;" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if(request('search') || request('type') || request('status'))
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Sent At</th>
                        <th>Type</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td class="text-muted">
                                {{ ($notification->sent_at ?? $notification->created_at)?->format('M d, Y h:i A') }}
                            </td>
                            <td>{{ str_replace('_', ' ', ucfirst($notification->type)) }}</td>
                            <td>{{ $notification->recipient_email ?? '-' }}</td>
                            <td class="text-truncate" style="max-width: 300px;">{{ $notification->subject ?? '-' }}</td>
                            <td>
                                @if($notification->company)
                                    <a href="{{ route('admin.companies.show', $notification->company) }}" class="text-reset">{{ $notification->company->name }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @switch($notification->status)
                                    @case('sent')
                                        <span class="badge bg-green-lt">Sent</span>
                                        @break
                                    @case('queued')
                                        <span class="badge bg-yellow-lt">Queued</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-red-lt" title="{{ $notification->error }}">Failed</span>
                                        @if($notification->error)
                                            <div class="text-danger small text-truncate" style="max-width: 250px;" title="{{ $notification->error }}">{{ $notification->error }}</div>
                                        @endif
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($notification->status) }}</span>
                                @endswitch
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No emails sent yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notifications->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $notifications->firstItem() }}</span> to <span>{{ $notifications->lastItem() }}</span> of <span>{{ $notifications->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $notifications->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
