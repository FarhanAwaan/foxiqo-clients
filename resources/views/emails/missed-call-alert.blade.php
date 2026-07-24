@extends('emails.layouts.base')

@section('title', 'Missed Call')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Missed Call — {{ $agent->name }}</h2>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        {{ $agent->name }} was unable to complete a call with a caller. Details below.
    </p>

    <!-- Call Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fff7ed; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#9a3412; font-size:13px;">Caller Number</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $callLog->from_number ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#9a3412; font-size:13px;">Reason</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $reasonLabel }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#9a3412; font-size:13px;">Time</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ ($callLog->started_at ?? $callLog->ended_at)?->format('M d, Y g:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#9a3412; font-size:13px;">Assistant</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $agent->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        You're receiving this because missed-call email alerts are enabled for {{ $agent->name }}. You can turn these off any time from the agent's settings.
    </p>
@endsection
