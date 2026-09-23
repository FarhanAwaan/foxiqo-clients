@extends('layouts.billing')

@section('title', 'Payment Method Unavailable')

@section('content')
<div class="payment-card">
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <span class="avatar avatar-xl bg-warning-lt rounded-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                </span>
            </div>

            <h1 class="h2 mb-2">Payment Temporarily Unavailable</h1>
            <p class="text-muted mb-4">
                This payment method isn't accepting payments right now. Please contact us and
                we'll get you a working payment link.
            </p>

            <div class="btn-list justify-content-center">
                <a href="mailto:{{ config('mail.from.address', 'support@example.com') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
