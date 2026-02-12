@extends('layouts.customer')

@section('title', 'My Invoices')

@section('page-header')
    My Invoices
@endsection

@section('content')
<!-- Stats Cards -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Invoices</div>
                </div>
                <div class="h1 mb-0">{{ $stats['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Paid</div>
                </div>
                <div class="h1 mb-0 text-success">{{ $stats['paid'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending</div>
                </div>
                <div class="h1 mb-0 text-warning">{{ $stats['pending'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Paid</div>
                </div>
                <div class="h1 mb-0">${{ number_format($stats['total_paid'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('customer.invoices.index') }}" class="row g-3 align-items-end">
            <div class="col-auto">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" style="min-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['status']))
                    <a href="{{ route('customer.invoices.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Service</th>
                    <th>Period</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('customer.invoices.show', $invoice) }}" class="text-reset">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td>
                            @if($invoice->subscription?->agent)
                                <div>{{ $invoice->subscription->agent->name }}</div>
                                <div class="text-muted small">{{ $invoice->subscription->plan->name ?? 'N/A' }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted">
                                {{ $invoice->billing_period_start?->format('M d') }} - {{ $invoice->billing_period_end?->format('M d, Y') }}
                            </span>
                        </td>
                        <td class="fw-bold">${{ number_format($invoice->amount, 2) }}</td>
                        <td>
                            {{ $invoice->due_date->format('M d, Y') }}
                            @if($invoice->isOverdue() && $invoice->status !== 'paid')
                                <span class="badge bg-danger ms-1">Overdue</span>
                            @endif
                        </td>
                        <td>
                            @switch($invoice->status)
                                @case('draft')
                                    <span class="badge bg-secondary text-white">Draft</span>
                                    @break
                                @case('sent')
                                    <span class="badge bg-info text-white">Sent</span>
                                    @break
                                @case('paid')
                                    <span class="badge bg-success text-white">Paid</span>
                                    @break
                                @case('overdue')
                                    <span class="badge bg-danger text-white">Overdue</span>
                                    @break
                                @case('voided')
                                    <span class="badge bg-dark text-white">Voided</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary text-white">{{ ucfirst($invoice->status) }}</span>
                            @endswitch
                        </td>
                        <td>
                            <a href="{{ route('customer.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="card-footer d-flex align-items-center">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
