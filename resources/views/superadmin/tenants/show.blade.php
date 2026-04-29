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
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Tenant Info</h6>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Plan</dt>
                    <dd class="col-7">{{ $tenant->plan?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $tenant->email }}</dd>
                    <dt class="col-5 text-muted">Admin Name</dt>
                    <dd class="col-7">{{ $tenant->admin_name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Tenant ID</dt>
                    <dd class="col-7"><code>{{ $tenant->id }}</code></dd>
                    <dt class="col-5 text-muted">Primary Domain</dt>
                    <dd class="col-7">{{ $primaryDomain?->domain ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Address</dt>
                    <dd class="col-7">{{ $tenant->address ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $tenant->status === 'active' ? 'success' : ($tenant->status === 'suspended' ? 'warning text-dark' : 'secondary') }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Due Date</dt>
                    <dd class="col-7">{{ $tenant->subscription_due_at?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7">{{ $tenant->created_at->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Tenant Usage</h6>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="rounded border p-3">
                            <div class="text-muted small">Branches</div>
                            <div class="fw-bold fs-5">{{ number_format($usage['branches'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded border p-3">
                            <div class="text-muted small">Users</div>
                            <div class="fw-bold fs-5">{{ number_format($usage['users'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded border p-3">
                            <div class="text-muted small">Members</div>
                            <div class="fw-bold fs-5">{{ number_format($usage['members'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded border p-3">
                            <div class="text-muted small">Loans</div>
                            <div class="fw-bold fs-5">{{ number_format($usage['loans'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="rounded border p-3">
                            <div class="text-muted small">Total Records</div>
                            <div class="fw-bold fs-4">{{ number_format($usage['total'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
