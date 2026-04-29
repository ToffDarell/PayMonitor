@extends('layouts.app')

@section('title', 'New Tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">New Tenant</h5>
    <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form action="{{ route('superadmin.tenants.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Plan</label>
                <select name="plan_id" class="form-select @error('plan_id') is-invalid @enderror" required>
                    <option value="">— Select Plan —</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} — ₱{{ number_format($plan->price, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tenant Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subdomain</label>
                    <input type="text" name="domain" value="{{ old('domain') }}" class="form-control @error('domain') is-invalid @enderror" required>
                    @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Admin Name</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="form-control @error('admin_name') is-invalid @enderror" required>
                    @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Admin Email</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" class="form-control @error('admin_email') is-invalid @enderror" required>
                    @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Subscription Due Date</label>
                <input type="date" name="subscription_due_at" value="{{ old('subscription_due_at') }}" class="form-control @error('subscription_due_at') is-invalid @enderror">
                @error('subscription_due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Create Tenant</button>
        </form>
    </div>
</div>
@endsection
