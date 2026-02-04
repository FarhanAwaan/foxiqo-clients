@extends('layouts.customer')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('page-header')
    Invoice {{ $invoice->invoice_number }}
@endsection

@section('content')
<!-- Payment Link Banner (if pending payment) -->
@if($activePaymentLink && $invoice->status !== 'paid')
    <div class="card card-md bg-primary-lt mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-1">Payment Required</h3>
                    <p class="text-muted mb-0">Click the button below to pay this invoice securely online.</p>
                </div>
                <div class="col-auto">
                    <a href="{{ $activePaymentLink->getInternalPaymentUrl() }}" class="btn btn-primary btn-lg" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg>
                        Pay Now - ${{ number_format($invoice->amount, 2) }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <!-- Invoice Details -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Invoice Details</h3>
                <div class="card-actions">
                    @switch($invoice->status)
                        @case('draft')
                            <span class="badge bg-secondary">Draft</span>
                            @break
                        @case('sent')
                            <span class="badge bg-info">Awaiting Payment</span>
                            @break
                        @case('paid')
                            <span class="badge bg-success">Paid</span>
                            @break
                        @case('overdue')
                            <span class="badge bg-danger">Overdue</span>
                            @break
                        @case('voided')
                            <span class="badge bg-dark">Voided</span>
                            @break
                    @endswitch
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Invoice Number</div>
                        <div class="datagrid-content">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Amount</div>
                        <div class="datagrid-content">
                            <span class="h3 mb-0">${{ number_format($invoice->amount, 2) }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Billing Period</div>
                        <div class="datagrid-content">
                            {{ $invoice->billing_period_start?->format('M d, Y') }} - {{ $invoice->billing_period_end?->format('M d, Y') }}
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Due Date</div>
                        <div class="datagrid-content">
                            {{ $invoice->due_date->format('M d, Y') }}
                            @if($invoice->isOverdue() && $invoice->status !== 'paid')
                                <span class="badge bg-danger ms-1">Overdue</span>
                            @endif
                        </div>
                    </div>
                    @if($invoice->sent_at)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Sent On</div>
                            <div class="datagrid-content">{{ $invoice->sent_at->format('M d, Y \a\t g:i A') }}</div>
                        </div>
                    @endif
                    @if($invoice->paid_at)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Paid On</div>
                            <div class="datagrid-content text-success">{{ $invoice->paid_at->format('M d, Y \a\t g:i A') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Service Details -->
        @if($invoice->subscription)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Service Details</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Agent</div>
                            <div class="datagrid-content">
                                <a href="{{ route('customer.agents.show', $invoice->subscription->agent) }}">
                                    {{ $invoice->subscription->agent->name }}
                                </a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Plan</div>
                            <div class="datagrid-content">{{ $invoice->subscription->plan->name ?? 'N/A' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Included Minutes</div>
                            <div class="datagrid-content">{{ number_format($invoice->subscription->plan->included_minutes ?? 0) }} min</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Subscription Status</div>
                            <div class="datagrid-content">
                                @if($invoice->subscription->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @elseif($invoice->subscription->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($invoice->subscription->status) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Payment History -->
        @if($invoice->payments->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment History</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at?->format('M d, Y \a\t g:i A') ?? $payment->created_at->format('M d, Y') }}</td>
                                    <td class="text-success fw-bold">${{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $payment->provider)) }}</td>
                                    <td>
                                        @if($payment->provider_transaction_id)
                                            <code>{{ $payment->provider_transaction_id }}</code>
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
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        @if($invoice->status !== 'paid' && $invoice->status !== 'voided' && $activePaymentLink)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ $activePaymentLink->getInternalPaymentUrl() }}" class="btn btn-primary w-100 mb-2" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg>
                        Pay Invoice
                    </a>
                    <p class="text-muted small mb-0 text-center">
                        Secure payment powered by {{ config('app.name') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Invoice Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Summary</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span>${{ number_format($invoice->amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tax</span>
                    <span>$0.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Total Due</span>
                    <span class="fw-bold h4 mb-0">${{ number_format($invoice->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Need Help -->
        <div class="card">
            <div class="card-body text-center">
                <h4>Need Help?</h4>
                <p class="text-muted small">If you have questions about this invoice, please contact our support team.</p>
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
