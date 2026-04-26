@extends('layouts.tenant')

@section('title', 'Create Role')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

@include('users._tabs')

<div class="d-flex justify-content-between align-items-center mb-4 mx-auto" style="max-width: 860px;">
    <div>
        <h1 class="h3 fw-bold mb-1">Create Custom Role</h1>
        <p class="text-muted mb-0">Create a reusable access role for your cooperative team.</p>
    </div>
    <a href="{{ route('users.roles.index', $tenantParameter) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Roles
    </a>
</div>

<div class="card border-0 shadow-sm mx-auto" style="max-width: 860px;">
    <div class="card-body p-4">
        <form action="{{ route('users.roles.store', $tenantParameter) }}" method="POST" class="row g-3">
            @csrf

            <div class="col-12">
                <label for="name" class="form-label fw-semibold">Role Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Collector" required>
                <div class="form-text">We will save this as a reusable custom role and show it in the user role dropdown.</div>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3">
                    <h2 class="h6 fw-bold mb-3">Permissions</h2>
                    <p class="text-muted small mb-3">Choose the permissions this custom role should have.</p>

                    @foreach($permissionGroups as $groupLabel => $permissions)
                        <div class="mb-3">
                            <p class="small text-uppercase fw-semibold text-muted mb-2">{{ $groupLabel }}</p>
                            <div class="row g-2">
                                @foreach($permissions as $permission)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="permission_{{ str_replace('.', '_', $permission) }}"
                                                name="permissions[]"
                                                value="{{ $permission }}"
                                                @checked(in_array($permission, $selectedPermissions, true))
                                            >
                                            <label class="form-check-label" for="permission_{{ str_replace('.', '_', $permission) }}">
                                                {{ $permissionLabels[$permission] ?? $permission }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @error('permissions') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @error('permissions.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a href="{{ route('users.roles.index', $tenantParameter) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-shield-plus me-2"></i>Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
