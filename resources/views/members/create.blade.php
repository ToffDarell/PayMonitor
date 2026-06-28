@extends('layouts.tenant')

@section('title', 'Register Member')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Register Member</h1>
        <p class="text-muted mb-0">Create a new borrower profile for this cooperative.</p>
    </div>
    <a href="{{ route('members.index', $tenantParameter) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Members
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        {{-- Alpine.js for Monthly Salary auto-comma formatting on input --}}
        <form action="{{ route('members.store', $tenantParameter) }}" method="POST" x-data="{
            monthlySalaryRaw: '{{ old('monthly_salary') }}',
            formatSalary(n) {
                if (!n) return '';
                let num = n.replace(/,/g, '');
                if (!num || isNaN(num)) return n;
                let parts = num.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return parts.join('.');
            },
            submitForm() {
                document.getElementById('monthly_salary_hidden').value = this.monthlySalaryRaw.replace(/,/g, '');
            }
        }" @submit="submitForm()">
            @csrf
            {{-- Row 1: Personal Information (left) | Employment & Financial (right) --}}
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="border rounded-3 p-4 h-100">
                        <h2 class="h5 fw-bold mb-3">Personal Information</h2>

                        <div class="mb-3">
                            <label for="first_name" class="form-label fw-semibold">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="middle_name" class="form-label fw-semibold">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}" class="form-control @error('middle_name') is-invalid @enderror">
                            @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="birthdate" class="form-label fw-semibold">Birthdate</label>
                            <input type="text" data-datepicker id="birthdate" name="birthdate" value="{{ old('birthdate') }}" class="form-control @error('birthdate') is-invalid @enderror">
                            @error('birthdate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label fw-semibold">Gender</label>
                            <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Select gender</option>
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="civil_status" class="form-label fw-semibold">Civil Status</label>
                            <select id="civil_status" name="civil_status" class="form-select @error('civil_status') is-invalid @enderror">
                                <option value="">Select status</option>
                                <option value="single" @selected(old('civil_status') === 'single')>Single</option>
                                <option value="married" @selected(old('civil_status') === 'married')>Married</option>
                                <option value="widowed" @selected(old('civil_status') === 'widowed')>Widowed</option>
                            </select>
                            @error('civil_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="valid_id_type" class="form-label fw-semibold">Valid ID Type</label>
                            <select id="valid_id_type" name="valid_id_type" class="form-select @error('valid_id_type') is-invalid @enderror">
                                <option value="">Select ID type</option>
                                <option value="Driver's License" @selected(old('valid_id_type') === "Driver's License")>Driver's License</option>
                                <option value="Passport" @selected(old('valid_id_type') === 'Passport')>Passport</option>
                                <option value="UMID" @selected(old('valid_id_type') === 'UMID')>UMID (Unified Multi-Purpose ID)</option>
                                <option value="SSS ID" @selected(old('valid_id_type') === 'SSS ID')>SSS ID</option>
                                <option value="GSIS ID" @selected(old('valid_id_type') === 'GSIS ID')>GSIS ID</option>
                                <option value="TIN ID" @selected(old('valid_id_type') === 'TIN ID')>TIN ID</option>
                                <option value="PRC ID" @selected(old('valid_id_type') === 'PRC ID')>PRC ID</option>
                                <option value="Voter's ID" @selected(old('valid_id_type') === "Voter's ID")>Voter's ID</option>
                                <option value="PhilHealth ID" @selected(old('valid_id_type') === 'PhilHealth ID')>PhilHealth ID</option>
                                <option value="Pag-IBIG ID" @selected(old('valid_id_type') === 'Pag-IBIG ID')>Pag-IBIG ID</option>
                                <option value="Postal ID" @selected(old('valid_id_type') === 'Postal ID')>Postal ID</option>
                                <option value="National ID" @selected(old('valid_id_type') === 'National ID')>National ID (PhilSys ID)</option>
                                <option value="Senior Citizen ID" @selected(old('valid_id_type') === 'Senior Citizen ID')>Senior Citizen ID</option>
                                <option value="PWD ID" @selected(old('valid_id_type') === 'PWD ID')>PWD ID</option>
                                <option value="Barangay ID" @selected(old('valid_id_type') === 'Barangay ID')>Barangay ID</option>
                                <option value="Police Clearance" @selected(old('valid_id_type') === 'Police Clearance')>Police Clearance</option>
                                <option value="NBI Clearance" @selected(old('valid_id_type') === 'NBI Clearance')>NBI Clearance</option>
                                <option value="Company ID" @selected(old('valid_id_type') === 'Company ID')>Company ID</option>
                            </select>
                            @error('valid_id_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="valid_id_number" class="form-label fw-semibold">Valid ID Number</label>
                            <input type="text" id="valid_id_number" name="valid_id_number" value="{{ old('valid_id_number') }}" class="form-control @error('valid_id_number') is-invalid @enderror">
                            @error('valid_id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Employment & Financial Information section --}}
                <div class="col-lg-6">
                    <div class="border rounded-3 p-4 h-100">
                        <h2 class="h5 fw-bold mb-3">Employment & Financial Information</h2>

                        {{-- Moved from Contact & Branch Details to group with salary --}}
                        <div class="mb-3">
                            <label for="occupation" class="form-label fw-semibold">Occupation</label>
                            <input type="text" id="occupation" name="occupation" value="{{ old('occupation') }}" class="form-control @error('occupation') is-invalid @enderror">
                            @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Monthly Salary with Alpine.js auto-comma formatting --}}
                        {{-- Hidden input submits raw value; visible input displays formatted --}}
                        <div class="mb-0">
                            <label for="monthly_salary" class="form-label fw-semibold">Monthly Salary</label>
                            <input type="hidden" id="monthly_salary_hidden" name="monthly_salary" value="{{ old('monthly_salary') }}">
                            <input type="text" id="monthly_salary" inputmode="decimal" placeholder="0.00" class="form-control @error('monthly_salary') is-invalid @enderror" x-ref="salaryInput" x-init="$refs.salaryInput.value = formatSalary(monthlySalaryRaw)" x-on:input="monthlySalaryRaw = $event.target.value.replace(/[^0-9.]/g, ''); $refs.salaryInput.value = formatSalary(monthlySalaryRaw)" x-on:keydown="if ($event.ctrlKey || $event.metaKey) return; if (!'0123456789.'.includes($event.key) && $event.key.length === 1) $event.preventDefault()" data-format-currency>
                            @error('monthly_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 2: Contact and Branch Details (left) | Co-maker Information (right) --}}
            <div class="row g-4 mt-2">
                <div class="col-lg-6">
                    <div class="border rounded-3 p-4 h-100">
                        {{-- Note: Occupation moved to Employment & Financial section above --}}
                        <h2 class="h5 fw-bold mb-3">Contact and Branch Details</h2>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label fw-semibold">Complete Address</label>
                            <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="branch_id" class="form-label fw-semibold">Branch *</label>
                            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="joined_at" class="form-label fw-semibold">Joined Date</label>
                            <input type="text" data-datepicker id="joined_at" name="joined_at" value="{{ old('joined_at') }}" class="form-control @error('joined_at') is-invalid @enderror">
                            @error('joined_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Co-maker Information section (person who vouches for this member) --}}
                <div class="col-lg-6">
                    <div class="border rounded-3 p-4 h-100">
                        <h2 class="h5 fw-bold mb-3">Co-maker Information</h2>
                        <p class="text-muted small mb-3">Person who vouches for this member.</p>

                        <div class="mb-3">
                            <label for="co_maker_name" class="form-label fw-semibold">Co-maker Name</label>
                            <input type="text" id="co_maker_name" name="co_maker_name" value="{{ old('co_maker_name') }}" placeholder="Full name" class="form-control @error('co_maker_name') is-invalid @enderror">
                            @error('co_maker_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="co_maker_address" class="form-label fw-semibold">Co-maker Address</label>
                            <input type="text" id="co_maker_address" name="co_maker_address" value="{{ old('co_maker_address') }}" placeholder="Complete address" class="form-control @error('co_maker_address') is-invalid @enderror">
                            @error('co_maker_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="co_maker_contact_number" class="form-label fw-semibold">Co-maker Contact Number</label>
                            <input type="text" id="co_maker_contact_number" name="co_maker_contact_number" value="{{ old('co_maker_contact_number') }}" placeholder="Phone number" class="form-control @error('co_maker_contact_number') is-invalid @enderror">
                            @error('co_maker_contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('members.index', $tenantParameter) }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-check-fill me-2"></i>Register Member
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
