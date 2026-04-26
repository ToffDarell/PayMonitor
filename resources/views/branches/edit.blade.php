@extends('layouts.tenant')

@section('title', 'Edit Branch')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Edit Branch</h1>
        <p class="text-muted mb-0">Update branch details and operational status.</p>
    </div>
    <a href="{{ route('branches.show', [...$tenantParameter, 'branch' => $branch]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Branch
    </a>
</div>

<div class="card border-0 shadow-sm mx-auto w-100" style="max-width: 760px;">
    <div class="card-body p-4">
        <form action="{{ route('branches.update', [...$tenantParameter, 'branch' => $branch]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="border rounded-3 p-3 mb-4 bg-light">
                <div class="small text-muted text-uppercase fw-semibold">Branch Setup</div>
                <div class="fw-semibold">Update the branch details and control whether it is active.</div>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Branch Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $branch->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="address" class="form-label fw-semibold">Address</label>
                <textarea id="address" name="address" rows="4" class="form-control @error('address') is-invalid @enderror">{{ old('address', $branch->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <input type="hidden" name="is_active" value="0">
            <div class="border rounded-3 p-3 mb-4 bg-light">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked((bool) old('is_active', $branch->is_active))>
                    <label class="form-check-label fw-semibold" for="is_active">Is Active</label>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('branches.show', [...$tenantParameter, 'branch' => $branch]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Branch
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
