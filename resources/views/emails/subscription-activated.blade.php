@extends('emails.layouts.base')

@section('title', 'Subscription Active')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Your Subscription is Active!</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Dear {{ $company->name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Great news — your subscription has been activated. Your AI agent is ready to go.
    </p>

    <!-- Subscription Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Agent</td>
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
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Billing Period</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $subscription->current_period_start->format('M d') }} — {{ $subscription->current_period_end->format('M d, Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Invoice Note -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#e7f0ff; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="color:#1a4b8c; margin:0; font-size:13px;">
                    <strong>Invoice {{ $invoice->invoice_number }}</strong> has been created for ${{ number_format($invoice->amount, 2) }}.
                    @if($invoice->due_date)
                        Payment is due by {{ $invoice->due_date->format('M d, Y') }}.
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        You can manage your subscription and view your agents from your dashboard. If you have any questions, please don't hesitate to reach out.
    </p>
@endsection
