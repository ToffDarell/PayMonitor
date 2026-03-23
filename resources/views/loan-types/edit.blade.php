@extends('layouts.tenant')

@section('title', 'Edit Loan Type')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Edit Loan Type</h1>
        <p class="text-muted mb-0">Update pricing rules and limits for {{ $loanType->name }}.</p>
    </div>
    <a href="{{ route('loan-types.show', [...$tenantParameter, 'loan_type' => $loanType]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Details
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="{{ route('loan-types.update', [...$tenantParameter, 'loan_type' => $loanType]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $loanType->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $loanType->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="interest_rate" class="form-label fw-semibold">Interest Rate *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" id="interest_rate" name="interest_rate" value="{{ old('interest_rate', $loanType->interest_rate) }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
                                <span class="input-group-text">%</span>
                            </div>
                            @error('interest_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_term_months" class="form-label fw-semibold">Max Term Months</label>
                            <input type="number" min="1" id="max_term_months" name="max_term_months" value="{{ old('max_term_months', $loanType->max_term_months) }}" class="form-control @error('max_term_months') is-invalid @enderror">
                            @error('max_term_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="min_amount" class="form-label fw-semibold">Min Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">P</span>
                                <input type="number" step="0.01" min="0" id="min_amount" name="min_amount" value="{{ old('min_amount', $loanType->min_amount) }}" class="form-control @error('min_amount') is-invalid @enderror">
                            </div>
                            @error('min_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_amount" class="form-label fw-semibold">Max Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">P</span>
                                <input type="number" step="0.01" min="0" id="max_amount" name="max_amount" value="{{ old('max_amount', $loanType->max_amount) }}" class="form-control @error('max_amount') is-invalid @enderror">
                            </div>
                            @error('max_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded-3 p-4 bg-light h-100">
                        <h2 class="h5 fw-bold mb-3">Interest Type</h2>

                        <div class="form-check border rounded-3 bg-white p-3 mb-3">
                            <input class="form-check-input" type="radio" name="interest_type" id="interest_type_flat" value="flat" @checked(old('interest_type', $loanType->interest_type) === 'flat')>
                            <label class="form-check-label fw-semibold" for="interest_type_flat">Flat</label>
                            <div class="small text-muted mt-2">Interest stays fixed on the original principal for the full term.</div>
                        </div>

                        <div class="form-check border rounded-3 bg-white p-3 mb-3">
                            <input class="form-check-input" type="radio" name="interest_type" id="interest_type_diminishing" value="diminishing" @checked(old('interest_type', $loanType->interest_type) === 'diminishing')>
                            <label class="form-check-label fw-semibold" for="interest_type_diminishing">Diminishing</label>
                            <div class="small text-muted mt-2">Interest decreases as the outstanding principal is paid down over time.</div>
                        </div>
                        @error('interest_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $loanType->is_active))>
                            <label class="form-check-label fw-semibold" for="is_active">Is Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('loan-types.show', [...$tenantParameter, 'loan_type' => $loanType]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Loan Type
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
