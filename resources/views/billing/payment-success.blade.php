@extends('layouts.billing')

@section('title', 'Payment Successful')

@section('content')
<div class="payment-card">
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <span class="avatar avatar-xl bg-success-lt rounded-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                </span>
            </div>

            <h1 class="h2 mb-2">Payment Successful!</h1>
            <p class="text-muted mb-4">
                Thank you for your payment. Your transaction has been completed successfully.
            </p>

            <div class="card bg-success-lt mb-4">
                <div class="card-body">
                    <div class="row text-start">
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="text-muted small">Invoice Number</div>
                                <div class="fw-bold">{{ $invoice->invoice_number }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="text-muted small">Amount Paid</div>
                                <div class="fw-bold text-success">${{ number_format($invoice->amount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-0">
                                <div class="text-muted small">Company</div>
                                <div class="fw-bold">{{ $invoice->company->name }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-0">
                                <div class="text-muted small">Payment Date</div>
                                <div class="fw-bold">{{ $invoice->paid_at?->format('M d, Y') ?? now()->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoice->payments->isNotEmpty())
                @php $payment = $invoice->payments->first(); @endphp
                @if($payment->provider_transaction_id)
                    <p class="text-muted small mb-4">
                        Transaction ID: <code>{{ $payment->provider_transaction_id }}</code>
                    </p>
                @endif
            @endif

            <p class="text-muted small mb-4">
                A confirmation email with your receipt has been sent to your email address on file.
            </p>

            <div class="btn-list justify-content-center">
                @auth
                    <a href="{{ route('customer.dashboard') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                        Go to Dashboard
                    </a>
                @endauth
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
