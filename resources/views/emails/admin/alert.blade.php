@extends('emails.layouts.base')

@section('title', $headline)

@section('content')
    <h2 style="color:#1a1a2e; margin:0 0 16px; font-size:20px;">{{ $headline }}</h2>

    <p style="color:#495057; margin:0 0 24px; line-height:1.6;">{{ $intro }}</p>

    @if(!empty($facts))
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $palette['bg'] }}; border-radius:6px; margin:0 0 24px;">
            <tr>
                <td style="padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        @foreach($facts as $label => $value)
                            <tr>
                                <td style="padding:4px 0; color:{{ $palette['fg'] }}; font-size:13px; vertical-align:top;">{{ $label }}</td>
                                <td style="padding:4px 0 4px 16px; color:#1a1a2e; font-weight:600; text-align:right; vertical-align:top;">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    @endif

    @if($ctaUrl)
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center" style="padding:0 0 8px;">
                    <a href="{{ $ctaUrl }}" style="background-color:#4361ee; color:#ffffff; padding:14px 36px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600; font-size:15px;">
                        {{ $ctaLabel ?? 'Open in portal' }}
                    </a>
                </td>
            </tr>
        </table>
    @endif
@endsection
