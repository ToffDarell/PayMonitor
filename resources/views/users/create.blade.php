@extends('layouts.tenant')

@section('title', 'Create User')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $passwordValue = old('generated_password', $generatedPassword);
    $customRolesEnabled = \App\Support\TenantFeatures::tenantHasFeature('custom_roles');
@endphp

@include('users._tabs')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Create User</h1>
        <p class="text-muted mb-0">Provision a new staff account and assign the right cooperative role.</p>
    </div>
    <a href="{{ route('users.index', $tenantParameter) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Users
    </a>
</div>

<div
    class="card border-0 shadow-sm"
    x-data="{
        password: @js($passwordValue),
        copied: false,
        selectedRole: @js(old('role', $selectedRole)),
        rolePermissionMap: @js($rolePermissionMap),
        copyPassword() {
            navigator.clipboard.writeText(this.password).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            });
        },
        applyRolePermissions() {
            const allowed = this.rolePermissionMap[this.selectedRole] ?? [];
            this.$refs.permissionToggles.querySelectorAll('[data-permission-toggle]').forEach((input) => {
                input.checked = allowed.includes(input.value);
            });
        }
    }"
>
    <div class="card-body p-4">
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>Save this password. It will not be shown again.</div>
        </div>

        <form action="{{ route('users.store', $tenantParameter) }}" method="POST" class="row g-3">
            @csrf
            <input type="hidden" name="permissions_present" value="1">

            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">Full Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-8">
                <label for="generated_password" class="form-label fw-semibold">Password</label>
                <input type="text" id="generated_password" name="generated_password" x-model="password" value="{{ $passwordValue }}" class="form-control @error('generated_password') is-invalid @enderror" readonly>
                @error('generated_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 d-grid">
                <label class="form-label fw-semibold d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-outline-secondary" @click="copyPassword()">
                    <i class="bi bi-clipboard me-2"></i><span x-text="copied ? 'Copied' : 'Copy Password'"></span>
                </button>
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label fw-semibold">Role *</label>
                <select id="role" name="role" x-model="selectedRole" @change="applyRolePermissions()" class="form-select @error('role') is-invalid @enderror" required>
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $selectedRole) === $role->name)>{{ \App\Support\TenantPermissions::displayRoleName($role->name) }}</option>
                    @endforeach
                </select>
                @if($customRolesEnabled)
                    <div class="form-text">
                        Need a reusable role like collector? <a href="{{ route('users.roles.index', $tenantParameter) }}">Manage roles</a>.
                    </div>
                @endif
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="branch_id" class="form-label fw-semibold">Branch Assignment</label>
                <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">Unassigned</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3" x-ref="permissionToggles">
                    <h2 class="h6 fw-bold mb-3">Permission Toggles</h2>
                    <p class="text-muted small mb-3">Choose a role first. Changing the role resets the toggles to that role's default permissions, then you can fine-tune them if needed.</p>

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

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                    <label class="form-check-label fw-semibold" for="is_active">Active / Inactive</label>
                    <div class="form-text">Inactive users cannot sign in until they are reactivated.</div>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a href="{{ route('users.index', $tenantParameter) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill me-2"></i>Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
