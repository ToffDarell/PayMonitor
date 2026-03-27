@extends('layouts.tenant')

@section('title', 'Create Loan Type')

@push('styles')
<style>
    [x-cloak] {
        display: none !important;
    }

    .loan-type-preset-panel {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 1.25rem;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(10, 22, 40, 0.96) 100%);
        box-shadow: 0 24px 50px rgba(2, 6, 23, 0.28);
    }

    .loan-type-preset {
        width: 100%;
        min-height: 100%;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.92);
        color: #e2e8f0;
        padding: 1rem;
        text-align: left;
        transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        box-shadow: 0 18px 40px rgba(2, 6, 23, 0.2);
    }

    .loan-type-preset:hover {
        transform: translateY(-2px);
        border-color: rgba(52, 211, 153, 0.45);
        background: rgba(15, 23, 42, 0.98);
    }

    .loan-type-preset--active {
        border-color: rgba(16, 185, 129, 0.9);
        background: linear-gradient(180deg, rgba(6, 78, 59, 0.45) 0%, rgba(15, 23, 42, 0.96) 100%);
        box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.18), 0 22px 44px rgba(5, 150, 105, 0.18);
    }

    .loan-type-divider {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .loan-type-divider::before,
    .loan-type-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(148, 163, 184, 0.18);
    }
