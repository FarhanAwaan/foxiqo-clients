@extends('emails.layouts.base')

@section('title', 'Usage charge')

@section('footer_note', 'Questions about this charge? Just reply to this email.')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Usage charge</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">Dear {{ $company->name }},</p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        On top of your regular monthly retainer (charged separately), the card on file was charged <strong>{{ $amount }}</strong> for <strong>{{ $assistantName }}</strong>'s call usage
        @if($period)
            for <strong>{{ $period }}</strong>
        @endif
        . There is nothing you need to do; this is just your confirmation.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Minutes used</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ number_format($minutes) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Rate</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $rate }} / minute</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Amount charged</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right; font-size:18px;">{{ $amount }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Invoice</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#6c757d; margin:0; line-height:1.6; font-size:13px;">
        Paddle, our payment provider, also emails you an official receipt for this payment. You can view this invoice in your customer dashboard at any time.
    </p>
@endsection
