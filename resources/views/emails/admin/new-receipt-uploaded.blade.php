@extends('emails.layouts.base')

@section('title', 'New Receipt Uploaded')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">New Receipt Requires Review</h2>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        A customer has uploaded a payment receipt that requires your review.
    </p>

    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#e7f0ff; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Company</td>
                        <td style="padding:4px 0; color:#1a4b8c; font-weight:600; text-align:right;">{{ $company->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Invoice</td>
                        <td style="padding:4px 0; color:#1a4b8c; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Amount</td>
                        <td style="padding:4px 0; color:#1a4b8c; font-weight:600; text-align:right; font-size:18px;">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">File</td>
                        <td style="padding:4px 0; color:#1a4b8c; text-align:right;">{{ $receipt->original_filename }} ({{ $receipt->getFormattedFileSize() }})</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#1a4b8c; font-size:13px;">Uploaded</td>
                        <td style="padding:4px 0; color:#1a4b8c; text-align:right;">{{ $receipt->created_at->format('M d, Y h:i A') }}</td>
                    </tr>
                    @if($receipt->customer_notes)
                    <tr>
                        <td colspan="2" style="padding:8px 0 0; color:#1a4b8c; font-size:13px;">
                            <strong>Customer Notes:</strong><br>{{ $receipt->customer_notes }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 16px;">
                <a href="{{ $reviewUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Review Receipt
                </a>
            </td>
        </tr>
    </table>
@endsection
