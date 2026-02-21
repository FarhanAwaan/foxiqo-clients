@extends('emails.layouts.base')

@section('title', 'Your Free Trial Has Started')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Your Free Trial Has Started!</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Great news! Your <strong>{{ $subscription->trial_days }}-day free trial</strong> for <strong>{{ $agent->name }}</strong> is now active. You can start using your AI assistant right away — no payment required during the trial period.
    </p>

    <!-- Trial Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#e8f5e9; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#2e7d32; font-size:13px;">Assistant</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#2e7d32; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#2e7d32; font-size:13px;">Included Minutes</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ number_format($plan->included_minutes) }} min</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#2e7d32; font-size:13px;">Trial Period</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $subscription->trial_days }} days</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#2e7d32; font-size:13px;">Trial Ends</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $subscription->trial_ends_at->format('M d, Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        When your trial ends, we'll send you an invoice to continue the service. You'll have a grace period to complete payment before the assistant is paused.
    </p>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you have any questions during your trial, please don't hesitate to reach out.
    </p>
@endsection
