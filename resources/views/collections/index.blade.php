@extends('layouts.tenant')

@section('title', 'Collections')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $collectionRateClass = match (true) {
        $collectionRate >= 80 => 'text-success',
        $collectionRate >= 50 => 'text-warning',
        default => 'text-danger',
    };
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Collections</h1>
        <p class="text-muted mb-0">Monitor payment collections and overdue accounts.</p>
    </div>
    <a href="{{ route('loan-payments.index', $tenantParameter) }}" class="btn btn-outline-secondary">
        <i class="bi bi-list-ul me-2"></i>View All Payments
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Total Due This Month</div>
                <div class="h3 fw-bold mb-0">P{{ number_format($totalDue, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card border-0 shadow-sm h-100 border-success">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Total Collected This Month</div>
                <div class="h3 fw-bold text-success mb-0">P{{ number_format($totalCollected, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Collection Rate</div>
                <div class="h3 fw-bold {{ $collectionRateClass }} mb-0">{{ number_format($collectionRate, 1) }}%</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card border-0 shadow-sm h-100 {{ $overdueLoans > 0 ? 'border-danger' : '' }}">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Overdue Loans</div>
                <div class="h3 fw-bold {{ $overdueLoans > 0 ? 'text-danger' : '' }} mb-1">{{ number_format($overdueLoans) }}</div>
                <div class="text-muted small">P{{ number_format($overdueAmount, 2) }} outstanding</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card border-0 shadow-sm h-100 border-success">
            <div class="card-body">
                <div class="text-muted text-uppercase small fw-semibold mb-2">Collected Today</div>
                <div class="h3 fw-bold text-success mb-0">P{{ number_format($collectedToday, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h2 class="h5 fw-bold mb-0">Recent Collections</h2>
                <a href="{{ route('loan-payments.index', $tenantParameter) }}" class="btn btn-outline-secondary btn-sm">View All Payments</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Member</th>
                            <th>Loan #</th>
                            <th class="text-end">Amount</th>
                            <th>Recorded By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCollections as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td>{{ $payment->loan?->member?->full_name ?? 'N/A' }}</td>
                                <td class="fw-semibold">{{ $payment->loan?->loan_number ?? 'N/A' }}</td>
                                <td class="text-end text-success fw-semibold">P{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $payment->user?->name ?? 'System' }}</td>
                                <td class="text-end">
                                    @if($payment->loan)
                                        <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $payment->loan]) }}" class="btn btn-outline-primary btn-sm">
                                            View Loan
                                        </a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No collections found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 fw-bold mb-0">Due This Week</h2>
            </div>
            <div class="card-body p-4">
                @forelse($dueThisWeek as $schedule)
                    @php
                        $dueLabelClass = match (true) {
                            $schedule->due_date?->isToday() => 'text-danger',
                            $schedule->due_date?->isTomorrow() => 'text-warning',
                            default => 'text-muted',
                        };
                    @endphp
                    <div class="border rounded-4 p-3 mb-3 bg-light">
                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">{{ $schedule->loan?->member?->full_name ?? 'Unknown Member' }}</div>
                                <div class="text-muted small">{{ $schedule->loan?->loan_number ?? 'N/A' }}</div>
                                <div class="mt-2 text-muted small">Amount Due</div>
                                <div class="fw-semibold">P{{ number_format((float) $schedule->amount_due, 2) }}</div>
                            </div>
                            <div class="text-sm-end">
                                <div class="text-muted small">Due Date</div>
                                <div class="fw-semibold {{ $dueLabelClass }}">
                                    {{ $schedule->due_date?->format('M d, Y') ?? 'N/A' }}
                                </div>
                                @can('create', \App\Models\LoanPayment::class)
                                    @if($schedule->loan)
                                        <a href="{{ route('loan-payments.create', [...$tenantParameter, 'loan' => $schedule->loan->id]) }}" class="btn btn-primary btn-sm mt-3">
                                            Record Payment
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        No scheduled dues in the next 7 days.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('tenant.collections', $tenantParameter) }}" class="row g-3">
            <div class="col-md-6 col-xl-2">
                <label for="branch_id" class="form-label fw-semibold">Branch</label>
                <select id="branch_id" name="branch_id" class="form-select" onchange="this.form.requestSubmit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="collector" class="form-label fw-semibold">Collector</label>
                <select id="collector" name="collector" class="form-select" onchange="this.form.requestSubmit()">
                    <option value="">All Collectors</option>
                    @foreach($collectors as $collector)
                        <option value="{{ $collector->id }}" @selected((string) ($filters['collector'] ?? '') === (string) $collector->id)>{{ $collector->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select id="status" name="status" class="form-select" onchange="this.form.requestSubmit()">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>Overdue</option>
                    <option value="restructured" @selected(($filters['status'] ?? '') === 'restructured')>Restructured</option>
                    <option value="fully_paid" @selected(($filters['status'] ?? '') === 'fully_paid')>Fully Paid</option>
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="date_from" class="form-label fw-semibold">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" onchange="this.form.requestSubmit()">
            </div>
            <div class="col-md-6 col-xl-2">
                <label for="date_to" class="form-label fw-semibold">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" onchange="this.form.requestSubmit()">
            </div>
            <div class="col-md-6 col-xl-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Apply</button>
                <a href="{{ route('tenant.collections', $tenantParameter) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>
@endsection
