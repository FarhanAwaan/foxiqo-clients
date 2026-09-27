@extends('emails.layouts.base')

@section('title', 'Payment received')

@section('footer_note', 'Questions about this charge? Just reply to this email.')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">
        {{ $convertedFromTrial ? 'Your free trial has ended' : 'Payment received' }}
    </h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">{{ $greeting }},</p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        @if($convertedFromTrial)
            Your free trial for <strong>{{ $subjectName }}</strong> is over, and the card you entered at checkout was charged <strong>{{ $amount }}</strong> for your first month.
        @elseif($usageInvoice)
            Your payment for <strong>{{ $subjectName }}</strong> went through &mdash; the card on file was charged <strong>{{ $totalAmount }}</strong>: your monthly retainer plus this period's call usage, billed together.
        @else
            Your monthly payment for <strong>{{ $subjectName }}</strong> went through &mdash; the card on file was charged <strong>{{ $amount }}</strong>.
        @endif
        There is nothing you need to do; this is just your confirmation.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    @if($usageInvoice)
                        <tr>
                            <td style="padding:4px 0; color:#6c757d; font-size:13px;">Monthly retainer</td>
                            <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $amount }}</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0; color:#6c757d; font-size:13px;">Usage &middot; {{ number_format($usageInvoice->usage_minutes) }} min</td>
                            <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $usageAmount }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0 4px; color:#1a1a2e; font-size:13px; font-weight:600; border-top:1px solid #e9ecef;">Total charged</td>
                            <td style="padding:8px 0 4px; color:#1a1a2e; font-weight:600; text-align:right; font-size:18px; border-top:1px solid #e9ecef;">{{ $totalAmount }}</td>
                        </tr>
                    @else
                        <tr>
                            <td style="padding:4px 0; color:#6c757d; font-size:13px;">Amount charged</td>
                            <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right; font-size:18px;">{{ $amount }}</td>
                        </tr>
                    @endif
                    @if($invoice->billing_period_start && $invoice->billing_period_end)
                        <tr>
                            <td style="padding:4px 0; color:#6c757d; font-size:13px;">Covers</td>
                            <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $invoice->billing_period_start->format('M j') }} &ndash; {{ $invoice->billing_period_end->format('M j, Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Invoice{{ $usageInvoice ? 's' : '' }}</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}{{ $usageInvoice ? ', ' . $usageInvoice->invoice_number : '' }}</td>
                    </tr>
                    @if($nextChargeDate)
                        <tr>
                            <td style="padding:4px 0; color:#6c757d; font-size:13px;">Next charge</td>
                            <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $nextChargeDate }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#6c757d; margin:0; line-height:1.6; font-size:13px;">
        Paddle, our payment provider, also emails you an official receipt for this payment. You can view this invoice in your customer dashboard at any time.
    </p>
@endsection
