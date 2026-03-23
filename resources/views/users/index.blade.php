@extends('layouts.tenant')

@section('title', 'Users')

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

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Users</h1>
        <p class="text-muted mb-0">Manage branch staff access, roles, and assignments.</p>
    </div>
    <a href="{{ route('users.create', $tenantParameter) }}" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-2"></i>Add User
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('users.index', $tenantParameter) }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="role" class="form-label fw-semibold">Role</label>
                <select name="role" id="role" class="form-select">
                    <option value="">All Roles</option>
                    @foreach(['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'] as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ str_replace('_', ' ', $role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="branch_id" class="form-label fw-semibold">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                </button>
            </div>
            <div class="col-md-2 d-grid">
                <a href="{{ route('users.index', $tenantParameter) }}" class="btn btn-light border">Reset</a>
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php($roleName = $user->getRoleNames()->first() ?? 'viewer')
                    <tr>
                        <td>{{ $users->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="text-decoration-none fw-semibold">
                                {{ $user->name }}
                            </a>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-{{ $roleBadgeClasses[$roleName] ?? 'secondary' }}">
                                {{ str_replace('_', ' ', $roleName) }}
                            </span>
                        </td>
                        <td>{{ $user->branch?->name ?? 'Unassigned' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('users.show', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('users.edit', [...$tenantParameter, 'user' => $user]) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('users.destroy', [...$tenantParameter, 'user' => $user]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this user?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">No users found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="card-footer bg-white">
            {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
