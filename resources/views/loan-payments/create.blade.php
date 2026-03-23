@extends('layouts.tenant')

@section('title', 'Record Payment')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Record Payment</h1>
        <p class="text-muted mb-0">Apply a new payment to this loan and keep the schedule updated.</p>
    </div>
    <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Loan
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Loan Summary</h2>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-semibold">Loan No.</div>
                <div class="fw-bold">{{ $loan->loan_number }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-semibold">Member</div>
                <div>{{ $loan->member?->full_name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-semibold">Branch</div>
                <div>{{ $loan->branch?->name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Principal</div>
                <div>P{{ number_format((float) $loan->principal_amount, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Total Payable</div>
                <div>P{{ number_format((float) $loan->total_payable, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Amount Paid So Far</div>
                <div class="text-success">P{{ number_format((float) $loan->amount_paid, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Outstanding Balance</div>
                <div class="text-danger fw-bold">P{{ number_format((float) $loan->outstanding_balance, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 760px;">
    <div class="card-body p-4">
        <form action="{{ route('loan-payments.store', $tenantParameter) }}" method="POST" class="row g-3">
            @csrf
            <input type="hidden" name="loan_id" value="{{ $loan->id }}">

            <div class="col-12">
                <label for="amount" class="form-label fw-semibold">Amount *</label>
                <div class="input-group">
                    <span class="input-group-text">P</span>
                    <input type="number" step="0.01" min="0.01" max="{{ number_format((float) $loan->outstanding_balance, 2, '.', '') }}" id="amount" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                </div>
                @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-text">Remaining balance: P{{ number_format((float) $loan->outstanding_balance, 2) }}</div>
            </div>

            <div class="col-md-6">
                <label for="payment_date" class="form-label fw-semibold">Payment Date *</label>
                <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control @error('payment_date') is-invalid @enderror" required>
                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="period_covered" class="form-label fw-semibold">Period Covered</label>
                <input type="text" id="period_covered" name="period_covered" value="{{ old('period_covered') }}" class="form-control @error('period_covered') is-invalid @enderror" placeholder="Example: January 2026">
                @error('period_covered') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label for="notes" class="form-label fw-semibold">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional payment remarks">{{ old('notes') }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Record Payment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
