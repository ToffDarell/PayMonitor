@extends('layouts.app')

@section('title', $tenant->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">{{ $tenant->name }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="btn btn-sm btn-primary">Edit</a>
        <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Tenant Info</h6>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Plan</dt>
                    <dd class="col-7">{{ $tenant->plan?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $tenant->email }}</dd>
                    <dt class="col-5 text-muted">Phone</dt>
                    <dd class="col-7">{{ $tenant->phone ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Address</dt>
                    <dd class="col-7">{{ $tenant->address ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        @if($tenant->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </dd>
                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7">{{ $tenant->created_at->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Branches ({{ $tenant->branches->count() }})</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Phone</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($tenant->branches as $branch)
                            <tr>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->phone ?? '—' }}</td>
                                <td>
                                    @if($branch->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-2">No branches.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Users ({{ $tenant->users->count() }})</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Email</th><th>Role</th></tr>
                    </thead>
                    <tbody>
                        @forelse($tenant->users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge bg-secondary text-uppercase">{{ $user->role }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-2">No users.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
