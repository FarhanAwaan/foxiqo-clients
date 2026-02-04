@extends('emails.layouts.base')

@section('title', 'Subscription Renewed')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Subscription Renewed</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Your subscription has been automatically renewed for a new billing period.
    </p>

    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Agent</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Plan</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">New Period</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $subscription->current_period_start->format('M d') }} — {{ $subscription->current_period_end->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Invoice</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Amount</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right; font-size:18px;">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Due Date</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        A payment link will be sent separately. You can also view your invoice from your customer dashboard.
    </p>
@endsection
