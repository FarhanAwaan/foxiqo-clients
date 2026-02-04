@extends('emails.layouts.base')

@section('title', 'Receipt Rejected')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Receipt Rejected</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Unfortunately, your payment receipt for invoice <strong>{{ $invoice->invoice_number }}</strong> could not be approved.
    </p>

    <!-- Rejection Reason -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fee2e2; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <p style="color:#991b1b; margin:0 0 4px; font-size:13px; font-weight:600;">Reason for Rejection:</p>
                <p style="color:#991b1b; margin:0; font-size:14px; line-height:1.5;">{{ $receipt->rejection_reason }}</p>
            </td>
        </tr>
    </table>

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
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($paymentUrl)
        <!-- CTA Button -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center" style="padding:0 0 24px;">
                    <a href="{{ $paymentUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                        Upload New Receipt
                    </a>
                </td>
            </tr>
        </table>
    @endif

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        Please upload a new receipt with the correct information. If you have any questions, contact our support team.
    </p>
@endsection
