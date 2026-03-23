@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3 fs-4 text-primary">
                    <i class="bi bi-buildings"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Tenants</div>
                    <div class="fw-bold fs-5">{{ $totalTenants }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3 fs-4 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Tenants</div>
                    <div class="fw-bold fs-5">{{ $activeTenants }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Recent Tenants</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Plan</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr>
                        <td>{{ $tenant->name }}</td>
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
                        <td>
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No tenants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
