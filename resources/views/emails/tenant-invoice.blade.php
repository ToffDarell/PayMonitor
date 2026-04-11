@php
    $isOverdue = $variant === 'overdue' || $invoice->status === 'overdue';
    $heading = match ($variant) {
        'due_soon' => 'Your subscription is due in 7 days.',
        'urgent' => 'Your subscription is due in 3 days.',
        'overdue' => 'Your subscription is now overdue.',
        default => 'Your PayMonitor billing invoice is ready.',
    };
    $body = match ($variant) {
        'due_soon' => 'Please settle your subscription to avoid interruption.',
        'urgent' => 'Please settle your subscription as soon as possible to avoid disruption.',
        'overdue' => 'Your account may be suspended if payment is not settled immediately.',
        default => 'Please settle before the due date to avoid suspension.',
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2ff;font-family:Inter,Arial,sans-serif;color:#0f172a;">
    <div style="max-width:720px;margin:0 auto;padding:32px 18px;">
        <div style="overflow:hidden;border-radius:24px;background:#ffffff;box-shadow:0 20px 50px rgba(15,23,42,0.12);">
            <div style="padding:24px 28px;background:#0f766e;color:#ecfeff;">
                <div style="font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;">PayMonitor Billing</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;font-weight:800;">{{ $heading }}</h1>
            </div>

            <div style="padding:28px;">
                <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Hello {{ $tenant->name }},</p>
                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#334155;">{{ $body }}</p>

                <div style="border:1px solid #dbeafe;border-radius:18px;padding:20px;background:#f8fafc;">
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Invoice Number</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Cooperative</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $tenant->name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Plan</td>
                            <td style="padding:8px 0;text-align:right;color:#0f172a;">{{ $tenant->plan?->name ?? 'No Plan' }} (P{{ number_format((float) ($tenant->plan?->price ?? 0), 2) }})</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Due Date</td>
                            <td style="padding:8px 0;text-align:right;font-weight:700;color:{{ $isOverdue ? '#dc2626' : '#0f172a' }};">
                                {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#475569;">Amount Due</td>
                            <td style="padding:8px 0;text-align:right;font-size:18px;font-weight:800;color:#0f766e;">
                                P{{ number_format((float) $invoice->amount, 2) }}
                            </td>
                        </tr>
                        @if($daysOverdue)
                            <tr>
                                <td style="padding:8px 0;color:#475569;">Days Overdue</td>
                                <td style="padding:8px 0;text-align:right;font-weight:700;color:#dc2626;">{{ $daysOverdue }} day(s)</td>
                            </tr>
                        @endif
                    </table>
                </div>

                <div style="margin-top:22px;border-radius:18px;background:#fefce8;padding:18px 20px;">
                    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#a16207;">Payment Instructions</p>
                    <p style="margin:0;font-size:14px;line-height:1.7;color:#713f12;">
                        Please coordinate with the PayMonitor central administrator for payment processing and settle before the due date to avoid suspension.
                    </p>
                </div>

                @if(filled($invoice->notes))
                    <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                        <strong>Notes:</strong> {{ $invoice->notes }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
