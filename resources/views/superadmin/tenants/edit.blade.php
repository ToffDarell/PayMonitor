@extends('layouts.app')

@section('title', 'Edit Tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Edit Tenant — {{ $tenant->name }}</h5>
    <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width: 680px;">
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
                    <label class="form-label fw-semibold">Company Name</label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $tenant->slug) }}" class="form-control @error('slug') is-invalid @enderror" required>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $tenant->address) }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Tenant</button>
        </form>
    </div>
</div>
@endsection
