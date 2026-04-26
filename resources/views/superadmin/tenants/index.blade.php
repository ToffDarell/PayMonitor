@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Tenants</h5>
    <a href="{{ route('superadmin.tenants.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Tenant
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Plan</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr>
                        <td>{{ $tenant->id }}</td>
                        <td>{{ $tenant->name }}</td>
                        <td><code>{{ $tenant->slug }}</code></td>
                        <td>{{ $tenant->plan?->name ?? '—' }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td>
                            @if($tenant->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $tenant->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('superadmin.tenants.destroy', $tenant) }}" method="POST" class="d-inline"
                                data-confirm="Delete this tenant?"
                                data-confirm-title="Delete tenant?"
                                data-confirm-confirm-text="Delete"
                                data-confirm-tone="danger">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No tenants found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tenants->hasPages())
        <div class="card-footer bg-white">{{ $tenants->links() }}</div>
    @endif
</div>
@endsection
