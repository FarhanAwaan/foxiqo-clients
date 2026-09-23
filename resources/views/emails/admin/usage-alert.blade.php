@extends('emails.layouts.base')

@section('title', 'Usage Alert')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Usage Alert</h2>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        A customer has crossed your configured usage alert threshold for this billing period.
        This is informational only — the assistant keeps running and the customer hasn't been
        notified.
    </p>

    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fee2e2; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Customer</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">{{ $company->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Assistant</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#991b1b; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Minutes Used</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">{{ number_format($subscription->minutes_used, 1) }} min</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Usage Cost So Far</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right; font-size:18px;">${{ number_format($usageCost, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#991b1b; font-size:13px;">Predictable Cost This Period</td>
                        <td style="padding:4px 0; color:#991b1b; font-weight:600; text-align:right;">${{ number_format($predictedTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        Retainer (${{ number_format($plan->price, 2) }}) plus usage so far, at ${{ number_format($plan->per_minute_rate ?? 0, 4) }}/min. The actual usage invoice is calculated once this billing period closes.
    </p>
@endsection
