<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing:antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f6f9; padding:40px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1a1a2e; padding:24px 32px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:22px; font-weight:600; letter-spacing:0.5px;">{{ config('app.name', 'Foxiqo') }}</h1>
                            <p style="color:#a0a0c0; margin:4px 0 0; font-size:12px;">Transform Your Business with Foxiqo</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa; padding:20px 32px; text-align:center; border-top:1px solid #e9ecef;">
                            <p style="margin:0 0 8px; font-size:12px; color:#6c757d;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Foxiqo') }}. All rights reserved.
                            </p>
                            <p style="margin:0; font-size:11px; color:#adb5bd;">
                                This is an automated notification. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
