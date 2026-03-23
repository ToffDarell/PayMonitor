@extends('layouts.tenant')

@section('title', 'Branch Details')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $roleBadgeClasses = [
        'tenant_admin' => 'danger',
        'branch_manager' => 'primary',
        'loan_officer' => 'success',
        'cashier' => 'warning text-dark',
        'viewer' => 'secondary',
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Branch Details</h1>
        <p class="text-muted mb-0">Review branch performance, staffing, and borrower coverage.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('branches.index', $tenantParameter) }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
        <a href="{{ route('branches.edit', [...$tenantParameter, 'branch' => $branch]) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square me-2"></i>Edit
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <h2 class="h3 fw-bold mb-2">{{ $branch->name }}</h2>
                <p class="text-muted mb-3">{{ $branch->address ?: 'No address provided.' }}</p>
                <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }}">
                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-semibold mb-2">Staff Count</div><div class="h3 fw-bold mb-0">{{ number_format($staffCount) }}</div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-semibold mb-2">Members Count</div><div class="h3 fw-bold mb-0">{{ number_format($membersCount) }}</div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-semibold mb-2">Active Loans</div><div class="h3 fw-bold mb-0">{{ number_format($activeLoansCount) }}</div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-danger"><div class="card-body"><div class="text-muted text-uppercase small fw-semibold mb-2">Outstanding Balance</div><div class="h3 fw-bold text-danger mb-0">P{{ number_format($outstandingBalance, 2) }}</div></div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Staff</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse (collect($branch->users) as $user)
                    @php($roleName = $user->getRoleNames()->first() ?? 'viewer')
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-{{ $roleBadgeClasses[$roleName] ?? 'secondary' }}">
                                {{ str_replace('_', ' ', $roleName) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('users.edit', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">No staff members are assigned to this branch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
