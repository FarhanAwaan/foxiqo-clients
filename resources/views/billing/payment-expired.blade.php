@extends('layouts.billing')

@section('title', 'Payment Link Expired')

@section('content')
<div class="payment-card">
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <span class="avatar avatar-xl bg-warning-lt rounded-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 8l0 4l2 2" /><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" /></svg>
                </span>
            </div>

            <h1 class="h2 mb-2">Payment Link Expired</h1>
            <p class="text-muted mb-4">
                This payment link has expired and is no longer valid.
            </p>

            <div class="card bg-light mb-4">
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
                                <div class="text-muted small">Amount Due</div>
                                <div class="fw-bold">${{ number_format($invoice->amount, 2) }}</div>
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
                                <div class="text-muted small">Expired On</div>
                                <div class="fw-bold text-danger">{{ $paymentLink->expires_at?->format('M d, Y \a\t g:i A') ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info text-start">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">Need a new payment link?</h4>
                        <div class="text-secondary">
                            Please contact our support team or your account manager to request a new payment link for this invoice.
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn-list justify-content-center">
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}?subject=Request New Payment Link - {{ $invoice->invoice_number }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                    Request New Payment Link
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
