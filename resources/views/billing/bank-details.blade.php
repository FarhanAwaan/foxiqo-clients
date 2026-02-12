@extends('layouts.billing')

@section('title', 'Bank Transfer Details')

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
            <!-- Rejected Receipt Alert -->
            @if(isset($rejectedReceipt) && $rejectedReceipt)
                <div class="alert alert-danger mb-4">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                        </div>
                        <div>
                            <h4 class="alert-title">Your Previous Receipt Was Rejected</h4>
                            <div class="text-secondary">
                                @if($rejectedReceipt->rejection_reason)
                                    <strong>Reason:</strong> {{ $rejectedReceipt->rejection_reason }}
                                @else
                                    Your payment receipt could not be verified. Please upload a valid receipt.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="alert alert-info mb-4">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">Bank Transfer Instructions</h4>
                        <div class="text-secondary">
                            Please transfer the exact amount to the bank account below. Include the invoice number as the payment reference. After making the payment, upload your receipt for verification.
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="card-title">Bank Account Details</h3>

            <div class="card bg-light mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Bank Name</label>
                                <div class="h4 mb-0" id="bankName">{{ config('billing.bank_name', 'Your Bank Name') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Account Holder</label>
                                <div class="h4 mb-0" id="accountHolder">{{ config('billing.account_holder', 'Your Company Name') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Account Number</label>
                                <div class="d-flex align-items-center">
                                    <div class="h4 mb-0 me-2 font-monospace" id="accountNumber">{{ config('billing.account_number', '1234567890') }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('accountNumber', event)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Routing Number / SWIFT</label>
                                <div class="d-flex align-items-center">
                                    <div class="h4 mb-0 me-2 font-monospace" id="routingNumber">{{ config('billing.routing_number', 'ABCD1234') }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('routingNumber', event)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label text-muted small mb-1">Amount to Transfer</label>
                                <div class="d-flex align-items-center">
                                    <div class="h3 mb-0 me-2 text-primary" id="amount">${{ number_format($invoice->amount, 2) }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('amount', event)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label text-muted small mb-1">Payment Reference</label>
                                <div class="d-flex align-items-center">
                                    <div class="h3 mb-0 me-2 font-monospace" id="reference">{{ $invoice->invoice_number }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('reference', event)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipt Upload Section -->
            <div class="card bg-success-lt mb-4">
                <div class="card-body">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M12 11l0 6" /><path d="M9 14l3 -3l3 3" /></svg>
                        Upload Payment Receipt
                    </h3>
                    <p class="text-muted mb-3">
                        After completing your bank transfer, upload a screenshot or photo of your payment receipt. This helps us verify and process your payment faster.
                    </p>

                    <form action="{{ route('billing.payment.upload-receipt', $paymentLink->payment_token) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label required">Payment Receipt</label>
                            <input type="file" name="receipt" class="form-control @error('receipt') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                            @error('receipt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">Accepted formats: JPG, PNG, PDF (Max 10MB)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Notes (Optional)</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="e.g., Transfer made from ABC Bank, Transaction ID: 12345">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>
                            Upload Receipt
                        </button>
                    </form>
                </div>
            </div>

            <div class="alert alert-warning mb-0">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                    </div>
                    <div>
                        <strong>Important:</strong> Please include the invoice number <code>{{ $invoice->invoice_number }}</code> as your payment reference. This helps us identify and process your payment quickly.
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('billing.payment.show', $paymentLink->payment_token) }}" class="btn btn-outline-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg>
                    Back to Payment Options
                </a>
            </div>
        </div>

        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col">
                    <span class="text-muted">Need help?</span>
                </div>
                <div class="col-auto">
                    <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}" class="btn btn-outline-primary btn-sm">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

