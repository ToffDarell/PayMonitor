<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
</head>
<body style="margin:0;padding:0;background:#ecfdf5;font-family:Inter,Arial,sans-serif;color:#0f172a;">
    <div style="max-width:720px;margin:0 auto;padding:32px 18px;">
        <div style="overflow:hidden;border-radius:24px;background:#ffffff;box-shadow:0 20px 50px rgba(15,23,42,0.12);">
            <div style="padding:24px 28px;background:#166534;color:#ecfdf5;">
                <div style="font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;">Payment Receipt</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;font-weight:800;">Thank you for your payment.</h1>
            </div>

            <div style="padding:28px;">
                <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Hello {{ $tenant->name }},</p>
                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#334155;">
                    Your subscription payment has been recorded successfully and your PayMonitor subscription is active.
                </p>

                <div style="border:1px solid #dcfce7;border-radius:18px;padding:20px;background:#f8fafc;">
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Receipt Number</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Cooperative</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $tenant->name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Plan</td>
                            <td style="padding:8px 0;text-align:right;color:#0f172a;">{{ $tenant->plan?->name ?? 'No Plan' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Amount Paid</td>
                            <td style="padding:8px 0;text-align:right;font-size:18px;font-weight:800;color:#166534;">
                                P{{ number_format((float) $invoice->amount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Date Paid</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">
                                {{ $invoice->paid_at?->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Next Billing Date</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">
                                {{ $nextBillingDate?->format('M d, Y') ?? 'To be announced' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
