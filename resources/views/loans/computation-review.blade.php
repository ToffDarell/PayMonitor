<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Loan Computation Review - {{ $loan->loan_number }}</title>
    <style>
        @page { size: A4 landscape; margin: 15mm; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .coop-name { font-size: 12pt; font-weight: bold; }
        .form-ref { font-size: 8pt; color: #888; text-align: right; }
        .info-row { display: flex; gap: 24px; margin-bottom: 10px; font-size: 9pt; }
        .info-row .field { display: flex; gap: 4px; }
        .info-row .label { color: #555; }
        .info-row .value { font-weight: bold; }
        hr { border: none; border-top: 1px solid #ccc; margin: 6px 0; }
        table.schedule { width: 100%; border-collapse: collapse; font-size: 9pt; page-break-inside: avoid; }
        table.schedule th { background: #e0e0e0; padding: 4px 6px; text-align: left; font-weight: bold; font-size: 8pt; text-transform: uppercase; }
        table.schedule td { padding: 3px 6px; border-bottom: 1px solid #ddd; }
        table.schedule tr:nth-child(even) td { background: #f5f5f5; }
        table.schedule .text-end { text-align: right; }
        .footer { font-size: 8pt; color: #888; margin-top: 10px; padding-top: 4px; border-top: 1px solid #ccc; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.schedule tr:nth-child(even) td { background: #f5f5f5; }
            table.schedule th { background: #e0e0e0; }
        }
    </style>
</head>
<body>
    @php $coopName = tenant()?->name ?? 'Cooperative'; @endphp

    <div class="header">
        <div>
            <div class="coop-name">{{ $coopName }}</div>
            <h1 style="font-size:14pt;margin:2px 0;">LOAN COMPUTATION REVIEW</h1>
            <div style="font-size:8pt;color:#888;">Date Printed: {{ now()->format('F d, Y') }}</div>
        </div>
        <div class="form-ref">Form: MDF-01</div>
    </div>
    <hr>

    <div class="info-row">
        <div class="field"><span class="label">Member:</span> <span class="value">{{ $loan->member?->full_name ?? 'N/A' }}</span></div>
        <div class="field"><span class="label">ID:</span> <span class="value">{{ $loan->member?->member_number ?? 'N/A' }}</span></div>
        <div class="field"><span class="label">Released:</span> <span class="value">{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</span></div>
        <div class="field"><span class="label">Released By:</span> <span class="value">{{ $loan->user?->name ?? 'N/A' }}</span></div>
        <div class="field"><span class="label">Due Date:</span> <span class="value">{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</span></div>
    </div>
    <hr>

    <div style="font-size:9pt;font-weight:bold;margin-bottom:6px;">LOAN COMPUTATION SUMMARY</div>
    <div class="info-row">
        <div class="field"><span class="label">Principal:</span> <span class="value">₱{{ number_format((float) $loan->principal_amount, 2) }}</span></div>
        <div class="field"><span class="label">Interest Rate:</span> <span class="value">{{ $loan->interest_rate }}%</span></div>
        <div class="field"><span class="label">Interest Type:</span> <span class="value">{{ ucfirst($loan->interest_type) }}</span></div>
        <div class="field"><span class="label">Total Interest:</span> <span class="value">₱{{ number_format((float) $loan->total_interest, 2) }}</span></div>
        <div class="field"><span class="label">Total Payable:</span> <span class="value">₱{{ number_format((float) $loan->total_payable, 2) }}</span></div>
        <div class="field"><span class="label">Monthly Payment:</span> <span class="value">₱{{ number_format((float) $loan->monthly_payment, 2) }}</span></div>
        <div class="field"><span class="label">Term:</span> <span class="value">{{ $loan->term_months }} months</span></div>
    </div>
    <hr>

    <table class="schedule">
        <thead>
            <tr>
                <th style="width:50px">Period</th>
                <th>Due Date</th>
                <th class="text-end">Amount Due</th>
                <th class="text-end">Principal</th>
                <th class="text-end">Interest</th>
                <th class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->loanSchedules->sortBy('period_number') as $schedule)
                <tr>
                    <td>{{ $schedule->period_number }}</td>
                    <td>{{ $schedule->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                    <td class="text-end">₱{{ number_format((float) $schedule->amount_due, 2) }}</td>
                    <td class="text-end">₱{{ number_format((float) $schedule->principal_portion, 2) }}</td>
                    <td class="text-end">₱{{ number_format((float) $schedule->interest_portion, 2) }}</td>
                    <td class="text-end">₱{{ number_format((float) $schedule->balance, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#888;">No schedule records.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Generated by PayMonitor &mdash; Internal Use Only</span>
        <span>Page 1 of 1</span>
    </div>

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
