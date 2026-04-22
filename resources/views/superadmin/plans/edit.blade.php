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
                    <small class="d-block text-muted mb-1">0 = Unlimited</small>
                    <input type="number" name="max_branches" value="{{ old('max_branches', $plan->max_branches) }}" min="0" class="form-control @error('max_branches') is-invalid @enderror" required>
                    @error('max_branches')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Max Users</label>
                    <small class="d-block text-muted mb-1">0 = Unlimited</small>
                    <input type="number" name="max_users" value="{{ old('max_users', $plan->max_users) }}" min="0" class="form-control @error('max_users') is-invalid @enderror" required>
                    @error('max_users')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>

            <?php
            $availableFeatures = [
                'basic_members' => ['name' => 'Member Management', 'desc' => 'Register and manage cooperative borrowers.', 'requires' => null],
                'loan_management' => ['name' => 'Loan Management', 'desc' => 'Create and track loans with amortization.', 'requires' => null],
                'loan_types' => ['name' => 'Loan Type Configuration', 'desc' => 'Configure custom loan products and interest rates.', 'requires' => null],
                'payment_tracking' => ['name' => 'Payment Tracking', 'desc' => 'Record and monitor loan payment collections.', 'requires' => null],
                'basic_reports' => ['name' => 'Basic Reports', 'desc' => 'Generate PDF and Excel lending reports.', 'requires' => null],
                'branch_management' => ['name' => 'Branch Management', 'desc' => 'Manage multiple cooperative branches.', 'requires' => null],
                'multi_user' => ['name' => 'Multi-User Access', 'desc' => 'Add staff with role-based access control.', 'requires' => null],
                'collections_dashboard' => ['name' => 'Collections Dashboard', 'desc' => 'Real-time collection monitoring and analytics.', 'requires' => 'payment_tracking'],
                'advanced_reports' => ['name' => 'Advanced Reports', 'desc' => 'Full analytics with trend analysis and exports.', 'requires' => 'basic_reports'],
                'audit_logs' => ['name' => 'Audit Logs', 'desc' => 'Complete action history and change tracking.', 'requires' => null],
                'member_documents' => ['name' => 'Member Documents', 'desc' => 'Attach files and documents to member profiles.', 'requires' => 'basic_members'],
                'loan_documents' => ['name' => 'Loan Documents', 'desc' => 'Attach supporting documents to loan records.', 'requires' => 'loan_management'],
                'custom_roles' => ['name' => 'Custom Roles', 'desc' => 'Create custom staff roles and permissions.', 'requires' => 'multi_user'],
                'advanced_analytics' => ['name' => 'Advanced Analytics', 'desc' => 'Business performance insights and forecasting.', 'requires' => 'advanced_reports'],
            ];
            $selectedFeatures = old('features', $plan->features ?? []);
            ?>

            <div class="mb-4">
                <label class="form-label fw-semibold">Plan Features</label>
                <div class="border rounded p-3 bg-light">
                    @foreach($availableFeatures as $key => $feature)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}" id="feature_{{ $key }}" {{ in_array($key, $selectedFeatures) ? 'checked' : '' }}>
                            <label class="form-check-label d-block" for="feature_{{ $key }}">
                                <strong>{{ $feature['name'] }}</strong>
                                <span class="d-block text-muted small">{{ $feature['desc'] }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Plan</button>
        </form>
    </div>
</div>
@endsection
