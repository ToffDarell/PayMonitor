<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to PayMonitor</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f7fb; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f766e, #155e75); padding: 40px 32px; color: #ffffff;">
                            <p style="margin: 0 0 12px; font-size: 14px; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.85;">PayMonitor</p>
                            <h1 style="margin: 0; font-size: 32px; line-height: 1.2; font-weight: 700;">{{ $tenant->name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 20px; font-size: 18px; line-height: 1.6;">Your application has been approved and your PayMonitor account is now ready.</p>
                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6;">Login URL:</p>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; font-weight: 700;">
                                <a href="{{ $loginUrl }}" style="color: #0f766e; text-decoration: none;">{{ $loginUrl }}</a>
                            </p>
                            <p style="margin: 0 0 8px; font-size: 15px; line-height: 1.6;"><strong>Email Address:</strong> {{ $email }}</p>
                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6;"><strong>Temporary Password:</strong></p>
                            <div style="margin: 0 0 24px; padding: 16px 18px; background-color: #0f172a; border-radius: 12px; font-family: 'Courier New', Courier, monospace; font-size: 18px; color: #e2e8f0; display: inline-block;">
                                {{ $password }}
                            </div>
                            <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #b91c1c;">Please log in and change your password immediately.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 13px; color: #64748b; text-align: center;">
                            PayMonitor System
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
