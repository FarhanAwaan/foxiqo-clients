@extends('emails.layouts.base')

@section('title', 'Update to your subscription')

@section('footer_note', 'Questions? Just reply to this email.')

@section('content')
    @php
        $headline = match ($kind) {
            'trial_changed' => 'Your trial has been updated',
            'cancel_scheduled' => 'Your subscription is set to cancel',
            'cancel_withdrawn' => 'Your subscription will continue',
            default => 'Your subscription has been cancelled',
        };
    @endphp

    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">{{ $headline }}</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">Hi {{ $firstName }},</p>

    @if($kind === 'trial_changed')
        <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
            We've changed the date your free trial ends for <strong>{{ $deal->business_name }}</strong>.
            @if($previousChargeDate)
                It used to end on {{ $previousChargeDate }}; it now ends on <strong>{{ $chargeDate }}</strong>.
            @else
                It now ends on <strong>{{ $chargeDate }}</strong>.
            @endif
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#e7f0ff; border-radius:6px; margin:0 0 24px;">
            <tr>
                <td style="padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">First charge</td>
                            <td style="padding:4px 0; color:#1a1a2e; font-weight:700; text-align:right;">{{ $chargeDate }}</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Amount</td>
                            <td style="padding:4px 0; color:#1a1a2e; font-weight:700; text-align:right;">{{ $amount }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <p style="color:#495057; margin:0; line-height:1.6;">Nothing is charged before that date, and there's nothing you need to do.</p>

    @elseif($kind === 'cancel_scheduled')
        <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
            As requested, the subscription for <strong>{{ $deal->business_name }}</strong> is set to cancel on <strong>{{ $endDate }}</strong>. You won't be charged again.
        </p>
        <p style="color:#495057; margin:0; line-height:1.6;">Changed your mind? Reply to this email before then and we'll keep it going.</p>

    @elseif($kind === 'cancel_withdrawn')
        <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
            Good news &mdash; the cancellation for <strong>{{ $deal->business_name }}</strong> has been withdrawn and your subscription carries on as normal.
        </p>
        @if($chargeDate !== '—')
            <p style="color:#495057; margin:0; line-height:1.6;">Your next charge of {{ $amount }} is on <strong>{{ $chargeDate }}</strong>.</p>
        @endif

    @else
        <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
            The subscription for <strong>{{ $deal->business_name }}</strong> has been cancelled and <strong>you won't be charged again</strong>.
        </p>
        <p style="color:#495057; margin:0; line-height:1.6;">If this wasn't what you expected, or you'd like to start again, reply to this email.</p>
    @endif
@endsection
