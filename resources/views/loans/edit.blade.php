@extends('layouts.tenant')

@section('title', 'Edit Loan')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $statusOptions = ['active', 'overdue', 'restructured', 'fully_paid'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Edit Loan</h1>
        <p class="text-muted mb-0">Update the notes and allowed status fields for this loan.</p>
    </div>
    <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Loan Details
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Loan Number</div>
                <div class="fw-bold">{{ $loan->loan_number }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Member</div>
                <div>{{ $loan->member?->full_name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Principal</div>
                <div>P{{ number_format((float) $loan->principal_amount, 2) }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Outstanding</div>
                <div class="text-danger fw-bold">P{{ number_format((float) $loan->outstanding_balance, 2) }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Loan Type</div>
                <div>{{ $loan->loanType?->name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Release Date</div>
                <div>{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Due Date</div>
                <div>{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="text-muted small text-uppercase fw-semibold">Payments Recorded</div>
                <div>{{ number_format((int) $loan->loan_payments_count) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 760px;">
    <div class="card-body p-4">
        <form action="{{ route('loans.update', [...$tenantParameter, 'loan' => $loan]) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            @if($loan->loan_payments_count === 0)
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        No payments have been recorded yet, so the loan status can still be adjusted.
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" @selected(old('status', $loan->status) === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @else
                <input type="hidden" name="status" value="{{ old('status', $loan->status) }}">
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        Status changes are locked once payments have been recorded. You can still update the notes below.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Current Status</label>
                    <input type="text" class="form-control" value="{{ str_replace('_', ' ', ucfirst($loan->status)) }}" readonly>
                </div>
            @endif

            <div class="col-12">
                <label for="notes" class="form-label fw-semibold">Notes</label>
                <textarea id="notes" name="notes" rows="5" class="form-control @error('notes') is-invalid @enderror" placeholder="Update any remarks for this loan">{{ old('notes', $loan->notes) }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Loan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
