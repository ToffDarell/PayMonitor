@extends('layouts.tenant')

@section('title', 'Edit Member')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Edit Member</h1>
        <p class="text-muted mb-0">Update profile details for {{ $member->full_name }}.</p>
    </div>
    <a href="{{ route('members.show', [...$tenantParameter, 'member' => $member]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Profile
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="{{ route('members.update', [...$tenantParameter, 'member' => $member]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border rounded-3 p-3 mb-4 bg-light">
                <div>
                    <div class="small text-muted text-uppercase fw-semibold">Member Number</div>
                    <div class="fw-bold">{{ $member->member_number }}</div>
                </div>
                <div class="form-check form-switch m-0">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $member->is_active))>
                    <label class="form-check-label fw-semibold" for="is_active">Active / Inactive</label>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="border rounded-3 p-4 h-100">
                        <h2 class="h5 fw-bold mb-3">Personal Information</h2>

                        <div class="mb-3">
                            <label for="first_name" class="form-label fw-semibold">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $member->first_name) }}" class="form-control @error('first_name') is-invalid @enderror" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $member->last_name) }}" class="form-control @error('last_name') is-invalid @enderror" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="middle_name" class="form-label fw-semibold">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $member->middle_name) }}" class="form-control @error('middle_name') is-invalid @enderror">
                            @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="birthdate" class="form-label fw-semibold">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate', $member->birthdate?->toDateString()) }}" class="form-control @error('birthdate') is-invalid @enderror">
                            @error('birthdate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label fw-semibold">Gender</label>
                            <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Select gender</option>
                                <option value="male" @selected(old('gender', $member->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $member->gender) === 'female')>Female</option>
                                <option value="other" @selected(old('gender', $member->gender) === 'other')>Other</option>
                            </select>
                            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="civil_status" class="form-label fw-semibold">Civil Status</label>
                            <select id="civil_status" name="civil_status" class="form-select @error('civil_status') is-invalid @enderror">
                                <option value="">Select status</option>
                                <option value="single" @selected(old('civil_status', $member->civil_status) === 'single')>Single</option>
                                <option value="married" @selected(old('civil_status', $member->civil_status) === 'married')>Married</option>
                                <option value="widowed" @selected(old('civil_status', $member->civil_status) === 'widowed')>Widowed</option>
                            </select>
                            @error('civil_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded-3 p-4 h-100">
                        <h2 class="h5 fw-bold mb-3">Contact and Branch Details</h2>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $member->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $member->email) }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label fw-semibold">Address</label>
                            <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $member->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="occupation" class="form-label fw-semibold">Occupation</label>
                            <input type="text" id="occupation" name="occupation" value="{{ old('occupation', $member->occupation) }}" class="form-control @error('occupation') is-invalid @enderror">
                            @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="branch_id" class="form-label fw-semibold">Branch *</label>
                            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $member->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="joined_at" class="form-label fw-semibold">Joined Date</label>
                            <input type="date" id="joined_at" name="joined_at" value="{{ old('joined_at', $member->joined_at?->toDateString()) }}" class="form-control @error('joined_at') is-invalid @enderror">
                            @error('joined_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('members.show', [...$tenantParameter, 'member' => $member]) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