</style>
@endpush

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $loanTypePresets = [
        [
            'name' => 'Emergency Loan',
            'interest_rate' => '2',
            'interest_type' => 'flat',
            'max_term_months' => '12',
            'description' => 'Short-term emergency financial assistance',
        ],
        [
            'name' => 'Salary Loan',
            'interest_rate' => '3',
            'interest_type' => 'flat',
            'max_term_months' => '12',
            'description' => 'Loan based on monthly salary',
        ],
        [
            'name' => 'Appliance Loan',
            'interest_rate' => '3',
            'interest_type' => 'flat',
            'max_term_months' => '24',
            'description' => 'For purchase of home appliances',
        ],
        [
            'name' => 'Medical Loan',
            'interest_rate' => '2',
            'interest_type' => 'flat',
            'max_term_months' => '12',
            'description' => 'For medical and hospitalization expenses',
        ],
        [
            'name' => 'Educational Loan',
            'interest_rate' => '2',
            'interest_type' => 'flat',
            'max_term_months' => '24',
            'description' => 'For tuition and educational expenses',
        ],
        [
            'name' => 'Business Loan',
            'interest_rate' => '5',
            'interest_type' => 'diminishing',
            'max_term_months' => '36',
            'description' => 'For small business capital and expenses',
        ],
        [
            'name' => 'Agricultural Loan',
            'interest_rate' => '3',
            'interest_type' => 'flat',
            'max_term_months' => '12',
            'description' => 'For farming and agricultural needs',
        ],
        [
            'name' => 'Housing Loan',
            'interest_rate' => '6',
            'interest_type' => 'diminishing',
            'max_term_months' => '120',
            'description' => 'For home improvement or construction',
        ],
        [
            'name' => 'Personal Loan',
            'interest_rate' => '4',
            'interest_type' => 'flat',
            'max_term_months' => '24',
            'description' => 'For personal use and expenses',
        ],
        [
            'name' => 'Special Loan',
            'interest_rate' => '0',
            'interest_type' => 'flat',
            'max_term_months' => '6',
            'description' => 'Special loan with custom terms',
        ],
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Add Loan Type</h1>
        <p class="text-muted mb-0">Set up a new cooperative loan product and its pricing rules.</p>
    </div>
    <a href="{{ route('loan-types.index', $tenantParameter) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Loan Types
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4" x-data="loanTypePresetForm({ showPresets: true })">
        <form action="{{ route('loan-types.store', $tenantParameter) }}" method="POST">
            @csrf

            <div class="loan-type-preset-panel p-4 p-lg-5 mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <h2 class="h4 fw-bold text-white mb-2">Quick Select a Loan Type (Optional)</h2>
                        <p class="text-secondary-emphasis mb-0">Choose a preset to auto-fill the form, then adjust any value below as needed.</p>
                    </div>
                    <div class="small text-uppercase fw-semibold text-success-emphasis">10 presets available</div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3">
                    <template x-for="preset in presets" :key="preset.name">
                        <div class="col">
                            <button
                                type="button"
                                class="loan-type-preset"
                                :class="{ 'loan-type-preset--active': selectedPreset === preset.name }"
                                x-on:click="selectPreset(preset)"
                            >
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold fs-6" x-text="preset.name"></div>
                                        <div class="small text-secondary mt-2" x-text="preset.description"></div>
                                    </div>
                                    <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle" x-text="`${preset.interest_rate}%`"></span>
                                </div>
                                <div class="d-flex gap-2 mt-3 flex-wrap">
                                    <span class="badge rounded-pill text-bg-dark border border-light-subtle text-uppercase" x-text="preset.interest_type"></span>
                                    <span class="badge rounded-pill text-bg-dark border border-light-subtle" x-text="`${preset.max_term_months} months`"></span>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="loan-type-divider mb-4">Or customize the loan type below</div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name *</label>
                        <input type="text" id="name" name="name" x-ref="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" x-ref="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="interest_rate" class="form-label fw-semibold">Interest Rate *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" id="interest_rate" name="interest_rate" x-ref="interestRate" value="{{ old('interest_rate') }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
                                <span class="input-group-text">%</span>
                            </div>
                            @error('interest_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_term_months" class="form-label fw-semibold">Max Term Months</label>
                            <input type="number" min="1" id="max_term_months" name="max_term_months" x-ref="maxTermMonths" value="{{ old('max_term_months') }}" class="form-control @error('max_term_months') is-invalid @enderror">
                            @error('max_term_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="min_amount" class="form-label fw-semibold">Min Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">P</span>
                                <input type="number" step="0.01" min="0" id="min_amount" name="min_amount" value="{{ old('min_amount') }}" class="form-control @error('min_amount') is-invalid @enderror">
                            </div>
                            @error('min_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_amount" class="form-label fw-semibold">Max Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">P</span>
                                <input type="number" step="0.01" min="0" id="max_amount" name="max_amount" value="{{ old('max_amount') }}" class="form-control @error('max_amount') is-invalid @enderror">
                            </div>
                            @error('max_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded-3 p-4 bg-light h-100">
                        <h2 class="h5 fw-bold mb-3">Interest Type</h2>

                        <div class="form-check border rounded-3 bg-white p-3 mb-3">
                            <input class="form-check-input" type="radio" name="interest_type" id="interest_type_flat" x-ref="interestTypeFlat" value="flat" @checked(old('interest_type', 'flat') === 'flat')>
                            <label class="form-check-label fw-semibold" for="interest_type_flat">Flat</label>
                            <div class="small text-muted mt-2">Interest stays fixed on the original principal for the full term.</div>
                        </div>

                        <div class="form-check border rounded-3 bg-white p-3 mb-3">
                            <input class="form-check-input" type="radio" name="interest_type" id="interest_type_diminishing" x-ref="interestTypeDiminishing" value="diminishing" @checked(old('interest_type') === 'diminishing')>
                            <label class="form-check-label fw-semibold" for="interest_type_diminishing">Diminishing</label>
                            <div class="small text-muted mt-2">Interest decreases as the outstanding principal is paid down over time.</div>
                        </div>
                        @error('interest_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label fw-semibold" for="is_active">Is Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('loan-types.index', $tenantParameter) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Save Loan Type
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loanTypePresetForm', ({ showPresets = true } = {}) => ({
            selectedPreset: '',
            showPresets,
            presets: @js($loanTypePresets),
            init() {
                const currentInterestType = this.$refs.interestTypeDiminishing?.checked ? 'diminishing' : 'flat';
                const matchedPreset = this.presets.find((preset) =>
                    preset.name === (this.$refs.name?.value ?? '') &&
                    preset.description === (this.$refs.description?.value ?? '') &&
                    String(preset.interest_rate) === String(this.$refs.interestRate?.value ?? '') &&
                    preset.interest_type === currentInterestType &&
                    String(preset.max_term_months) === String(this.$refs.maxTermMonths?.value ?? '')
                );

                if (matchedPreset) {
                    this.selectedPreset = matchedPreset.name;
                }
            },
            updateField(refName, value) {
                const field = this.$refs[refName];

                if (! field) {
                    return;
                }

                field.value = value ?? '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            },
            setInterestType(value) {
                const flatField = this.$refs.interestTypeFlat;
                const diminishingField = this.$refs.interestTypeDiminishing;

                if (! flatField || ! diminishingField) {
                    return;
                }

                flatField.checked = value === 'flat';
                diminishingField.checked = value === 'diminishing';

                const activeField = flatField.checked ? flatField : diminishingField;

                activeField.dispatchEvent(new Event('change', { bubbles: true }));
            },
            selectPreset(preset) {
                this.selectedPreset = preset.name;
                this.updateField('name', preset.name);
                this.updateField('description', preset.description);
                this.updateField('interestRate', preset.interest_rate);
                this.updateField('maxTermMonths', preset.max_term_months);
                this.setInterestType(preset.interest_type);
            },
        }));
    });
</script>
@endpush
