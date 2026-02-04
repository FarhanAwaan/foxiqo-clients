@extends('emails.layouts.base')

@section('title', 'Payment Confirmed')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Payment Confirmed</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        We have received your payment. Thank you for your prompt payment.
    </p>

    <!-- Payment Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#d1fae5; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Invoice Number</td>
                        <td style="padding:4px 0; color:#065f46; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Amount Paid</td>
                        <td style="padding:4px 0; color:#065f46; font-weight:600; text-align:right; font-size:18px;">${{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Payment Method</td>
                        <td style="padding:4px 0; color:#065f46; text-align:right;">{{ ucfirst(str_replace('_', ' ', $payment->provider)) }}</td>
                    </tr>
                    @if($payment->provider_transaction_id)
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Transaction ID</td>
                        <td style="padding:4px 0; color:#065f46; text-align:right; font-family:monospace; font-size:12px;">{{ $payment->provider_transaction_id }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 0; color:#065f46; font-size:13px;">Payment Date</td>
                        <td style="padding:4px 0; color:#065f46; text-align:right;">{{ $payment->paid_at->format('M d, Y h:i A') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        This email serves as your payment confirmation. If you have any questions, please contact our support team.
    </p>
@endsection
