@extends('layouts.tenant')

@section('title', 'Members')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Members</h1>
        <p class="text-muted mb-0">Manage borrower profiles, branch assignments, and member activity.</p>
    </div>
    @can('create', \App\Models\Member::class)
        <a href="{{ route('members.create', $tenantParameter) }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-2"></i>Add Member
        </a>
    @endcan
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('members.index', $tenantParameter) }}" class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label for="search" class="form-label fw-semibold">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search member number, name, phone, or email"
                    >
                </div>
            </div>
            <div class="col-md-3">
                <label for="branch" class="form-label fw-semibold">Branch</label>
                <select name="branch" id="branch" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-grid gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                </button>
                <a href="{{ route('members.index', $tenantParameter) }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 72px;">#</th>
                    <th>Member No.</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Branch</th>
                    <th class="text-center">Active Loans</th>
                    <th class="text-end">Outstanding Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $members->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('members.show', [...$tenantParameter, 'member' => $member]) }}" class="text-decoration-none fw-semibold">
                                {{ $member->member_number }}
                            </a>
                        </td>
                        <td>{{ $member->full_name }}</td>
                        <td>{{ $member->phone ?: 'N/A' }}</td>
                        <td>{{ $member->branch?->name ?? 'Unassigned' }}</td>
                        <td class="text-center">{{ number_format((int) ($member->active_loans_count ?? 0)) }}</td>
                        <td class="text-end">P{{ number_format((float) ($member->outstanding_balance_sum ?? 0), 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $member->is_active ? 'success' : 'secondary' }}">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('members.show', [...$tenantParameter, 'member' => $member]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $member)
                                    <a href="{{ route('members.edit', [...$tenantParameter, 'member' => $member]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('create', \App\Models\Loan::class)
                                    <a href="{{ route('loans.create', [...$tenantParameter, 'member_id' => $member->id]) }}" class="btn btn-outline-success">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">No members found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($members->hasPages())
        <div class="card-footer bg-white">
            {{ $members->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
