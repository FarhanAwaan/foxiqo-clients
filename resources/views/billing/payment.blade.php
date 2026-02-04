@extends('layouts.billing')

@section('title', 'Pay Invoice ' . $invoice->invoice_number)

@section('content')
<div class="payment-card">
    <div class="card">
        <!-- Invoice Summary Header -->
        <div class="invoice-summary p-4">
            <div class="row align-items-center">
                <div class="col">
                    <div class="text-white-50 small">Invoice</div>
                    <div class="h3 mb-0">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="col-auto text-end">
                    <div class="text-white-50 small">Amount Due</div>
                    <div class="h2 mb-0">${{ number_format($invoice->amount, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Invoice Details -->
            <div class="mb-4">
                <h3 class="card-title">Invoice Details</h3>
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Company</div>
                        <div class="datagrid-content">{{ $invoice->company->name }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Billing Period</div>
                        <div class="datagrid-content">
                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Due Date</div>
                        <div class="datagrid-content">
                            {{ $invoice->due_date->format('M d, Y') }}
                            @if($invoice->isOverdue())
                                <span class="badge bg-danger ms-1">Overdue</span>
                            @endif
                        </div>
                    </div>
                    @if($invoice->subscription?->agent)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Service</div>
                            <div class="datagrid-content">
                                {{ $invoice->subscription->agent->name }} ({{ $invoice->subscription->plan->name }})
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <hr class="my-4">

            <!-- Payment Method Selection -->
            <h3 class="card-title">Select Payment Method</h3>

            <form action="{{ route('billing.payment.process', $paymentLink->payment_token) }}" method="POST" id="paymentForm">
                @csrf

                <div class="row g-3 mb-4">
                    <!-- Bank Transfer Option -->
                    <div class="col-md-6">
                        <label class="payment-method-card card h-100">
                            <input type="radio" name="payment_method" value="bank_transfer" required>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 10l18 0" /><path d="M5 6l7 -3l7 3" /><path d="M4 10l0 11" /><path d="M20 10l0 11" /><path d="M8 14l0 3" /><path d="M12 14l0 3" /><path d="M16 14l0 3" /></svg>
                                </div>
                                <h4 class="mb-1">Bank Transfer</h4>
                                <p class="text-muted mb-0 small">Pay via wire transfer or direct deposit</p>
                            </div>
                        </label>
                    </div>

                    <!-- Card Payment Option (Coming Soon) -->
                    <div class="col-md-6">
                        <label class="payment-method-card card h-100 opacity-50">
                            <input type="radio" name="payment_method" value="card" disabled>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg>
                                </div>
                                <h4 class="mb-1">Credit/Debit Card</h4>
                                <p class="text-muted mb-0 small">Coming soon</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Continue to Payment
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M13 18l6 -6" /><path d="M13 6l6 6" /></svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="card-footer text-center text-muted">
            <small>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>
                Your payment information is secure and encrypted
            </small>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.payment-method-card');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.disabled) {
                cards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            }
        });
    });
});
</script>
@endpush
