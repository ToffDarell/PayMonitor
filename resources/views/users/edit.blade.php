@extends('layouts.tenant')

@section('title', 'Edit User')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $currentRole = old('role', $user->getRoleNames()->first() ?? 'viewer');
@endphp

@include('users._tabs')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Edit User</h1>
        <p class="text-muted mb-0">Update staff role assignments and branch placement.</p>
    </div>
    <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to User
    </a>
</div>

<div
    class="card border-0 shadow-sm"
    style="max-width: 760px;"
    x-data="{
        selectedRole: @js($currentRole),
        rolePermissionMap: @js($rolePermissionMap),
        applyRolePermissions() {
            const allowed = this.rolePermissionMap[this.selectedRole] ?? [];
            this.$refs.permissionToggles.querySelectorAll('[data-permission-toggle]').forEach((input) => {
                input.checked = allowed.includes(input.value);
            });
        }
    }"
>
    <div class="card-body p-4">
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" form="user-edit-form" @checked(old('is_active', $user->is_active))>
            <label class="form-check-label fw-semibold" for="is_active">Active / Inactive</label>
            <div class="form-text">Inactive users cannot sign in until they are reactivated.</div>
        </div>

        <form id="user-edit-form" action="{{ route('users.update', [...$tenantParameter, 'user' => $user]) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="permissions_present" value="1">

            <div class="col-12">
                <label for="name" class="form-label fw-semibold">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label fw-semibold">Role</label>
                <select id="role" name="role" x-model="selectedRole" @change="applyRolePermissions()" class="form-select @error('role') is-invalid @enderror" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected($currentRole === $role->name)>{{ \App\Support\TenantPermissions::displayRoleName($role->name) }}</option>
                    @endforeach
                </select>
                <div class="form-text">
                    Need to adjust reusable role access? <a href="{{ route('users.roles.index', $tenantParameter) }}">Manage roles</a>.
                </div>
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

            <div class="col-12">
                <div class="border rounded-3 p-3" x-ref="permissionToggles">
                    <h2 class="h6 fw-bold mb-3">Permission Toggles</h2>
                    <p class="text-muted small mb-3">Changing the role resets these toggles to that role's defaults. You can then fine-tune the user if needed.</p>

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
                                                data-permission-toggle
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
                <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
