@extends('emails.layouts.base')

@section('title', 'Your order is ready')

@section('footer_note', 'Questions? Just reply to this email.')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">
        {{ $deal->isRetainerDeal() ? 'Your monthly retainer is ready' : 'Your order is ready' }}
    </h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">Hi {{ $firstName }},</p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        @if($deal->isRetainerDeal())
            Your assistant for <strong>{{ $deal->business_name }}</strong> is ready, so here is the monthly retainer we agreed. The last step is a secure checkout that takes about two minutes.
        @else
            Thanks for speaking with us. Everything we agreed for <strong>{{ $deal->business_name }}</strong> is below &mdash; the last step is a secure checkout that takes about two minutes.
        @endif
    </p>

    @include('emails.partials.deal-summary', ['rows' => $rows])

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 24px;">
                <a href="{{ $checkoutUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Complete Secure Checkout
                </a>
            </td>
        </tr>
    </table>

    <h3 style="color:#1a1a2e; margin:0 0 12px; font-size:15px;">What happens next</h3>
    <ol style="color:#495057; margin:0 0 24px; padding-left:20px; line-height:1.7; font-size:14px;">
        <li>You complete checkout. Payments are handled securely by Paddle, our payment provider, and they email you the receipt.</li>
        @unless($deal->isRetainerDeal())
            <li>We email you a link to set up your portal login.</li>
            <li>We build and configure your AI receptionist, and keep you posted as it comes together.</li>
        @endunless
        @if(!$deal->isSetupOnly() && $deal->is_trial)
            <li>Your first monthly charge happens automatically when your free trial ends. We remind you a few days before, and you can ask us to move the date or cancel any time before it.</li>
        @elseif($deal->isSetupOnly())
            <li>Once you're happy with your assistant, we agree the monthly retainer and send you a separate link for it.</li>
        @endif
    </ol>

    <p style="color:#6c757d; margin:0; font-size:12px; text-align:center;">
        If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{{ $checkoutUrl }}" style="color:#4361ee; word-break:break-all;">{{ $checkoutUrl }}</a>
    </p>
@endsection
