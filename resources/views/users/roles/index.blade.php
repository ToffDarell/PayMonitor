@extends('layouts.tenant')

@section('title', 'Roles')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

@include('users._tabs')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Custom Roles</h1>
        <p class="text-muted mb-0">Create reusable tenant roles like collector, auditor, or encoder with selected permissions.</p>
    </div>
    <a href="{{ route('users.roles.create', $tenantParameter) }}" class="btn btn-primary">
        <i class="bi bi-shield-plus me-2"></i>Create Role
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Role</th>
                    <th>Type</th>
                    <th>Permissions</th>
                    <th>Assigned Users</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    @php($isSystemRole = \App\Support\TenantPermissions::isSystemRole($role->name))
                    <tr>
                        <td class="fw-semibold">{{ \App\Support\TenantPermissions::displayRoleName($role->name) }}</td>
                        <td>
                            <span class="badge bg-{{ $isSystemRole ? 'secondary' : 'info text-dark' }}">
                                {{ $isSystemRole ? 'Default' : 'Custom' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($role->permissions->pluck('name')->sort()->values() as $permission)
                                    <span class="badge bg-light text-dark">{{ $permissionLabels[$permission] ?? $permission }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>{{ number_format($role->users_count ?? 0) }}</td>
                        <td class="text-end">
                            @if($isSystemRole)
                                <span class="text-muted small">Protected</span>
                            @else
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('users.roles.edit', [...$tenantParameter, 'role' => $role]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('users.roles.destroy', [...$tenantParameter, 'role' => $role]) }}" method="POST"
                                        data-confirm="Delete this custom role?"
                                        data-confirm-title="Delete custom role?"
                                        data-confirm-confirm-text="Delete"
                                        data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
