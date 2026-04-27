<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Overdue Notice</title>
</head>
<body style="margin:0; padding:24px; background-color:#f3f4f6; color:#111827; font-family:Arial, Helvetica, sans-serif;">
@php
    $settings = \App\Models\TenantSetting::allKeyed();
    $daysOverdue = $loan->due_date?->diffInDays(today()) ?? 0;
    $supportEmail = $settings['contact_email'] ?? (tenant()?->email ?? config('mail.from.address'));
    $supportPhone = $settings['contact_number'] ?? config('app.support_phone', '');
    $cooperativeName = tenant()?->name ?? 'PayMonitor Cooperative';
    $cooperativeAddress = $settings['address'] ?? (tenant()?->address ?? '');
@endphp
    <div style="max-width:680px; margin:0 auto; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 34px rgba(15,23,42,0.10);">
        <div style="background-color:#991b1b; padding:22px 28px; color:#ffffff;">
            <div style="font-size:12px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.85;">Formal Notice</div>
            <h1 style="margin:8px 0 0; font-size:28px; line-height:1.2;">OVERDUE NOTICE</h1>
        </div>

        <div style="padding:28px;">
            <p style="margin-top:0;">Dear <strong>{{ $member->full_name }}</strong>,</p>
            <p>This is a formal notice that your loan is past due.</p>

            <table style="width:100%; border-collapse:collapse; margin:24px 0; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                <tbody>
                    <tr style="background-color:#f9fafb;">
                        <td style="padding:12px 16px; color:#6b7280; width:48%;">Loan Number</td>
                        <td style="padding:12px 16px; text-align:right; font-weight:700;">{{ $loan->loan_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px; color:#6b7280;">Original Amount</td>
                        <td style="padding:12px 16px; text-align:right;">&#8369;{{ number_format((float) $loan->principal_amount, 2) }}</td>
                    </tr>
                    <tr style="background-color:#f9fafb;">
                        <td style="padding:12px 16px; color:#6b7280;">Outstanding Balance</td>
                        <td style="padding:12px 16px; text-align:right; color:#b91c1c; font-size:18px; font-weight:700;">&#8369;{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px; color:#6b7280;">Due Date</td>
                        <td style="padding:12px 16px; text-align:right;">
                            {{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}
                            @if($daysOverdue > 0)
                                <span style="display:block; color:#b91c1c; font-size:12px; font-weight:700;">{{ $daysOverdue }} day{{ $daysOverdue === 1 ? '' : 's' }} overdue</span>
                            @endif
                        </td>
                    </tr>
                    <tr style="background-color:#f9fafb;">
                        <td style="padding:12px 16px; color:#6b7280;">Monthly Payment</td>
                        <td style="padding:12px 16px; text-align:right;">&#8369;{{ number_format((float) $loan->monthly_payment, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-bottom:20px;">Please settle your outstanding balance immediately to avoid additional penalties.</p>
            <p style="margin-bottom:8px;">Contact us at {{ $supportEmail ?: 'our cooperative support email' }}{{ $supportPhone ? ' / '.$supportPhone : '' }}.</p>

            <div style="margin-top:28px; padding-top:18px; border-top:1px solid #e5e7eb; color:#6b7280; font-size:13px;">
                <strong style="display:block; color:#111827; margin-bottom:4px;">{{ $cooperativeName }}</strong>
                @if(filled($cooperativeAddress))
                    <div>{{ $cooperativeAddress }}</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
