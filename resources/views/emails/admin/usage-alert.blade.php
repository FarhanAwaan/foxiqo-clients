@extends('emails.layouts.base')

@section('title', 'Usage Alert')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Usage Alert: Circuit Breaker Triggered</h2>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        An AI agent has exceeded the usage threshold and the circuit breaker has been triggered.
    </p>

    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fee2e2; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Company</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">{{ $company->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Agent</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#991b1b; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Minutes Used</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">{{ number_format($subscription->minutes_used, 1) }} / {{ number_format($plan->included_minutes) }} min</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Usage</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right; font-size:18px;">{{ number_format($usagePercentage, 0) }}%</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        The agent has been automatically restricted to prevent further overuse. Please review the subscription and take appropriate action (upgrade plan, contact the customer, or adjust the circuit breaker threshold).
    </p>
@endsection
