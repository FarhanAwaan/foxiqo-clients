@extends('layouts.admin')

@section('title', 'Subscription Details')

@section('page-pretitle')
    Subscriptions
@endsection

@section('page-header')
    Subscription: {{ $subscription->agent?->name }}
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
            Edit
        </a>
        @if($subscription->status === 'pending')
            <form action="{{ route('admin.subscriptions.activate', $subscription) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                    Activate
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <!-- Subscription Info Card -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        @switch($subscription->status)
                            @case('active')
                                <span class="avatar avatar-xl bg-green-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                </span>
                                @break
                            @case('pending')
                                <span class="avatar avatar-xl bg-yellow-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 12l0 -4" /><path d="M12 12l4 2" /></svg>
                                </span>
                                @break
                            @case('cancelled')
                                <span class="avatar avatar-xl bg-red-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                </span>
                                @break
                            @default
                                <span class="avatar avatar-xl bg-secondary-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>
                                </span>
                        @endswitch
                    </div>
                    <h3 class="card-title mb-1">{{ ucfirst($subscription->status) }}</h3>
                    <p class="text-muted">
                        {{ $subscription->plan?->name ?? 'No Plan' }}
                    </p>
                    <h2 class="mb-0">
                        ${{ number_format($subscription->getEffectivePrice(), 2) }}<span class="text-muted fs-5">/mo</span>
                    </h2>
                    @if($subscription->custom_price)
                        <small class="text-primary">Custom price (Plan: ${{ number_format($subscription->plan->price, 2) }})</small>
                    @endif
                </div>
                <div class="card-body border-top">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Agent</div>
                            <div class="datagrid-content">
                                @if($subscription->agent)
                                    <a href="{{ route('admin.agents.show', $subscription->agent) }}">
                                        {{ $subscription->agent->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Company</div>
                            <div class="datagrid-content">
                                @if($subscription->company)
                                    <a href="{{ route('admin.companies.show', $subscription->company) }}">
                                        {{ $subscription->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Plan</div>
                            <div class="datagrid-content">
                                @if($subscription->plan)
                                    <a href="{{ route('admin.plans.show', $subscription->plan) }}">
                                        {{ $subscription->plan->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Included Minutes</div>
                            <div class="datagrid-content">{{ number_format($subscription->plan->included_minutes ?? 0) }} min</div>
                        </div>
                        @if($subscription->activated_at)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Activated At</div>
                                <div class="datagrid-content">{{ $subscription->activated_at->format('M d, Y h:i A') }}</div>
                            </div>
                        @endif
                        @if($subscription->cancelled_at)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Cancelled At</div>
                                <div class="datagrid-content">{{ $subscription->cancelled_at->format('M d, Y h:i A') }}</div>
                            </div>
                            @if($subscription->cancellation_reason)
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Cancellation Reason</div>
                                    <div class="datagrid-content">{{ $subscription->cancellation_reason }}</div>
                                </div>
                            @endif
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $subscription->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Card -->
            @if($subscription->status === 'active' && $subscription->plan)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Usage</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $used = $subscription->minutes_used ?? 0;
                            $included = $subscription->plan->included_minutes ?: 1;
                            $percent = min(100, ($used / $included) * 100);
                            $remaining = max(0, $included - $used);
                        @endphp

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ number_format($used, 1) }} min used</span>
                                <span class="text-muted">{{ number_format($included) }} min included</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar {{ $percent > 90 ? 'bg-danger' : ($percent > 70 ? 'bg-warning' : 'bg-success') }}"
                                     style="width: {{ $percent }}%" role="progressbar"></div>
                            </div>
                        </div>

                        <div class="row text-center">
                            <div class="col">
                                <div class="h3 mb-0 {{ $remaining <= 0 ? 'text-danger' : 'text-green' }}">{{ number_format($remaining, 0) }}</div>
                                <div class="text-muted small">Minutes Remaining</div>
                            </div>
                            <div class="col">
                                <div class="h3 mb-0">{{ number_format($percent, 0) }}%</div>
                                <div class="text-muted small">Used</div>
                            </div>
                        </div>

                        @if($subscription->circuit_breaker_triggered)
                            <div class="alert alert-danger mt-3 mb-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                                <strong>Circuit Breaker Triggered</strong>
                                <div class="small">{{ $subscription->circuit_breaker_triggered_at?->format('M d, Y h:i A') }}</div>
                            </div>
                        @endif
                    </div>
                    @if($subscription->current_period_start && $subscription->current_period_end)
                        <div class="card-footer text-muted small">
                            Billing Period: {{ $subscription->current_period_start->format('M d') }} - {{ $subscription->current_period_end->format('M d, Y') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <!-- Invoices -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Invoices</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.invoices.index') }}?subscription_id={{ $subscription->id }}" class="btn btn-ghost-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Billing Period</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscription->invoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.invoices.show', $invoice) }}">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="text-money">${{ number_format($invoice->amount, 2) }}</td>
                                    <td>
                                        @if($invoice->billing_period_start && $invoice->billing_period_end)
                                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->due_date)
                                            <span class="{{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                                {{ $invoice->due_date->format('M d, Y') }}
                                            </span>
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
                                            @default
                                                <span class="badge bg-secondary-lt">{{ ucfirst($invoice->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-icon btn-ghost-primary btn-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No invoices generated yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Billing Cycles -->
            @if($subscription->billingCycles && $subscription->billingCycles->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Billing Cycles</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Minutes Used</th>
                                    <th>Base Amount</th>
                                    <th>Overage</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscription->billingCycles as $cycle)
                                    <tr>
                                        <td>
                                            {{ $cycle->period_start?->format('M d') }} - {{ $cycle->period_end?->format('M d, Y') }}
                                        </td>
                                        <td>{{ number_format($cycle->minutes_used ?? 0, 1) }} min</td>
                                        <td class="text-money">${{ number_format($cycle->base_amount ?? 0, 2) }}</td>
                                        <td class="text-money">
                                            @if($cycle->overage_amount > 0)
                                                <span class="text-warning">${{ number_format($cycle->overage_amount, 2) }}</span>
                                            @else
                                                $0.00
                                            @endif
                                        </td>
                                        <td class="text-money"><strong>${{ number_format($cycle->total_amount ?? 0, 2) }}</strong></td>
                                        <td>
                                            @if($cycle->is_finalized)
                                                <span class="badge bg-green-lt">Finalized</span>
                                            @else
                                                <span class="badge bg-yellow-lt">In Progress</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Actions Card -->
            @if($subscription->status === 'active')
                <div class="card border-danger">
                    <div class="card-header bg-danger-lt">
                        <h3 class="card-title text-danger">Danger Zone</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-1">Cancel Subscription</h4>
                                <p class="text-muted mb-0">This will stop the billing and usage tracking for this agent.</p>
                            </div>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                Cancel Subscription
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cancel Modal -->
                <div class="modal modal-blur fade" id="cancelModal" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Cancel Subscription</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to cancel this subscription for <strong>{{ $subscription->agent?->name }}</strong>?</p>
                                    <div class="mb-3">
                                        <label class="form-label">Reason (optional)</label>
                                        <textarea name="reason" class="form-control" rows="2" placeholder="Enter cancellation reason..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                                        Keep Subscription
                                    </button>
                                    <button type="submit" class="btn btn-danger ms-auto">
                                        Cancel Subscription
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
