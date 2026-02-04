@extends('emails.layouts.base')

@section('title', 'Payment Required')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Payment Required</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        A new invoice requires your attention. Please review the details below and complete your payment.
    </p>

    <!-- Invoice Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Invoice Number</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Amount Due</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right; font-size:18px;">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Due Date</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                    @if($invoice->billing_period_start && $invoice->billing_period_end)
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Billing Period</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $invoice->billing_period_start->format('M d') }} — {{ $invoice->billing_period_end->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 24px;">
                <a href="{{ $paymentUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Pay Now
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#6c757d; margin:0; font-size:12px; text-align:center;">
        If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{{ $paymentUrl }}" style="color:#4361ee; word-break:break-all;">{{ $paymentUrl }}</a>
    </p>
@endsection
