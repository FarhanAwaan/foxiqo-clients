@extends('layouts.billing')

@section('title', 'Checkout — ' . $deal->business_name)

@section('content')
<div class="payment-card">
    <div class="card">
        <div class="invoice-summary p-4">
            <div class="row align-items-center">
                <div class="col">
                    <div class="text-white-50 small">{{ $deal->business_name }}</div>
                    <div class="h3 mb-0">Complete Your Purchase</div>
                </div>
                <div class="col-auto text-end">
                    <div class="text-white-50 small">Due Today</div>
                    <div class="h2 mb-0">${{ number_format($deal->agreed_monthly_price + $deal->activation_price, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4 text-start">
                <div class="col-6">
                    <div class="text-muted small">Monthly Subscription</div>
                    <div class="fw-bold">${{ number_format($deal->agreed_monthly_price, 2) }}/mo</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">One-time Activation</div>
                    <div class="fw-bold">${{ number_format($deal->activation_price, 2) }}</div>
                </div>
            </div>

            <div id="paddle-checkout-loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-3 mb-0">Loading secure checkout&hellip;</p>
            </div>

            {{-- Paddle.js's inline frameTarget looks up the target by CLASS, not id --}}
            <div id="paddle-checkout-container" class="paddle-checkout-container"></div>

            <div id="paddle-checkout-error" class="alert alert-danger d-none mt-3">
                Checkout failed to load. Please refresh the page or
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}">contact support</a>.
            </div>
        </div>

        <div class="card-footer text-center text-muted">
            <small>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>
                Payments are processed securely by Paddle
            </small>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        @if($paddleEnvironment !== 'live')
        Paddle.Environment.set('sandbox');
        @endif

        Paddle.Initialize({ token: @json($paddleClientToken) });

        Paddle.Checkout.open({
            transactionId: @json($deal->paddle_transaction_id),
            settings: {
                displayMode: 'inline',
                frameTarget: 'paddle-checkout-container',
                frameInitialHeight: 450,
                frameStyle: 'width: 100%; min-width: 280px; background-color: transparent; border: none;',
            },
        });

        document.getElementById('paddle-checkout-loading').remove();
    } catch (e) {
        console.error('Paddle checkout failed to open:', e);
        document.getElementById('paddle-checkout-loading').remove();
        document.getElementById('paddle-checkout-error').classList.remove('d-none');
    }
});
</script>
@endpush
