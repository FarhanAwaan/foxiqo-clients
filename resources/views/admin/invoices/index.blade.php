@extends('layouts.admin')

@section('title', 'Invoices')

@section('page-pretitle')
    Billing
@endsection

@section('page-header')
    Invoices
@endsection

@section('content')
    <!-- Stats Cards (the whole filtered set — see InvoiceController@index) -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Invoices</div>
                    </div>
                    <div class="h1 mb-0">{{ $invoices->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Paid</div>
                    </div>
                    <div class="h1 mb-0 text-green">${{ number_format($stats->paid_amount, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Pending</div>
                    </div>
                    <div class="h1 mb-0 text-yellow">${{ number_format($stats->pending_amount, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Overdue</div>
                    </div>
                    <div class="h1 mb-0 text-red">${{ number_format($stats->overdue_amount, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paddle reconciliation: what Paddle's own transactions actually charged, next to what the
         portal recorded for the same invoices (this filtered set) — see Invoice::reconcilesWithPaddle(). -->
    @if($stats->paddle_recorded_total > 0 || $stats->paddle_mismatch_count > 0)
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <div class="subheader">Paddle collected</div>
                        <div class="h2 mb-0">${{ number_format($stats->paddle_charged_total, 2) }}</div>
                    </div>
                    <div class="col-auto text-muted">vs.</div>
                    <div class="col-auto">
                        <div class="subheader">Portal recorded</div>
                        <div class="h2 mb-0">${{ number_format($stats->paddle_recorded_total, 2) }}</div>
                    </div>
                    @if($stats->paddle_refunded_total > 0)
                        <div class="col-auto">
                            <div class="subheader">Refunded</div>
                            <div class="h2 mb-0 text-red">${{ number_format($stats->paddle_refunded_total, 2) }}</div>
                        </div>
                    @endif
                    <div class="col-auto ms-auto">
                        @if($stats->paddle_mismatch_count > 0)
                            <span class="badge bg-red-lt fs-6">{{ $stats->paddle_mismatch_count }} {{ Str::plural('invoice', $stats->paddle_mismatch_count) }} {{ $stats->paddle_mismatch_count === 1 ? "doesn't" : "don't" }} match Paddle</span>
                        @else
                            <span class="badge bg-green-lt fs-6">Every invoice matches Paddle</span>
                        @endif
                    </div>
                </div>
                @if($stats->paddle_mismatch_count > 0)
                    <div class="text-muted small mt-2">A mismatch is usually tax Paddle added that the "Portal recorded" figure doesn't already account for, a discount, or a proration — open the invoice to see its own Paddle breakdown.</div>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Invoices</h3>
            <div class="card-actions">
                <form action="{{ route('admin.invoices.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="company_id" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()">
                        <option value="">All Customers</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @if(request('company_id') || request('status'))
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Agent</th>
                        <th>Amount</th>
                        <th>Billing Period</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="w-1" title="View in Paddle"></th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-reset">
                                    <strong>{{ $invoice->invoice_number }}</strong>
                                </a>
                            </td>
                            <td>
                                @if($invoice->isUsageInvoice())
                                    <span class="badge bg-purple-lt">Usage &middot; {{ number_format($invoice->usage_minutes) }} min</span>
                                @elseif($invoice->isActivationInvoice())
                                    <span class="badge bg-orange-lt">Activation</span>
                                @else
                                    <span class="badge bg-blue-lt">Retainer</span>
                                @endif
                            </td>
                            <td>
                                @if($invoice->company)
                                    <a href="{{ route('admin.companies.show', $invoice->company) }}" class="text-reset">
                                        {{ $invoice->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($invoice->subscription && $invoice->subscription->agent)
                                    <a href="{{ route('admin.agents.show', $invoice->subscription->agent) }}" class="text-reset">
                                        {{ $invoice->subscription->agent->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-money">
                                <strong>${{ number_format($invoice->amount, 2) }}</strong>
                                @unless($invoice->reconcilesWithPaddle())
                                    <div class="text-red small" title="What Paddle's transaction actually shows as charged">Paddle: ${{ number_format($invoice->paddle_charged_amount, 2) }}</div>
                                @endunless
                            </td>
                            <td>
                                @if($invoice->billing_period_start && $invoice->billing_period_end)
                                    <span class="small">
                                        {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($invoice->due_date)
                                    <span class="{{ $invoice->isOverdue() ? 'text-danger fw-bold' : '' }}">
                                        {{ $invoice->due_date->format('M d, Y') }}
                                    </span>
                                    @if($invoice->isOverdue())
                                        <div class="text-danger small">{{ $invoice->due_date->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
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
                                    @case('draft')
                                        <span class="badge bg-secondary-lt">Draft</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-dark-lt">Cancelled</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($invoice->status) }}</span>
                                @endswitch
                            </td>
                            <td>
                                @if($invoice->paddle_transaction_id)
                                    <a href="{{ route('admin.invoices.paddle-invoice', $invoice) }}" target="_blank" class="btn btn-icon btn-ghost-primary btn-sm" title="View in Paddle">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M9 15l6 -6" /><path d="M11 9h4v4" /></svg>
                                    </a>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-ghost-primary btn-md" data-bs-toggle="dropdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.invoices.show', $invoice) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            View Details
                                        </a>
                                        @if($invoice->status !== 'paid' && $invoice->subscription->status !== 'cancelled')
                                            <form action="{{ route('admin.invoices.send-payment-link', $invoice) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                                                    Send Payment Link
                                                </button>
                                            </form>
                                            <a class="dropdown-item text-success" href="#" data-bs-toggle="modal" data-bs-target="#markPaidModal{{ $invoice->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                Mark as Paid
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 7l1 0" /><path d="M9 13l6 0" /><path d="M13 17l2 0" /></svg>
                                    </div>
                                    <p class="empty-state-title">No invoices found</p>
                                    <p class="empty-state-description">Invoices will appear here when subscriptions are activated.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $invoices->firstItem() }}</span> to <span>{{ $invoices->lastItem() }}</span> of <span>{{ $invoices->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Mark as Paid Modals -->
    @foreach($invoices->where('status', '!=', 'paid') as $invoice)
        <div class="modal modal-blur fade" id="markPaidModal{{ $invoice->id }}" tabindex="-1">
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
                                    <option value="manual">Manual Payment</option>
                                    <option value="bank_transfer">Bank Transfer (Nsave)</option>
                                    <option value="paddle">Paddle</option>
                                    <option value="stripe">Stripe</option>
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
    @endforeach
@endsection
