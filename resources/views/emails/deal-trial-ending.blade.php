@extends('emails.layouts.base')

@section('title', 'Your free trial is ending')

@section('footer_note', 'Questions or changes? Just reply to this email.')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">
        Your free trial ends {{ $daysLeft === null ? 'soon' : ($daysLeft <= 0 ? 'today' : ($daysLeft === 1 ? 'tomorrow' : "in {$daysLeft} days")) }}
    </h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">Hi {{ $firstName }},</p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        A quick heads-up about <strong>{{ $deal->business_name }}</strong>: on <strong>{{ $chargeDate }}</strong> your free trial ends and the card you entered at checkout is charged automatically for your first month. There is nothing you need to do to keep going.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fff3e0; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">First charge</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:700; text-align:right;">{{ $chargeDate }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">Amount</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:700; text-align:right;">{{ $amount }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#e65100; font-size:13px;">Then</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">Every month, automatically</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Need a few more days, or would you rather not continue? Just reply to this email <strong>before {{ $chargeDate }}</strong> and we'll sort it out &mdash; if you cancel before then, nothing is charged.
    </p>

    <p style="color:#6c757d; margin:0; line-height:1.6; font-size:13px;">
        Paddle, our payment provider, also emails you a reminder and your receipt.
    </p>
@endsection
