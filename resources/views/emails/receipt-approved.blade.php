@extends('emails.layouts.base')

@section('title', 'Receipt Approved')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Receipt Approved</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Your payment receipt has been reviewed and approved. Your invoice has been marked as paid and your subscription is active.
    </p>

    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#d1fae5; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Invoice Number</td>
                        <td style="padding:4px 0; color:#065f46; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Amount</td>
                        <td style="padding:4px 0; color:#065f46; font-weight:600; text-align:right; font-size:18px;">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Status</td>
                        <td style="padding:4px 0; color:#065f46; font-weight:600; text-align:right;">Paid</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Approved On</td>
                        <td style="padding:4px 0; color:#065f46; text-align:right;">{{ $receipt->reviewed_at->format('M d, Y h:i A') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        Thank you for your payment. Your subscription services will continue uninterrupted.
    </p>
@endsection
