@extends('emails.layouts.base')

@section('title', 'New Subscription — Payment Required')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">New Subscription — Payment Required</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        A new subscription has been set up for you. Please review the details below and complete your payment to activate the service.
    </p>

    <!-- Subscription Details -->
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
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $plan->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Monthly Price</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">${{ number_format($subscription->getEffectivePrice(), 2) }}/mo</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Included Minutes</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ number_format($plan->included_minutes) }} min</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Invoice & Payment Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#e7f0ff; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Invoice Number</td>
                        <td style="padding:4px 0; color:#1a4b8c; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Amount Due</td>
                        <td style="padding:4px 0; color:#1a4b8c; font-weight:600; text-align:right; font-size:18px;">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Due Date</td>
                        <td style="padding:4px 0; color:#1a4b8c; font-weight:600; text-align:right;">{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Pay Now Button -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 24px;">
                <a href="{{ $paymentUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Pay Now
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#6c757d; margin:0 0 16px; font-size:12px; text-align:center;">
        If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{{ $paymentUrl }}" style="color:#4361ee; word-break:break-all;">{{ $paymentUrl }}</a>
    </p>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        Your subscription will be activated once your payment is received and verified. If you have any questions, please don't hesitate to reach out.
    </p>
@endsection
