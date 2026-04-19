<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 28px 24px 72px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #ffffff;
        }

        .header,
        .header-table,
        .brand-table,
        .details-table,
        .summary-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            padding-bottom: 14px;
            border-bottom: 3px solid #4f46e5;
            margin-bottom: 20px;
        }

        .header-table td,
        .brand-table td {
            vertical-align: middle;
        }

        .brand-logo-cell {
            width: 78px;
        }

        .header-right {
            text-align: right;
        }

        .logo {
            display: block;
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .logo-fallback {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            background: #4f46e5;
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            line-height: 64px;
            text-align: center;
        }

        .coop-name {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .coop-tagline {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
        }

        .report-subtitle,
        .generated-at {
            font-size: 9px;
            color: #6b7280;
            margin-top: 3px;
        }

        .section-header {
            background: #4f46e5;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
            padding: 7px 12px;
            text-transform: uppercase;
            margin: 18px 0 0;
        }

        .details-table td {
            width: 25%;
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            vertical-align: top;
        }

        .details-label,
        .summary-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .details-value {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
        }

        .summary-table td {
            width: 33.33%;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 10px 12px;
            vertical-align: top;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .summary-value.currency {
            color: #059669;
        }

        .summary-value.danger {
            color: #dc2626;
        }

        .data-table thead tr {
            background: #f1f5f9;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
        }

        .data-table th {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .data-table td {
            font-size: 10px;
            color: #374151;
            vertical-align: top;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .empty-row td {
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            padding: 12px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }

        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        p {
            margin: 0;
        }
    </style>
</head>
<body>
@php
    $logoPath = filled($logo ?? null) ? storage_path('app/public/' . ltrim((string) $logo, '/')) : null;
    $hasLogo = is_string($logoPath) && $logoPath !== '';
    $currency = $currencySymbol ?? "\u{20B1}";
    $brandInitial = strtoupper(substr((string) $coopName, 0, 1));
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 56%;">
                <table class="brand-table">
                    <tr>
                        <td class="brand-logo-cell">
                            @if($hasLogo)
                                <img src="{{ $logoPath }}" alt="Cooperative logo" class="logo">
                            @else
                                <div class="logo-fallback">{{ $brandInitial }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="coop-name">{{ strtoupper($coopName) }}</div>
                            @if(($tagline ?? '') !== '')
                                <div class="coop-tagline">{{ $tagline }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-right" style="width: 44%;">
                <div class="report-title">Cooperative Lending Report</div>
                <div class="report-subtitle">Lending performance, collections, and borrower exposure</div>
                <div class="generated-at">Generated {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-header">Report Details</div>
<table class="details-table">
    <tr>
        <td>
            <div class="details-label">Branch</div>
            <div class="details-value">{{ $filters['branch'] ?? 'All Branches' }}</div>
        </td>
        <td>
            <div class="details-label">Date From</div>
            <div class="details-value">{{ $filters['date_from'] ?? 'All Dates' }}</div>
        </td>
        <td>
            <div class="details-label">Date To</div>
            <div class="details-value">{{ $filters['date_to'] ?? 'All Dates' }}</div>
        </td>
        <td>
            <div class="details-label">Generated At</div>
            <div class="details-value">{{ $generatedAt }}</div>
        </td>
    </tr>
</table>

<div class="section-header">Portfolio Summary</div>
<table class="summary-table">
    <tr>
        <td>
            <div class="summary-label">Total Loans Released (Count)</div>
            <div class="summary-value">{{ number_format($summary['total_loans_count'] ?? 0) }}</div>
        </td>
        <td>
            <div class="summary-label">Total Loans Released (Amount)</div>
            <div class="summary-value currency">{{ $currency }}{{ number_format($summary['total_loans_amount'] ?? 0, 2) }}</div>
        </td>
        <td>
            <div class="summary-label">Total Collections</div>
            <div class="summary-value currency">{{ $currency }}{{ number_format($summary['total_collections'] ?? 0, 2) }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="summary-label">Outstanding Balance</div>
            <div class="summary-value danger">{{ $currency }}{{ number_format($summary['outstanding_balance'] ?? 0, 2) }}</div>
        </td>
        <td>
            <div class="summary-label">Overdue Loans</div>
            <div class="summary-value danger">{{ number_format($summary['overdue_loans'] ?? 0) }}</div>
        </td>
        <td>
            <div class="summary-label">Interest Income / Profit</div>
            <div class="summary-value currency">{{ $currency }}{{ number_format($summary['interest_income'] ?? 0, 2) }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="summary-label">Fully Paid Loans</div>
            <div class="summary-value">{{ number_format($summary['fully_paid'] ?? 0) }}</div>
        </td>
    </tr>
</table>

<div class="section-header">Loan Releases by Type</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Loan Type</th>
            <th class="text-right">Count</th>
            <th class="text-right">Total Principal</th>
            <th class="text-right">Total Payable</th>
        </tr>
    </thead>
    <tbody>
        @forelse($loansByType as $type)
            <tr>
                <td>{{ $type->name }}</td>
                <td class="text-right">{{ number_format($type->count) }}</td>
                <td class="text-right">{{ $currency }}{{ number_format($type->total_principal, 2) }}</td>
                <td class="text-right">{{ $currency }}{{ number_format($type->total_payable, 2) }}</td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="4">No loan releases found for the selected period.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="section-header">Collections by Month</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Month</th>
            <th class="text-right">Payments Count</th>
            <th class="text-right">Total Collected</th>
        </tr>
    </thead>
    <tbody>
        @forelse($collectionsByMonth as $month)
            <tr>
                <td>{{ $month->month }}</td>
                <td class="text-right">{{ number_format($month->count) }}</td>
                <td class="text-right">{{ $currency }}{{ number_format($month->total, 2) }}</td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="3">No collections recorded for the selected period.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="section-header">Overdue Loans</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Member</th>
            <th>Loan No.</th>
            <th class="text-right">Balance</th>
            <th>Due Date</th>
            <th class="text-right">Days Overdue</th>
        </tr>
    </thead>
    <tbody>
        @forelse($overdueLoans as $loan)
            <tr>
                <td>{{ $loan->member?->full_name ?? 'N/A' }}</td>
                <td>{{ $loan->loan_number }}</td>
                <td class="text-right">{{ $currency }}{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                <td>{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                <td class="text-right">
                    <span class="badge badge-danger">
                        {{ $loan->due_date?->diffInDays(now()) ?? 0 }} days
                    </span>
                </td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="5">No overdue loans found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="section-header">Top 10 Borrowers by Outstanding Balance</div>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Member No.</th>
            <th>Name</th>
            <th class="text-right">Active Loans</th>
            <th class="text-right">Total Outstanding</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topBorrowers as $index => $borrower)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $borrower->member_number }}</td>
                <td>{{ $borrower->full_name }}</td>
                <td class="text-right">{{ number_format((int) ($borrower->active_loans_count ?? 0)) }}</td>
                <td class="text-right">{{ $currency }}{{ number_format((float) ($borrower->total_outstanding ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="5">No borrower balances found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;">
    <p style="font-size: 9px;
              color: #9ca3af;
              margin: 0 0 2px 0;">
        {{ $coopName }} | Cooperative Lending Report
        | Generated by PayMonitor
        | {{ now()->format('F d, Y') }}
    </p>

    <p style="font-size: 8px;
              color: #9ca3af;
              margin: 0 0 4px 0;">
        This report is confidential and intended
        for authorized personnel only.
    </p>

    <p style="font-size: 8px;
              color: #d1d5db;
              margin: 0;
              font-family: monospace;
              letter-spacing: 1px;">
        DS &middot; {{ $verificationCode }}
    </p>
</div>
</body>
</html>
