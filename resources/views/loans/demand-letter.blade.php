<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Demand Letter - {{ $loan->loan_number }}</title>
</head>
<body style="margin:38px 42px; font-family:DejaVu Sans, Arial, sans-serif; color:#111111; font-size:13px; line-height:1.55;">
@php
    $cooperativeName = tenant()?->name ?? 'PayMonitor Cooperative';
    $address = $settings['address'] ?? (tenant()?->address ?? '');
    $email = $settings['contact_email'] ?? (tenant()?->email ?? '');
    $phone = $settings['contact_number'] ?? '';
    $member = $loan->member;
    $role = auth()->user()?->getRoleNames()->first();
@endphp
    <table style="width:100%; border-bottom:2px solid #1f2937; padding-bottom:18px; margin-bottom:28px; border-collapse:collapse;">
        <tr>
            <td style="width:84px; vertical-align:top;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Logo" style="width:68px; height:68px; object-fit:contain;">
                @else
                    <div style="width:68px; height:68px; border:1px solid #cbd5e1; text-align:center; line-height:68px; font-weight:700; font-size:20px;">
                        {{ strtoupper(substr($cooperativeName, 0, 1)) }}
                    </div>
                @endif
            </td>
            <td style="vertical-align:top;">
                <div style="font-size:24px; font-weight:700; margin-bottom:4px;">{{ $cooperativeName }}</div>
                @if(filled($address))
                    <div>{{ $address }}</div>
                @endif
                @if(filled($phone))
                    <div>{{ $phone }}</div>
                @endif
                @if(filled($email))
                    <div>{{ $email }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div style="text-align:right; margin-bottom:28px;">Date: {{ now()->format('F d, Y') }}</div>

    <div style="margin-bottom:28px;">
        <div style="font-weight:700;">{{ $member?->full_name ?? 'Member' }}</div>
        <div>{{ $member?->address ?? 'Address not provided' }}</div>
    </div>

    <div style="font-weight:700; text-decoration:underline; margin-bottom:24px;">
        RE: DEMAND FOR PAYMENT - LOAN NO. {{ $loan->loan_number }}
    </div>

    <p>Dear {{ $member?->first_name ?? 'Member' }},</p>

    <p>
        This letter serves as a formal demand for the immediate settlement of your outstanding loan obligation
        with {{ $cooperativeName }}. Our records show that your account remains unpaid beyond its due date and
        is now considered seriously past due.
    </p>

    <div style="margin:24px 0;">
        <div style="font-weight:700; margin-bottom:10px;">LOAN DETAILS:</div>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="padding:4px 0; width:42%;">Loan Number:</td>
                <td style="padding:4px 0;">{{ $loan->loan_number }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Principal Amount:</td>
                <td style="padding:4px 0;">&#8369;{{ number_format((float) $loan->principal_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Total Payable:</td>
                <td style="padding:4px 0;">&#8369;{{ number_format((float) $loan->total_payable, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Amount Paid:</td>
                <td style="padding:4px 0;">&#8369;{{ number_format((float) $loan->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Outstanding Balance:</td>
                <td style="padding:4px 0;">&#8369;{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Due Date:</td>
                <td style="padding:4px 0;">{{ $loan->due_date?->format('F d, Y') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Days Overdue:</td>
                <td style="padding:4px 0;">{{ $daysOverdue }} day{{ $daysOverdue === 1 ? '' : 's' }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Penalties Applied:</td>
                <td style="padding:4px 0;">&#8369;{{ number_format((float) $loan->penalty_total, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0 4px; font-weight:700;">TOTAL AMOUNT DUE:</td>
                <td style="padding:10px 0 4px; font-weight:700;">&#8369;{{ number_format((float) $totalDue, 2) }}</td>
            </tr>
        </table>
    </div>

    <p>
        You are hereby required to pay the total amount of <strong>&#8369;{{ number_format((float) $totalDue, 2) }}</strong>
        within fifteen (15) days from receipt of this letter. Please govern yourself accordingly and settle this
        obligation within the period stated above.
    </p>

    <p>
        Failure to comply with this formal demand will compel us to endorse the matter for appropriate legal action
        and other remedies available under the loan agreement and applicable law, without further notice.
    </p>

    <div style="margin-top:48px;">
        <div>Sincerely,</div>
        <div style="margin-top:42px;">_______________________</div>
        <div style="font-weight:700;">{{ auth()->user()?->name ?? 'Authorized Officer' }}</div>
        <div>{{ $role ? \App\Support\TenantPermissions::displayRoleName((string) $role) : 'Management' }}</div>
        <div>{{ $cooperativeName }}</div>
    </div>

    <div style="margin-top:56px; border-top:1px solid #cbd5e1; padding-top:12px; text-align:center; font-size:11px; color:#475569;">
        This is an official document of {{ $cooperativeName }}.
    </div>
</body>
</html>
