@extends('emails.layouts.base')

@section('title', 'Payment received')

@section('footer_note', 'Questions? Just reply to this email.')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">
        {{ $deal->isRetainerDeal() ? 'Your monthly retainer is set up' : 'Payment received — welcome aboard' }}
    </h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">Hi {{ $firstName }},</p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Thank you &mdash; your payment for <strong>{{ $deal->business_name }}</strong> went through. Paddle will email your receipt separately.
    </p>

    @include('emails.partials.deal-summary', ['rows' => $rows])

    <h3 style="color:#1a1a2e; margin:0 0 12px; font-size:15px;">What happens next</h3>
    <ol style="color:#495057; margin:0 0 24px; padding-left:20px; line-height:1.7; font-size:14px;">
        @if($deal->isRetainerDeal())
            <li>Nothing else to do on your side &mdash; your assistant keeps running while billing continues automatically each month.</li>
        @else
            @if($invited)
                <li><strong>Set up your login.</strong> We just sent you a separate email with a link to create your password (it's valid for 7 days). Once in, you can see your assistant, its calls and your invoices at <a href="{{ $loginUrl }}" style="color:#4361ee;">{{ $loginUrl }}</a>.</li>
            @else
                <li><strong>Sign in as usual.</strong> This order is added to your existing account at <a href="{{ $loginUrl }}" style="color:#4361ee;">{{ $loginUrl }}</a>.</li>
            @endif
            <li><strong>We build your assistant.</strong> Our team configures your AI receptionist and will be in touch to confirm the details.</li>
        @endif
        @if($deal->isSetupOnly())
            <li><strong>Monthly retainer.</strong> Once you're happy with your assistant, we agree the monthly retainer and send you a separate checkout link. Nothing recurring is charged until then.</li>
        @elseif($deal->is_trial && $deal->nextChargeAt())
            <li><strong>Your free trial.</strong> Your first monthly charge of {{ \App\Support\DealTerms::money($deal->agreed_monthly_price) }} is on <strong>{{ \App\Support\BillingTime::date($deal->nextChargeAt()) }}</strong>, charged automatically to the card you entered. We remind you a few days before. If you need more time or don't want to continue, reply to this email before then &mdash; nothing is charged if you cancel first.</li>
        @elseif(!$deal->is_trial && $deal->next_billed_at)
            <li><strong>Monthly billing.</strong> Your first month was included above. The next charge of {{ \App\Support\DealTerms::money($deal->agreed_monthly_price) }} is on {{ \App\Support\BillingTime::date($deal->next_billed_at) }}, and then monthly.</li>
        @endif
    </ol>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        Reply to this email any time &mdash; it comes straight to us.
    </p>
@endsection
