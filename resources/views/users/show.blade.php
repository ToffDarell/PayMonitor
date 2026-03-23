@extends('layouts.tenant')

@section('title', 'User Profile')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $roleName = $user->getRoleNames()->first() ?? 'viewer';
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
        <h1 class="h3 fw-bold mb-1">User Profile</h1>
        <p class="text-muted mb-0">Review staff account details and access role.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('users.index', $tenantParameter) }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
        <a href="{{ route('users.edit', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square me-2"></i>Edit
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 820px;">
    <div class="card-body p-4">
        <h2 class="h3 fw-bold mb-2">{{ $user->name }}</h2>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <span class="badge bg-{{ $roleBadgeClasses[$roleName] ?? 'secondary' }}">{{ str_replace('_', ' ', $roleName) }}</span>
            <span class="badge bg-light text-dark">{{ $user->branch?->name ?? 'Unassigned Branch' }}</span>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="text-muted small text-uppercase fw-semibold">Email</div>
                <div>{{ $user->email }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small text-uppercase fw-semibold">Created At</div>
                <div>{{ $user->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small text-uppercase fw-semibold">Role</div>
                <div>{{ str_replace('_', ' ', $roleName) }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small text-uppercase fw-semibold">Branch</div>
                <div>{{ $user->branch?->name ?? 'Unassigned' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
