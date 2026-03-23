@extends('layouts.tenant')

@section('title', 'Loans')

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
        <h1 class="h3 fw-bold mb-1">Loans</h1>
        <p class="text-muted mb-0">Track loan releases, balances, repayments, and status changes.</p>
    </div>
    @can('create', \App\Models\Loan::class)
        <a href="{{ route('loans.create', $tenantParameter) }}" class="btn btn-primary">
            <i class="bi bi-cash-coin me-2"></i>New Loan
        </a>
    @endcan
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('loans.index', $tenantParameter) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="branch" class="form-label fw-semibold">Branch</label>
                <select name="branch" id="branch" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['active', 'fully_paid', 'overdue', 'restructured'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="loan_type" class="form-label fw-semibold">Loan Type</label>
                <select name="loan_type" id="loan_type" class="form-select">
                    <option value="">All Loan Types</option>
                    @foreach($loanTypes as $loanType)
                        <option value="{{ $loanType->id }}" @selected((string) request('loan_type') === (string) $loanType->id)>{{ $loanType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label fw-semibold">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label fw-semibold">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel-fill me-1"></i>Apply Filters
                </button>
            </div>
            <div class="col-md-3 d-grid">
                <a href="{{ route('loans.index', $tenantParameter) }}" class="btn btn-light border">Reset</a>
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
                    <th>Loan No.</th>
                    <th>Member</th>
                    <th>Type</th>
                    <th class="text-end">Principal</th>
                    <th class="text-end">Total Payable</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Monthly</th>
                    <th>Status</th>
                    <th>Release Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loans->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="text-decoration-none fw-semibold">
                                {{ $loan->loan_number }}
                            </a>
                        </td>
                        <td>{{ $loan->member?->full_name ?? 'Unknown Member' }}</td>
                        <td>{{ $loan->loanType?->name ?? 'N/A' }}</td>
                        <td class="text-end">P{{ number_format((float) $loan->principal_amount, 2) }}</td>
                        <td class="text-end">P{{ number_format((float) $loan->total_payable, 2) }}</td>
                        <td class="text-end text-danger">P{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                        <td class="text-end">P{{ number_format((float) $loan->monthly_payment, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $loanStatusClasses[$loan->status] ?? 'secondary' }}">
                                {{ str_replace('_', ' ', ucfirst($loan->status)) }}
                            </span>
                        </td>
                        <td>{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $loan)
                                    <a href="{{ route('loans.edit', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-5">No loans found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($loans->hasPages())
        <div class="card-footer bg-white">
            {{ $loans->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
