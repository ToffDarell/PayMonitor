@extends('layouts.app')

@section('title', 'Edit Plan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Edit Plan — {{ $plan->name }}</h5>
    <a href="{{ route('superadmin.plans.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width: 520px;">
    <div class="card-body">
        <form action="{{ route('superadmin.plans.update', $plan) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Plan Name</label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Price (₱)</label>
                <input type="number" name="price" value="{{ old('price', $plan->price) }}" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" required>
                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Max Branches</label>
                    <input type="number" name="max_branches" value="{{ old('max_branches', $plan->max_branches) }}" min="1" class="form-control @error('max_branches') is-invalid @enderror" required>
                    @error('max_branches')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Max Users</label>
                    <input type="number" name="max_users" value="{{ old('max_users', $plan->max_users) }}" min="1" class="form-control @error('max_users') is-invalid @enderror" required>
                    @error('max_users')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Plan</button>
        </form>
    </div>
</div>
@endsection
