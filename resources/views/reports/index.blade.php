@extends('layouts.tenant')

@section('title', 'Reports')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $exportQuery = http_build_query(request()->query());
    $pdfExportUrl = url('/reports/export/pdf').($exportQuery !== '' ? '?'.$exportQuery : '');
    $excelExportUrl = url('/reports/export/excel').($exportQuery !== '' ? '?'.$exportQuery : '');
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Reports</h1>
        <p class="text-muted mb-0">Monitor lending performance, collections, overdue exposure, and borrower concentration.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ $pdfExportUrl }}"
           class="flex items-center gap-1.5 rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-400 transition-colors hover:bg-red-500/20">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Export PDF
        </a>

        <a href="{{ $excelExportUrl }}"
           class="flex items-center gap-1.5 rounded-lg border border-green-500/20 bg-green-500/10 px-3 py-1.5 text-xs font-medium text-green-400 transition-colors hover:bg-green-500/20">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Excel
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index', $tenantParameter) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date_from" class="form-label fw-semibold">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" onchange="this.form.requestSubmit()">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label fw-semibold">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" onchange="this.form.requestSubmit()">
            </div>
            <div class="col-md-4">
                <label for="branch_id" class="form-label fw-semibold">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select" onchange="this.form.requestSubmit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel-fill me-1"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Total Loans Released</div>
                <div class="h3 fw-bold mb-1">{{ number_format($totalLoansReleasedCount) }}</div>
                <div class="text-muted">P{{ number_format($totalLoansReleasedAmount, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Total Collections</div>
                <div class="h3 fw-bold text-success mb-0">P{{ number_format($totalCollections, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Outstanding Balance</div>
                <div class="h3 fw-bold text-danger mb-0">P{{ number_format($totalOutstandingBalance, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Overdue Loans</div>
                <div class="h3 fw-bold text-warning mb-0">{{ number_format($totalOverdueLoans) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Interest Income / Profit</div>
                <div class="h3 fw-bold text-primary mb-0">P{{ number_format($interestIncome, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Fully Paid Loans</div>
                <div class="h3 fw-bold mb-0">{{ number_format($fullyPaidLoansCount) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Loan Releases by Type</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Loan Type</th>
                    <th class="text-center">Count</th>
                    <th class="text-end">Total Principal</th>
                    <th class="text-end">Total Payable</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanBreakdown as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-center">{{ number_format($item['count']) }}</td>
                        <td class="text-end">P{{ number_format($item['total_principal'], 2) }}</td>
                        <td class="text-end">P{{ number_format($item['total_payable'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">No loan releases found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Collections by Month</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Month</th>
                    <th class="text-center">Payments Count</th>
                    <th class="text-end">Total Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collectionsByMonth as $month)
                    <tr>
                        <td>{{ $month['month'] }}</td>
                        <td class="text-center">{{ number_format($month['payments_count']) }}</td>
                        <td class="text-end">P{{ number_format($month['total_collected'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">No collections recorded for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0 fw-bold">Overdue Loans</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Loan No.</th>
                            <th class="text-end">Balance</th>
                            <th>Due Date</th>
                            <th class="text-end">Days Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueLoans as $loan)
                            <tr>
                                <td>{{ $loan->member?->full_name ?? 'Unknown Member' }}</td>
                                <td>{{ $loan->loan_number }}</td>
                                <td class="text-end text-danger">P{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                                <td>{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="text-end">{{ $loan->due_date ? $loan->due_date->diffInDays(today()) : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">No overdue loans found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0 fw-bold">Top 10 Borrowers by Outstanding Balance</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member No.</th>
                            <th>Name</th>
                            <th class="text-center">Active Loans</th>
                            <th class="text-end">Total Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topBorrowers as $borrower)
                            <tr>
                                <td>{{ $borrower->member_number }}</td>
                                <td>{{ $borrower->full_name }}</td>
                                <td class="text-center">{{ number_format((int) ($borrower->active_loans_count ?? 0)) }}</td>
                                <td class="text-end text-danger">P{{ number_format((float) ($borrower->total_outstanding ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">No borrower balances found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
