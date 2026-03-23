@extends('layouts.tenant')

@section('title', 'Member Profile')

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
        <h1 class="h3 fw-bold mb-1">Member Profile</h1>
        <p class="text-muted mb-0">View borrower details, balances, and loan history.</p>
    </div>
    <div class="d-flex gap-2">
        @can('create', \App\Models\Loan::class)
            <a href="{{ route('loans.create', [...$tenantParameter, 'member_id' => $member->id]) }}" class="btn btn-primary">
                <i class="bi bi-cash-coin me-2"></i>New Loan for this Member
            </a>
        @endcan
        @can('update', $member)
            <a href="{{ route('members.edit', [...$tenantParameter, 'member' => $member]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil-square me-2"></i>Edit Member
            </a>
        @endcan
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <h2 class="h2 fw-bold mb-2">{{ $member->full_name }}</h2>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary-subtle text-primary">{{ $member->member_number }}</span>
                    <span class="badge bg-{{ $member->is_active ? 'success' : 'secondary' }}">
                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase fw-semibold">Phone</div>
                        <div>{{ $member->phone ?: 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase fw-semibold">Email</div>
                        <div>{{ $member->email ?: 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase fw-semibold">Branch</div>
                        <div>{{ $member->branch?->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase fw-semibold">Joined Date</div>
                        <div>{{ $member->joined_at?->format('M d, Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small text-uppercase fw-semibold">Address</div>
                        <div>{{ $member->address ?: 'No address recorded.' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="text-muted small text-uppercase fw-semibold mb-2">Borrower Snapshot</div>
                    <div class="mb-2"><strong>Birthdate:</strong> {{ $member->birthdate?->format('M d, Y') ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Gender:</strong> {{ $member->gender ? ucfirst($member->gender) : 'N/A' }}</div>
                    <div class="mb-2"><strong>Civil Status:</strong> {{ $member->civil_status ? ucfirst($member->civil_status) : 'N/A' }}</div>
                    <div class="mb-0"><strong>Occupation:</strong> {{ $member->occupation ?: 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Active Loans</div>
                <div class="display-6 fw-bold text-primary">{{ number_format($activeLoans->count()) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Total Borrowed</div>
                <div class="h3 fw-bold mb-0">P{{ number_format($totalBorrowed, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Total Paid</div>
                <div class="h3 fw-bold text-success mb-0">P{{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-danger">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Outstanding Balance</div>
                <div class="h3 fw-bold text-danger mb-0">P{{ number_format($totalOutstanding, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Loan History</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Loan #</th>
                    <th>Type</th>
                    <th class="text-end">Principal</th>
                    <th class="text-end">Total Payable</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanHistory as $loan)
                    <tr>
                        <td>{{ $loan->loan_number }}</td>
                        <td>{{ $loan->loanType?->name ?? 'N/A' }}</td>
                        <td class="text-end">P{{ number_format((float) $loan->principal_amount, 2) }}</td>
                        <td class="text-end">P{{ number_format((float) $loan->total_payable, 2) }}</td>
                        <td class="text-end">P{{ number_format((float) $loan->amount_paid, 2) }}</td>
                        <td class="text-end text-danger">P{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $loanStatusClasses[$loan->status] ?? 'secondary' }}">
                                {{ str_replace('_', ' ', ucfirst($loan->status)) }}
                            </span>
                        </td>
                        <td>{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</td>
                        <td class="text-end">
                            <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">This member has no loan history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
