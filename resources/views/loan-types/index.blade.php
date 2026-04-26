@extends('layouts.tenant')

@section('title', 'Loan Types')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Loan Types</h1>
        <p class="text-muted mb-0">Configure loan products, rates, and lending limits.</p>
    </div>
    @can('create', \App\Models\LoanType::class)
        <a href="{{ route('loan-types.create', $tenantParameter) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Loan Type
        </a>
    @endcan
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 72px;">#</th>
                    <th>Name</th>
                    <th>Interest Rate</th>
                    <th>Interest Type</th>
                    <th>Max Term</th>
                    <th class="text-end">Min Amount</th>
                    <th class="text-end">Max Amount</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanTypes as $loanType)
                    <tr>
                        <td>{{ $loanTypes->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('loan-types.show', [...$tenantParameter, 'loan_type' => $loanType]) }}" class="text-decoration-none fw-semibold">
                                {{ $loanType->name }}
                            </a>
                        </td>
                        <td>{{ number_format((float) $loanType->interest_rate, 2) }}%</td>
                        <td>
                            <span class="badge bg-info-subtle text-info">
                                {{ ucfirst($loanType->interest_type) }}
                            </span>
                        </td>
                        <td>{{ $loanType->max_term_months ? $loanType->max_term_months.' months' : 'No limit' }}</td>
                        <td class="text-end">{{ $loanType->min_amount !== null ? 'P'.number_format((float) $loanType->min_amount, 2) : 'N/A' }}</td>
                        <td class="text-end">{{ $loanType->max_amount !== null ? 'P'.number_format((float) $loanType->max_amount, 2) : 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $loanType->is_active ? 'success' : 'secondary' }}">
                                {{ $loanType->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('loan-types.show', [...$tenantParameter, 'loan_type' => $loanType]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $loanType)
                                    <a href="{{ route('loan-types.edit', [...$tenantParameter, 'loan_type' => $loanType]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $loanType)
                                    <form action="{{ route('loan-types.destroy', [...$tenantParameter, 'loan_type' => $loanType]) }}" method="POST"
                                        data-confirm="Delete this loan type?"
                                        data-confirm-title="Delete loan type?"
                                        data-confirm-confirm-text="Delete"
                                        data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">No loan types have been configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($loanTypes->hasPages())
        <div class="card-footer bg-white">
            {{ $loanTypes->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
