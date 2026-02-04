@extends('layouts.admin')

@section('title', $invoice->invoice_number)

@section('page-pretitle')
    Invoices
@endsection

@section('page-header')
    Invoice {{ $invoice->invoice_number }}
@endsection

@section('page-actions')
    <div class="btn-list">
        @if($invoice->status !== 'paid' && $invoice->subscription->status !== 'cancelled')
            <form action="{{ route('admin.invoices.send-payment-link', $invoice) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                    Send Payment Link
                </button>
            </form>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                Mark as Paid
            </button>
        @endif
    </div>
@endsection

@section('content')
    @php
        // Find the most recent active payment link
        $activePaymentLink = $invoice->paymentLinks?->first(function($link) {
            return !$link->paid_at && (!$link->expires_at || !$link->expires_at->isPast());
        });
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <!-- Active Payment Link Card (Prominent) -->
            @if($activePaymentLink && $invoice->status !== 'paid')
                <div class="card card-md bg-primary-lt mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg bg-primary text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l6 -6" /><circle cx="9.5" cy="9.5" r=".5" fill="currentColor" /><circle cx="14.5" cy="14.5" r=".5" fill="currentColor" /><path d="M5 7.5a2.5 2.5 0 0 1 2.5 -2.5h9a2.5 2.5 0 0 1 2.5 2.5v9a2.5 2.5 0 0 1 -2.5 2.5h-9a2.5 2.5 0 0 1 -2.5 -2.5z" /></svg>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-1">Active Payment Link</h3>
                                <div class="text-muted small mb-2">
                                    <span class="badge {{ $activePaymentLink->provider === 'stripe' ? 'bg-purple-lt' : 'bg-blue-lt' }}">
                                        {{ ucfirst($activePaymentLink->provider) }}
                                    </span>
                                    @if($activePaymentLink->expires_at)
                                        <span class="ms-2">Expires: {{ $activePaymentLink->expires_at->format('M d, Y h:i A') }}</span>
                                    @endif
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="activePaymentUrl" value="{{ $activePaymentLink->payment_url }}" readonly>
                                    <button class="btn btn-primary me-2" type="button" onclick="copyPaymentLink('activePaymentUrl')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
                                        Copy Link
                                    </button>
                                    <a href="{{ $activePaymentLink->payment_url }}" target="_blank" class="btn btn-outline-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                                        Open
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Invoice Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                    <div class="card-actions">
                        @switch($invoice->status)
                            @case('paid')
                                <span class="badge bg-green-lt fs-6">Paid</span>
                                @break
                            @case('sent')
                                <span class="badge bg-blue-lt fs-6">Sent</span>
                                @break
                            @case('overdue')
                                <span class="badge bg-red-lt fs-6">Overdue</span>
                                @break
                            @case('draft')
                                <span class="badge bg-secondary-lt fs-6">Draft</span>
                                @break
                            @default
                                <span class="badge bg-secondary-lt fs-6">{{ ucfirst($invoice->status) }}</span>
                        @endswitch
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-6">
                            <p class="h3">{{ $invoice->invoice_number }}</p>
                            <address>
                                @if($invoice->company)
                                    <strong>{{ $invoice->company->name }}</strong><br>
                                    @if($invoice->company->address)
                                        {{ $invoice->company->address }}<br>
                                    @endif
                                    @if($invoice->company->email)
                                        {{ $invoice->company->email }}
                                    @endif
                                @endif
                            </address>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-1"><strong>Invoice Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                            @if($invoice->due_date)
                                <p class="mb-1 {{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                    <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}
                                    @if($invoice->isOverdue())
                                        <span class="badge bg-red-lt">Overdue</span>
                                    @endif
                                </p>
                            @endif
                            @if($invoice->sent_at)
                                <p class="mb-1"><strong>Sent:</strong> {{ $invoice->sent_at->format('M d, Y h:i A') }}</p>
                            @endif
                            @if($invoice->paid_at)
                                <p class="mb-1 text-success"><strong>Paid:</strong> {{ $invoice->paid_at->format('M d, Y h:i A') }}</p>
                            @endif
                        </div>
                    </div>

                    <table class="table table-transparent table-responsive">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Period</th>
                                <th class="text-end" style="width: 120px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <p class="strong mb-1">
                                        {{ $invoice->subscription?->plan?->name ?? 'Subscription' }} - {{ $invoice->subscription?->agent?->name ?? 'Agent' }}
                                    </p>
                                    <div class="text-muted">
                                        Monthly subscription fee
                                        @if($invoice->subscription?->plan)
                                            ({{ number_format($invoice->subscription->plan->included_minutes) }} minutes included)
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($invoice->billing_period_start && $invoice->billing_period_end)
                                        {{ $invoice->billing_period_start->format('M d') }} -<br>{{ $invoice->billing_period_end->format('M d, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">${{ number_format($invoice->amount, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="strong text-end">Total Due</td>
                                <td class="text-end strong h3 mb-0">${{ number_format($invoice->amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    @if($invoice->notes)
                        <div class="mt-4">
                            <h4>Notes</h4>
                            <p class="text-muted">{{ $invoice->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Links History -->
            @if($invoice->paymentLinks && $invoice->paymentLinks->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment Links History</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Created</th>
                                    <th>Expires</th>
                                    <th>Status</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->paymentLinks as $index => $link)
                                    @php
                                        $isActive = !$link->paid_at && (!$link->expires_at || !$link->expires_at->isPast());
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $link->provider === 'stripe' ? 'bg-purple-lt' : 'bg-blue-lt' }}">
                                                {{ ucfirst($link->provider) }}
                                            </span>
                                        </td>
                                        <td>{{ $link->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @if($link->expires_at)
                                                <span class="{{ $link->expires_at->isPast() ? 'text-danger' : '' }}">
                                                    {{ $link->expires_at->format('M d, Y h:i A') }}
                                                </span>
                                            @else
                                                <span class="text-muted">No expiry</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->paid_at)
                                                <span class="badge bg-green-lt">Used</span>
                                            @elseif($link->expires_at && $link->expires_at->isPast())
                                                <span class="badge bg-secondary-lt">Expired</span>
                                            @else
                                                <span class="badge bg-blue-lt">Active</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isActive)
                                                <div class="btn-group">
                                                    <button class="btn btn-icon btn-ghost-primary btn-sm" type="button" onclick="copyPaymentLink('paymentUrl{{ $index }}')" title="Copy Link">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
                                                    </button>
                                                    <a href="{{ $link->payment_url }}" target="_blank" class="btn btn-icon btn-ghost-primary btn-sm" title="Open Link">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                                                    </a>
                                                </div>
                                                <input type="hidden" id="paymentUrl{{ $index }}" value="{{ $link->payment_url }}">
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Payment Receipts -->
            @if($invoice->receipts && $invoice->receipts->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment Receipts</h3>
                        @if($invoice->receipts->where('status', 'pending')->count() > 0)
                            <div class="card-actions">
                                <span class="badge bg-yellow-lt">{{ $invoice->receipts->where('status', 'pending')->count() }} pending</span>
                            </div>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Uploaded</th>
                                    <th>Status</th>
                                    <th>Reviewed By</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->receipts as $receipt)
                                    <tr>
                                        <td>
                                            <div>{{ Str::limit($receipt->original_filename, 30) }}</div>
                                            <div class="text-muted small">{{ $receipt->getFormattedFileSize() }}</div>
                                        </td>
                                        <td>
                                            {{ $receipt->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            @switch($receipt->status)
                                                @case('pending')
                                                    <span class="badge bg-yellow-lt">Pending</span>
                                                    @break
                                                @case('approved')
                                                    <span class="badge bg-green-lt">Approved</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge bg-red-lt">Rejected</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($receipt->reviewer)
                                                {{ $receipt->reviewer->name }}
                                                <div class="text-muted small">{{ $receipt->reviewed_at?->diffForHumans() }}</div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.receipts.show', $receipt) }}" class="btn btn-sm {{ $receipt->status === 'pending' ? 'btn-warning' : 'btn-outline-primary' }}">
                                                {{ $receipt->status === 'pending' ? 'Review' : 'View' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Payments -->
            @if($invoice->payments && $invoice->payments->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payments</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Provider</th>
                                    <th>Transaction ID</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            <span class="badge {{ $payment->provider === 'stripe' ? 'bg-purple-lt' : ($payment->provider === 'payoneer' ? 'bg-blue-lt' : 'bg-secondary-lt') }}">
                                                {{ ucfirst($payment->provider) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($payment->transaction_id)
                                                <code>{{ $payment->transaction_id }}</code>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-money">${{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            @if($payment->status === 'completed')
                                                <span class="badge bg-green-lt">Completed</span>
                                            @elseif($payment->status === 'pending')
                                                <span class="badge bg-yellow-lt">Pending</span>
                                            @else
                                                <span class="badge bg-red-lt">{{ ucfirst($payment->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Invoice Number</div>
                            <div class="datagrid-content">{{ $invoice->invoice_number }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Amount</div>
                            <div class="datagrid-content text-money h3 mb-0">${{ number_format($invoice->amount, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Status</div>
                            <div class="datagrid-content">
                                @switch($invoice->status)
                                    @case('paid')
                                        <span class="badge bg-green-lt">Paid</span>
                                        @break
                                    @case('sent')
                                        <span class="badge bg-blue-lt">Sent</span>
                                        @break
                                    @case('overdue')
                                        <span class="badge bg-red-lt">Overdue</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($invoice->status) }}</span>
                                @endswitch
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Company</div>
                            <div class="datagrid-content">
                                @if($invoice->company)
                                    <a href="{{ route('admin.companies.show', $invoice->company) }}">
                                        {{ $invoice->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Subscription</div>
                            <div class="datagrid-content">
                                @if($invoice->subscription)
                                    <a href="{{ route('admin.subscriptions.show', $invoice->subscription) }}">
                                        {{ $invoice->subscription->agent?->name ?? 'View' }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Plan</div>
                            <div class="datagrid-content">
                                @if($invoice->subscription?->plan)
                                    <a href="{{ route('admin.plans.show', $invoice->subscription->plan) }}">
                                        {{ $invoice->subscription->plan->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        @if($invoice->billing_period_start && $invoice->billing_period_end)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Billing Period</div>
                                <div class="datagrid-content">
                                    {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                </div>
                            </div>
                        @endif
                        @if($invoice->due_date)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Due Date</div>
                                <div class="datagrid-content {{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                    {{ $invoice->due_date->format('M d, Y') }}
                                </div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $invoice->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Links</h3>
                </div>
                <div class="list-group list-group-flush">
                    @if($invoice->company)
                        <a href="{{ route('admin.companies.show', $invoice->company) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                            View Company
                        </a>
                    @endif
                    @if($invoice->subscription)
                        <a href="{{ route('admin.subscriptions.show', $invoice->subscription) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>
                            View Subscription
                        </a>
                    @endif
                    @if($invoice->subscription?->agent)
                        <a href="{{ route('admin.agents.show', $invoice->subscription->agent) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 6a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -4"></path><path d="M12 2v2"></path><path d="M9 12v9"></path><path d="M15 12v9"></path><path d="M5 16l4 -2"></path><path d="M15 14l4 2"></path><path d="M9 18h6"></path><path d="M10 8v.01"></path><path d="M14 8v.01"></path></svg>
                            View Agent
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Mark as Paid Modal -->
    @if($invoice->status !== 'paid')
        <div class="modal modal-blur fade" id="markPaidModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.invoices.mark-paid', $invoice) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Mark Invoice as Paid</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Mark invoice <strong>{{ $invoice->invoice_number }}</strong> as paid?</p>
                            <p class="text-muted">Amount: <strong class="text-money">${{ number_format($invoice->amount, 2) }}</strong></p>

                            <div class="mb-3">
                                <label class="form-label required">Payment Provider</label>
                                <select name="provider" class="form-select" required>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="manual">Manual Payment</option>
                                    <option value="internal">Internal (Payment Page)</option>
                                    <option value="stripe">Stripe</option>
                                    <option value="payoneer">Payoneer</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Transaction ID (optional)</label>
                                <input type="text" name="transaction_id" class="form-control" placeholder="e.g., ch_1234567890">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-success ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                Mark as Paid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    function copyPaymentLink(elementId) {
        const input = document.getElementById(elementId);
        const url = input.value || input.textContent;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => {
                // Show success feedback
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg> Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-primary', 'btn-ghost-primary');

                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                }, 2000);
            }).catch(() => {
                input.select();
                document.execCommand('copy');
                alert('Payment link copied to clipboard!');
            });
        } else {
            // Fallback for older browsers
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            alert('Payment link copied to clipboard!');
        }
    }
</script>
@endpush
