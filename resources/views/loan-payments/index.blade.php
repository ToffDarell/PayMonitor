@extends('layouts.tenant')

@section('title', 'Loan Payments')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Loan Payments</h1>
        <p class="text-muted mb-0">Review recorded collections and trace them back to their loans.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('loan-payments.index', $tenantParameter) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="branch_id" class="form-label fw-semibold">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label fw-semibold">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label fw-semibold">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="member_search" class="form-label fw-semibold">Member Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="member_search" name="member_search" value="{{ $filters['member_search'] ?? '' }}" class="form-control" placeholder="Member no. or name">
                </div>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel-fill me-1"></i>Apply Filters
                </button>
            </div>
            <div class="col-md-3 d-grid">
                <a href="{{ route('loan-payments.index', $tenantParameter) }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 72px;">#</th>
                    <th>Date</th>
                    <th>Member Name</th>
                    <th>Loan No.</th>
                    <th class="text-end">Amount Paid</th>
                    <th>Period Covered</th>
                    <th>Recorded By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $loop->index }}</td>
                        <td>{{ $payment->payment_date?->format('M d, Y') ?? 'N/A' }}</td>
                        <td>{{ $payment->loan?->member?->full_name ?? 'Unknown Member' }}</td>
                        <td>{{ $payment->loan?->loan_number ?? 'N/A' }}</td>
                        <td class="text-end">P{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->period_covered ?: 'N/A' }}</td>
                        <td>{{ $payment->user?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            @if($payment->loan)
                                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $payment->loan]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>View Loan
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">No payments found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="text-muted text-uppercase small fw-semibold d-block">Total Collected in Filtered Period</span>
            <span class="h5 fw-bold text-success mb-0">P{{ number_format($totalCollected, 2) }}</span>
        </div>

        @if($payments->hasPages())
            {{ $payments->onEachSide(1)->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>
@endsection
