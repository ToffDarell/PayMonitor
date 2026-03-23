@extends('layouts.tenant')

@section('title', 'Loan Type Details')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="fw-bold">{{ $loanType->name }}</h5>
        <p class="text-muted mb-0">{{ $loanType->description ?: 'No description provided.' }}</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Loans Using This Type</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Loan No.</th>
                    <th>Member</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th class="text-end">Principal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td><a href="{{ url('/loans/'.$loan->id) }}">{{ $loan->loan_number }}</a></td>
                        <td>{{ $loan->member?->full_name ?? 'N/A' }}</td>
                        <td>{{ $loan->branch?->name ?? 'N/A' }}</td>
                        <td>{{ str_replace('_', ' ', $loan->status) }}</td>
                        <td class="text-end">{{ number_format((float) $loan->principal_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No loans found for this type.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

