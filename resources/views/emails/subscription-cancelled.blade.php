@extends('emails.layouts.base')

@section('title', 'Subscription Cancelled')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Subscription Cancelled</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Your subscription has been cancelled. The associated AI assistant has been deactivated.
    </p>

    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Assistant</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Cancelled On</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $subscription->cancelled_at->format('M d, Y') }}</td>
                    </tr>
                    @if($subscription->cancellation_reason)
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Reason</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $subscription->cancellation_reason }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you believe this was a mistake or would like to reactivate your subscription, please contact our support team.
    </p>
@endsection
