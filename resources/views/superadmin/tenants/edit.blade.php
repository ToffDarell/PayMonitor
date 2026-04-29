@extends('layouts.app')

@section('title', 'Edit Tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Edit Tenant — {{ $tenant->name }}</h5>
    <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form action="{{ route('superadmin.tenants.update', $tenant) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Plan</label>
                <select name="plan_id" class="form-select @error('plan_id') is-invalid @enderror" required>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ old('plan_id', $tenant->plan_id) == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} — ₱{{ number_format($plan->price, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tenant Name</label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(['active' => 'Active', 'overdue' => 'Overdue', 'suspended' => 'Suspended', 'inactive' => 'Inactive'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $tenant->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tenant ID</label>
                    <input type="text" value="{{ $tenant->id }}" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Primary Domain</label>
                    <input type="text" value="{{ $primaryDomain?->domain ?? '—' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Admin Name</label>
                    <input type="text" value="{{ $tenant->admin_name ?? '—' }}" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="text" value="{{ $tenant->email }}" class="form-control" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $tenant->address) }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Subscription Due Date</label>
                <input type="date" name="subscription_due_at" value="{{ old('subscription_due_at', optional($tenant->subscription_due_at)->format('Y-m-d')) }}" class="form-control @error('subscription_due_at') is-invalid @enderror">
                @error('subscription_due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Update Tenant</button>
        </form>
    </div>
</div>
@endsection
