<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f7fb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#166534,#15803d);padding:40px 32px;color:#ffffff;">
                            <p style="margin:0 0 12px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.85;">PayMonitor</p>
                            <h1 style="margin:0;font-size:30px;line-height:1.2;font-weight:700;">Payment Confirmed</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 20px;font-size:18px;line-height:1.6;">Hello {{ $application->admin_name }},</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#475569;">
                                We have verified the payment for your PayMonitor application for <strong style="color:#0f172a;">{{ $application->cooperative_name }}</strong>.
                            </p>

                            <div style="margin:0 0 24px;border:1px solid #dcfce7;border-radius:16px;padding:20px;background-color:#f8fafc;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                                    <tr>
                                        <td style="padding:8px 0;color:#64748b;">Cooperative</td>
                                        <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $application->cooperative_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#64748b;">Plan</td>
                                        <td style="padding:8px 0;text-align:right;color:#0f172a;">{{ $application->plan?->name ?? 'Selected Plan' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#64748b;">Amount Verified</td>
                                        <td style="padding:8px 0;text-align:right;font-size:18px;font-weight:800;color:#166534;">P{{ number_format((float) ($application->payment_amount ?? 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#64748b;">Payment Reference</td>
                                        <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $application->payment_reference ?: 'Not provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#64748b;">Verified At</td>
                                        <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $application->payment_verified_at?->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#64748b;">Status</td>
                                        <td style="padding:8px 0;text-align:right;font-weight:700;color:#166534;">Verified</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="border-radius:14px;background-color:#ecfeff;padding:18px 20px;">
                                <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#155e75;">Next step</p>
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#0f172a;">
                                    Your application is now ready for final admin approval. Once approved, you will receive a separate email with your tenant login URL and account access details.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b;text-align:center;">
                            PayMonitor System
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
