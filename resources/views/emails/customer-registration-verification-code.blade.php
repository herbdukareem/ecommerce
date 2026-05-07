<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#111827;padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 6px;font-size:13px;letter-spacing:1.6px;text-transform:uppercase;color:#f97316;font-weight:700;">Email verification</p>
                            <h1 style="margin:0;font-size:26px;line-height:1.25;">Confirm your customer account</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $name }},</p>
                            <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b5563;">
                                Use the verification code below to complete your registration. This helps us protect your account and make sure order updates reach the right email address.
                            </p>

                            <div style="margin:24px 0;padding:22px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa;text-align:center;">
                                <p style="margin:0 0 10px;font-size:12px;text-transform:uppercase;letter-spacing:1.5px;color:#9a3412;font-weight:700;">Your verification code</p>
                                <div style="font-size:34px;letter-spacing:8px;font-weight:800;color:#111827;">{{ $code }}</div>
                            </div>

                            <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#4b5563;">
                                This code expires in {{ $expiresInMinutes }} minutes. If you did not start this registration, you can safely ignore this email.
                            </p>

                            <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#6b7280;">
                                Thank you,<br>
                                {{ config('app.name') }} Team
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.6;">
                            This message was sent by {{ config('app.name') }}. Please do not share your verification code with anyone.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
