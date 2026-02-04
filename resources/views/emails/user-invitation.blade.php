@extends('emails.layouts.base')

@section('title', 'You\'re Invited')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">You've Been Invited!</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Hello {{ $user->first_name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        You have been invited to join <strong>{{ config('app.name', 'Foxiqo') }}</strong>
        @if($company)
            as a member of <strong>{{ $company->name }}</strong>
        @endif.
        Click the button below to set up your account.
    </p>

    <!-- CTA Button -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 24px;">
                <a href="{{ $signupUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Set Up Your Account
                </a>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7; border-radius:6px; margin:0 0 24px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="color:#92400e; margin:0; font-size:13px;">
                    This invitation link will expire in <strong>7 days</strong>. If it expires, please ask your administrator to resend the invitation.
                </p>
            </td>
        </tr>
    </table>

    <p style="color:#6c757d; margin:0; font-size:12px; text-align:center;">
        If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{{ $signupUrl }}" style="color:#4361ee; word-break:break-all;">{{ $signupUrl }}</a>
    </p>
@endsection
