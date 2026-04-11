@extends('layouts.tenant')

@section('title', 'Dashboard')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $loanStatusClasses = [
        'active' => 'primary',
        'fully_paid' => 'success',
        'overdue' => 'danger',
        'restructured' => 'warning text-dark',
    ];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Dashboard</h1>
        <p class="text-muted mb-0">{{ tenant()?->name ?? 'Lending Cooperative' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('members.index', $tenantParameter) }}" class="btn btn-outline-secondary">
            <i class="bi bi-people me-2"></i>Members
        </a>
        @can('create', \App\Models\Loan::class)
            <a href="{{ route('loans.create', $tenantParameter) }}" class="btn btn-primary">
                <i class="bi bi-cash-coin me-2"></i>New Loan
            </a>
        @endcan
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-[#8b949e] text-xs fw-semibold text-uppercase tracking-[0.16em]">Active Loans</div>
                        <div class="mt-3 text-2xl font-bold text-white">{{ number_format($activeLoansCount) }}</div>
                    </div>
                    <span class="d-inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-500/10 text-blue-400">
                        <i class="bi bi-cash-stack"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-[#8b949e] text-xs fw-semibold text-uppercase tracking-[0.16em]">Outstanding Balance</div>
                        <div class="mt-3 text-2xl font-bold text-white">P{{ number_format($totalOutstandingBalance, 2) }}</div>
                    </div>
                    <span class="d-inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-500/10 text-red-400">
                        <i class="bi bi-bank2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-[#8b949e] text-xs fw-semibold text-uppercase tracking-[0.16em]">Overdue Loans</div>
                        <div class="mt-3 text-2xl font-bold text-white">{{ number_format($overdueLoansCount) }}</div>
                    </div>
                    <span class="d-inline-flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500/10 text-yellow-400">
                        <i class="bi bi-exclamation-triangle"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-[#8b949e] text-xs fw-semibold text-uppercase tracking-[0.16em]">Members Count</div>
                        <div class="mt-3 text-2xl font-bold text-white">{{ number_format($totalMembersCount) }}</div>
                    </div>
                    <span class="d-inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-500/10 text-green-400">
                        <i class="bi bi-people-fill"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100 rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-[#8b949e] text-xs fw-semibold text-uppercase tracking-[0.16em]">Total Collections This Month</div>
                        <div class="mt-3 text-2xl font-bold text-white">P{{ number_format($totalPaymentsThisMonth, 2) }}</div>
                    </div>
                    <span class="d-inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-500/10 text-green-400">
                        <i class="bi bi-wallet2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-[#8b949e] text-xs fw-semibold text-uppercase tracking-[0.16em]">Loan Types Available</div>
                        <div class="mt-3 text-2xl font-bold text-white">{{ number_format($loanTypesCount) }}</div>
                    </div>
                    <span class="d-inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-500/10 text-blue-400">
                        <i class="bi bi-journal-bookmark"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="card-header border-bottom border-[#21262d] bg-[#161b22] py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 fw-bold">Recent Loans</h2>
                    <a href="{{ route('loans.index', $tenantParameter) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-right-circle me-1"></i>View All
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member Name</th>
                            <th>Loan Type</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Monthly Payment</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th>Date Released</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLoans as $loan)
                            <tr>
                                <td>
                                    <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="text-decoration-none fw-semibold">
                                        {{ $loan->member?->full_name ?? 'Unknown Member' }}
                                    </a>
                                </td>
                                <td>{{ $loan->loanType?->name ?? 'N/A' }}</td>
                                <td class="text-end">P{{ number_format((float) $loan->principal_amount, 2) }}</td>
                                <td class="text-end">P{{ number_format((float) $loan->monthly_payment, 2) }}</td>
                                <td class="text-end text-danger">P{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $loanStatusClasses[$loan->status] ?? 'secondary' }}">
                                        {{ str_replace('_', ' ', ucfirst($loan->status)) }}
                                    </span>
                                </td>
                                <td>{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No recent loans found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card rounded-xl border border-[#21262d] bg-[#161b22]">
            <div class="card-header border-bottom border-[#21262d] bg-[#161b22] py-3">
                <h2 class="h5 mb-0 fw-bold">Overdue Loans</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Loan #</th>
                            <th class="text-end">Balance</th>
                            <th>Due Date</th>
                            <th class="text-end">Days Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topOverdueLoans as $loan)
                            <tr>
                                <td>{{ $loan->member?->full_name ?? 'Unknown Member' }}</td>
                                <td>
                                    <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="text-decoration-none fw-semibold">
                                        {{ $loan->loan_number }}
                                    </a>
                                </td>
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
</div>
@endsection
