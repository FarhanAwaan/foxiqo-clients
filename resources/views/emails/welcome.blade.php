@extends('emails.layouts.base')

@section('title', 'Welcome')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Welcome to {{ config('app.name', 'Foxiqo') }}!</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Hello {{ $user->full_name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        Your account has been successfully activated. You're all set to get started
        @if($company)
            with <strong>{{ $company->name }}</strong>
        @endif.
    </p>

    <!-- Account Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Name</td>
                        <td style="padding:4px 0; color:#1a1a2e; font-weight:600; text-align:right;">{{ $user->full_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Email</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $user->email }}</td>
                    </tr>
                    @if($company)
                    <tr>
                        <td style="padding:4px 0; color:#6c757d; font-size:13px;">Company</td>
                        <td style="padding:4px 0; color:#1a1a2e; text-align:right;">{{ $company->name }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 24px;">
                <a href="{{ $dashboardUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Go to Dashboard
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you need any help getting started, feel free to reach out to our support team.
    </p>
@endsection
