@extends('emails.layouts.base')

@section('title', 'Your Trial Is Ending Soon')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Your Trial Ends in {{ $daysLeft }} Day(s)</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        This is a friendly reminder that your free trial for <strong>{{ $agent->name }}</strong> will end on <strong>{{ $subscription->trial_ends_at->format('M d, Y') }}</strong>.
    </p>

    <!-- Trial Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fff3e0; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">Assistant</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">Monthly Price (after trial)</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">${{ number_format($subscription->getEffectivePrice(), 2) }}/mo</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">Trial Ends</td>
                        <td style="padding:4px 0; color:#e65100; font-weight:700; text-align:right;">{{ $subscription->trial_ends_at->format('M d, Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        When your trial ends, an invoice will be automatically generated and sent to you with a payment link. Your assistant will continue running while your payment is being processed.
    </p>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you have any questions, please don't hesitate to reach out.
    </p>
@endsection
