@extends('emails.layouts.base')

@section('title', 'Payment Reminder')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Payment Reminder</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        This is a friendly reminder that your invoice is
        @if($invoice->due_date && $invoice->due_date->isPast())
            <strong style="color:#dc2626;">overdue</strong>.
        @else
            due soon.
        @endif
        Please arrange payment at your earliest convenience.
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
                        <td style="padding:4px 0; color:{{ $invoice->due_date->isPast() ? '#dc2626' : '#1a1a2e' }}; font-weight:600; text-align:right;">
                            {{ $invoice->due_date->format('M d, Y') }}
                            @if($invoice->due_date->isPast())
                                ({{ $invoice->due_date->diffForHumans() }})
                            @endif
                        </td>
                    </tr>
                    @endif
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
                        Pay Now
                    </a>
                </td>
            </tr>
        </table>
    @endif

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you have already made a payment, please disregard this reminder. For questions, contact our support team.
    </p>
@endsection
