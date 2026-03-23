@extends('layouts.tenant')

@section('title', 'Edit User')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $currentRole = old('role', $user->getRoleNames()->first() ?? 'viewer');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Edit User</h1>
        <p class="text-muted mb-0">Update staff role assignments and branch placement.</p>
    </div>
    <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to User
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width: 760px;">
    <div class="card-body p-4">
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active_display" checked disabled>
            <label class="form-check-label fw-semibold" for="is_active_display">Active / Inactive</label>
            <div class="form-text">Tenant user activation is not yet modeled in the current schema, so this toggle is display-only for now.</div>
        </div>

        <form action="{{ route('users.update', [...$tenantParameter, 'user' => $user]) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="name" class="form-label fw-semibold">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Email</label>
                <div class="form-control bg-light">{{ $user->email }}</div>
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label fw-semibold">Role</label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                    @foreach(['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'] as $role)
                        <option value="{{ $role }}" @selected($currentRole === $role)>{{ str_replace('_', ' ', $role) }}</option>
                    @endforeach
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="branch_id" class="form-label fw-semibold">Branch</label>
                <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">Unassigned</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
