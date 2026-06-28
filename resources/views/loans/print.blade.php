<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Loan Report - {{ $loan->loan_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; line-height: 1.4; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .header-left h1 { font-size: 14pt; margin-bottom: 2px; }
        .header-left .coop-name { font-size: 12pt; font-weight: bold; }
        .header-right { text-align: right; font-size: 9pt; color: #555; }
        hr { border: none; border-top: 1px solid #000; margin: 6px 0 8px; }
        .section-title { font-size: 10pt; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .two-col { display: flex; gap: 20px; margin-bottom: 8px; }
        .two-col > div { flex: 1; }
        .info-table { width: 100%; font-size: 9pt; }
        .info-table td { padding: 1px 0; vertical-align: top; }
        .info-table .label { color: #555; width: 110px; }
        .summary-row { display: flex; gap: 6px; margin-bottom: 8px; }
        .summary-box { flex: 1; border: 1px solid #ccc; padding: 6px 8px; text-align: center; }
        .summary-box .label { font-size: 7pt; text-transform: uppercase; color: #555; letter-spacing: 0.3px; }
        .summary-box .value { font-size: 11pt; font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 6px; page-break-inside: avoid; }
        table.data th { background: #e0e0e0; padding: 4px 6px; text-align: left; font-weight: bold; font-size: 8pt; text-transform: uppercase; }
        table.data td { padding: 3px 6px; border-bottom: 1px solid #ddd; }
        table.data tr:nth-child(even) td { background: #f5f5f5; }
        table.data .text-end { text-align: right; }
        .footer { display: flex; justify-content: space-between; font-size: 8pt; color: #888; margin-top: 10px; padding-top: 4px; border-top: 1px solid #ccc; }
        .status-paid { color: #059669; font-weight: bold; }
        .status-partial { color: #d97706; font-weight: bold; }
        .status-overdue { color: #dc2626; font-weight: bold; }
        .status-pending { color: #6b7280; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.data tr:nth-child(even) td { background: #f5f5f5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.data th { background: #e0e0e0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @php
        $coopName = tenant()?->name ?? 'Cooperative';
    @endphp

    <div class="header">
        <div class="header-left">
            <div class="coop-name">{{ $coopName }}</div>
            <h1>LOAN REPORT</h1>
        </div>
        <div class="header-right">
            <div>Date Printed: {{ now()->format('F d, Y') }}</div>
        </div>
    </div>
    <hr>

    <div class="two-col">
        <div>
            <div class="section-title" style="margin-bottom:4px;">MAKER</div>
            <table class="info-table">
                <tr><td class="label">Name:</td><td>{{ $loan->member?->full_name ?? 'N/A' }}</td></tr>
                <tr><td class="label">Occupation:</td><td>{{ $loan->member?->occupation ?? 'N/A' }}</td></tr>
                <tr><td class="label">Monthly Salary:</td><td>&#8369;{{ number_format((float) ($loan->member?->monthly_salary ?? 0), 2) }}</td></tr>
                <tr><td class="label">Address:</td><td>{{ $loan->member?->address ?? 'N/A' }}</td></tr>
                <tr><td class="label">Contact Number:</td><td>{{ $loan->member?->phone ?? 'N/A' }}</td></tr>
            </table>
            <div class="section-title" style="margin:8px 0 4px;">CO-MAKER</div>
            <table class="info-table">
                <tr><td class="label">Name:</td><td>{{ $loan->member?->co_maker_name ?? '—' }}</td></tr>
                <tr><td class="label">Contact Number:</td><td>{{ $loan->member?->co_maker_contact_number ?? '—' }}</td></tr>
            </table>
        </div>
        <div>
            <table class="info-table">
                <tr><td class="label">Loan No.:</td><td>{{ $loan->loan_number }}</td></tr>
                <tr><td class="label">Loan Type:</td><td>{{ $loan->loanType?->name ?? 'N/A' }}</td></tr>
                <tr><td class="label">Branch:</td><td>{{ $loan->branch?->name ?? 'N/A' }}</td></tr>
                <tr><td class="label">Released By:</td><td>{{ $loan->user?->name ?? 'N/A' }}</td></tr>
                <tr><td class="label">Due Date:</td><td>{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</td></tr>
                <tr><td class="label">Monthly Payment:</td><td>&#8369;{{ number_format((float) $loan->monthly_payment, 2) }}</td></tr>
            </table>
        </div>
    </div>
    <hr>

    <div class="section-title">Loan Summary</div>
    <div class="summary-row">
        <div class="summary-box">
            <div class="label">Principal</div>
            <div class="value">&#8369;{{ number_format((float) $loan->principal_amount, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Payable</div>
            <div class="value">&#8369;{{ number_format((float) $loan->total_payable, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Paid</div>
            <div class="value">&#8369;{{ number_format((float) $loan->amount_paid, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Outstanding</div>
            <div class="value">&#8369;{{ number_format((float) $loan->outstanding_balance, 2) }}</div>
        </div>
    </div>

    <div class="section-title">Amortization Schedule</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:50px">Period</th>
                <th>Due Date</th>
                <th class="text-end">Amount Due</th>
                <th class="text-end">Principal</th>
                <th class="text-end">Interest</th>
                <th class="text-end">Balance</th>
                <th style="width:80px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->loanSchedules as $schedule)
                @php
                    $statusClass = match($schedule->status) {
                        'paid' => 'status-paid',
                        'partially_paid' => 'status-partial',
                        'overdue' => 'status-overdue',
                        default => 'status-pending',
                    };
                @endphp
                <tr>
                    <td>{{ $schedule->period_number }}</td>
                    <td>{{ $schedule->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                    <td class="text-end">&#8369;{{ number_format((float) $schedule->amount_due, 2) }}</td>
                    <td class="text-end">&#8369;{{ number_format((float) $schedule->principal_portion, 2) }}</td>
                    <td class="text-end">&#8369;{{ number_format((float) $schedule->interest_portion, 2) }}</td>
                    <td class="text-end">&#8369;{{ number_format((float) $schedule->balance, 2) }}</td>
                    <td class="{{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($schedule->status)) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:#888;">No schedule records.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Payment History</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Date</th>
                <th class="text-end">Amount</th>
                <th>Period Covered</th>
                <th>Recorded By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->loanPayments as $payment)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $payment->payment_date?->format('M d, Y') ?? 'N/A' }}</td>
                    <td class="text-end">&#8369;{{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ $payment->period_covered ?: '-' }}</td>
                    <td>{{ $payment->user?->name ?? 'N/A' }}</td>
                    <td>{{ $payment->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#888;">No payment history.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Generated by PayMonitor</span>
        <span>Page 1 of 1</span>
    </div>

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
