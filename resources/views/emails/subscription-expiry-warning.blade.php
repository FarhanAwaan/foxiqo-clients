@extends('emails.layouts.base')

@section('title', 'Subscription Expiring Soon')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Subscription Expiring Soon</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Your subscription is expiring in <strong>{{ $daysRemaining }} day{{ $daysRemaining != 1 ? 's' : '' }}</strong>. If your subscription is not renewed, the associated AI agent will be deactivated.
    </p>

    <!-- Subscription Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#92400e; font-size:13px;">Agent</td>
                        <td style="padding:4px 0; color:#92400e; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#92400e; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#92400e; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#92400e; font-size:13px;">Expires On</td>
                        <td style="padding:4px 0; color:#92400e; font-weight:600; text-align:right;">{{ $subscription->current_period_end->format('M d, Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you have already made a payment, please disregard this email. Otherwise, please ensure your payment is completed before the expiry date to avoid service interruption.
    </p>
@endsection
