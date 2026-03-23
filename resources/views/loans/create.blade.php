@extends('layouts.tenant')

@section('title', 'Release Loan')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $memberOptions = $members->map(function ($member): array {
        return [
            'id' => (string) $member->id,
            'label' => $member->member_number.' - '.$member->full_name,
        ];
    })->values();
    $loanTypeOptions = $loanTypes->map(function ($loanType): array {
        return [
            'id' => (string) $loanType->id,
            'name' => $loanType->name,
            'rate' => (float) $loanType->interest_rate,
            'type' => $loanType->interest_type,
        ];
    })->values();
@endphp

<div
    class="row g-4"
    x-data="loanPreview({
        members: @js($memberOptions),
        loanTypes: @js($loanTypeOptions),
        previewUrl: @js(route('loans.compute-preview', $tenantParameter)),
        csrfToken: @js(csrf_token()),
        selectedMemberId: @js((string) old('member_id', request('member_id'))),
        branchId: @js((string) old('branch_id')),
        loanTypeId: @js((string) old('loan_type_id')),
        principal: @js((string) old('principal_amount')),
        term: @js((string) old('term_months')),
        releaseDate: @js(old('release_date', now()->toDateString())),
        notes: @js(old('notes')),
    })"
    x-init="initialize()"
>
    <div class="col-lg-7 col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Release Loan</h1>
                <p class="text-muted mb-0">Create a new loan record and preview the computation before saving.</p>
            </div>
            <a href="{{ route('loans.index', $tenantParameter) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Loans
            </a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('loans.store', $tenantParameter) }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <label for="member_search" class="form-label fw-semibold">Select Member *</label>
                        <input type="text" id="member_search" x-model="memberSearch" class="form-control mb-2" placeholder="Search member name or member number">
                        <select id="member_id" name="member_id" x-model="selectedMemberId" class="form-select @error('member_id') is-invalid @enderror" required>
                            <option value="">Select member</option>
                            <template x-for="member in filteredMembers" :key="member.id">
                                <option :value="member.id" x-text="member.label"></option>
                            </template>
                        </select>
                        @error('member_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="branch_id" class="form-label fw-semibold">Select Branch *</label>
                        <select id="branch_id" name="branch_id" x-model="branchId" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="loan_type_id" class="form-label fw-semibold">Select Loan Type *</label>
                        <select id="loan_type_id" name="loan_type_id" x-model="loanTypeId" @change="applyLoanType(); updatePreview()" class="form-select @error('loan_type_id') is-invalid @enderror" required>
                            <option value="">Select loan type</option>
                            <template x-for="loanType in loanTypes" :key="loanType.id">
                                <option :value="loanType.id" x-text="loanType.name"></option>
                            </template>
                        </select>
                        @error('loan_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="principal_amount" class="form-label fw-semibold">Principal Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">P</span>
                            <input type="number" step="0.01" min="1" id="principal_amount" name="principal_amount" x-model="principal" @input.debounce.300ms="updatePreview()" class="form-control @error('principal_amount') is-invalid @enderror" required>
                        </div>
                        @error('principal_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="term_months" class="form-label fw-semibold">Term Months *</label>
                        <input type="number" min="1" id="term_months" name="term_months" x-model="term" @input.debounce.300ms="updatePreview()" class="form-control @error('term_months') is-invalid @enderror" required>
                        @error('term_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="release_date" class="form-label fw-semibold">Release Date *</label>
                        <input type="date" id="release_date" name="release_date" x-model="releaseDate" class="form-control @error('release_date') is-invalid @enderror" required>
                        @error('release_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label fw-semibold">Notes</label>
                        <textarea id="notes" name="notes" x-model="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional notes about the loan release"></textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('loans.index', $tenantParameter) }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cash-coin me-2"></i>Release Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 96px;">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0 fw-bold">Loan Computation Preview</h2>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Principal</span>
                        <strong x-text="currency(principal || 0)"></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Interest Rate</span>
                        <strong x-text="rateDisplay()"></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Interest Type</span>
                        <strong x-text="formattedInterestType()"></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Total Interest</span>
                        <strong x-text="currency(totalInterest)"></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Total Payable</span>
                        <strong x-text="currency(totalPayable)"></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Monthly Payment</span>
                        <strong x-text="currency(monthly)"></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Term</span>
                        <strong x-text="termLabel()"></strong>
                    </div>
                </div>
                <div class="alert alert-info mt-4 mb-0">
                    Preview values update live as you change the principal, term, and loan type.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function loanPreview(config) {
        return {
            members: config.members,
            loanTypes: config.loanTypes,
            previewUrl: config.previewUrl,
            csrfToken: config.csrfToken,
            selectedMemberId: config.selectedMemberId,
            branchId: config.branchId,
            loanTypeId: config.loanTypeId,
            principal: config.principal,
            term: config.term,
            releaseDate: config.releaseDate,
            notes: config.notes,
            memberSearch: '',
            rate: 0,
            type: 'flat',
            totalInterest: 0,
            totalPayable: 0,
            monthly: 0,
            get filteredMembers() {
                if (!this.memberSearch) {
                    return this.members;
                }

                const search = this.memberSearch.toLowerCase();

                return this.members.filter((member) => member.label.toLowerCase().includes(search));
            },
            initialize() {
                this.applyLoanType();
                this.updatePreview();
            },
            applyLoanType() {
                const selectedLoanType = this.loanTypes.find((loanType) => loanType.id === String(this.loanTypeId));

                if (!selectedLoanType) {
                    this.rate = 0;
                    this.type = 'flat';

                    return;
                }

                this.rate = Number(selectedLoanType.rate);
                this.type = selectedLoanType.type;
            },
            computeFlat() {
                const principal = Number(this.principal || 0);
                const rate = Number(this.rate || 0) / 100;
                const term = Number(this.term || 0);

                if (!principal || !term) {
                    return { total_interest: 0, total_payable: 0, monthly_payment: 0 };
                }

                const totalInterest = principal * rate * term;
                const totalPayable = principal + totalInterest;

                return {
                    total_interest: totalInterest,
                    total_payable: totalPayable,
                    monthly_payment: totalPayable / term,
                };
            },
            computeDiminishing() {
                const principal = Number(this.principal || 0);
                const monthlyRate = Number(this.rate || 0) / 100;
                const term = Number(this.term || 0);

                if (!principal || !term) {
                    return { total_interest: 0, total_payable: 0, monthly_payment: 0 };
                }

                if (!monthlyRate) {
                    return {
                        total_interest: 0,
                        total_payable: principal,
                        monthly_payment: principal / term,
                    };
                }

                const growthFactor = Math.pow(1 + monthlyRate, term);
                const monthlyPayment = principal * ((monthlyRate * growthFactor) / (growthFactor - 1));
                const totalPayable = monthlyPayment * term;

                return {
                    total_interest: totalPayable - principal,
                    total_payable: totalPayable,
                    monthly_payment: monthlyPayment,
                };
            },
            async updatePreview() {
                if (!this.principal || !this.term || !this.rate) {
                    this.totalInterest = 0;
                    this.totalPayable = 0;
                    this.monthly = 0;

                    return;
                }

                try {
                    const response = await fetch(this.previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            principal: this.principal,
                            rate: this.rate,
                            type: this.type,
                            term_months: this.term,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Preview request failed.');
                    }

                    const data = await response.json();

                    this.totalInterest = Number(data.total_interest || 0);
                    this.totalPayable = Number(data.total_payable || 0);
                    this.monthly = Number(data.monthly_payment || 0);
                } catch (error) {
                    const fallback = this.type === 'diminishing' ? this.computeDiminishing() : this.computeFlat();

                    this.totalInterest = Number(fallback.total_interest || 0);
                    this.totalPayable = Number(fallback.total_payable || 0);
                    this.monthly = Number(fallback.monthly_payment || 0);
                }
            },
            currency(value) {
                return 'P' + Number(value || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
            formattedInterestType() {
                return this.type ? this.type.charAt(0).toUpperCase() + this.type.slice(1) : 'Flat';
            },
            rateDisplay() {
                return Number(this.rate || 0).toFixed(2) + '%';
            },
            termLabel() {
                const term = Number(this.term || 0);

                return term ? `${term} month${term === 1 ? '' : 's'}` : '0 months';
            },
        };
    }
</script>
@endpush
