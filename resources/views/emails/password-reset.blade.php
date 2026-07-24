@extends('emails.layouts.base')

@section('title', 'Reset Your Password')

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">Reset Your Password</h2>

    <p style="color:#495057; margin:0 0 16px; line-height:1.6;">
        Hi {{ $user->first_name }},
    </p>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">
        An administrator has initiated a password reset for your account. Click the button below to set a new password. This link expires in 24 hours.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:0 0 24px;">
                <a href="{{ $resetUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                    Set New Password
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#495057; margin:0 0 8px; line-height:1.6; font-size:13px;">
        If the button doesn't work, copy and paste this link into your browser:
    </p>
    <p style="color:#4361ee; margin:0 0 24px; line-height:1.6; font-size:13px; word-break:break-all;">
        {{ $resetUrl }}
    </p>

    <p style="color:#495057; margin:0; line-height:1.6; font-size:13px;">
        If you didn't expect this, please contact your administrator — your password has not been changed yet.
    </p>
@endsection
