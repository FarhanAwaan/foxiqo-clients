@extends('layouts.billing')

@section('title', 'Receipt Uploaded')

@section('content')
<div class="payment-card">
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <span class="avatar avatar-xl bg-success-lt rounded-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                </span>
            </div>

            <h1 class="h2 mb-2">Thank You!</h1>
            <p class="text-muted mb-4">
                Your payment receipt has been uploaded successfully. Our team will review it shortly.
            </p>

            <div class="card bg-light mb-4 text-start">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="text-muted small">Invoice Number</div>
                                <div class="fw-bold">{{ $invoice->invoice_number }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="text-muted small">Amount</div>
                                <div class="fw-bold">${{ number_format($invoice->amount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="text-muted small">Company</div>
                                <div class="fw-bold">{{ $invoice->company->name }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="text-muted small">Receipt Status</div>
                                <div>
                                    <span class="badge bg-warning text-white">Pending Review</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-0">
                                <div class="text-muted small">File Uploaded</div>
                                <div class="fw-bold">{{ $receipt->original_filename }}</div>
                                <div class="text-muted small">{{ $receipt->getFormattedFileSize() }} - Uploaded {{ $receipt->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info text-start mb-4">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">What happens next?</h4>
                        <div class="text-secondary">
                            <ol class="mb-0 ps-3">
                                <li>Our team will verify your payment receipt</li>
                                <li>Once approved, your invoice will be marked as paid</li>
                                <li>Your subscription will be activated automatically</li>
                                <li>You'll receive a confirmation email</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-4">
                Verification usually takes 1-2 business days. If you have any questions, please contact our support team.
            </p>

            <div class="btn-list justify-content-center">
                <a href="{{ route('billing.payment.show', $paymentLink->payment_token) }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" /></svg>
                    Check Status
                </a>
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
