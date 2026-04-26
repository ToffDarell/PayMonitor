@extends('layouts.tenant')

@section('title', 'Branches')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Branches</h1>
        <p class="text-muted mb-0">Manage branch locations, branch activity, and local staffing.</p>
    </div>
    @role('tenant_admin')
        <a href="{{ route('branches.create', $tenantParameter) }}" class="btn btn-primary">
            <i class="bi bi-diagram-3-fill me-2"></i>Add Branch
        </a>
    @endrole
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 72px;">#</th>
                    <th>Branch Name</th>
                    <th>Address</th>
                    <th class="text-center">Staff Count</th>
                    <th class="text-center">Active Loans Count</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @if($branches->isEmpty())
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">No branches have been created yet.</td>
                    </tr>
                @else
                    @foreach($branches as $branch)
                    <tr>
                        <td>{{ $branches->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('branches.show', [...$tenantParameter, 'branch' => $branch]) }}" class="text-decoration-none fw-semibold">
                                {{ $branch->name }}
                            </a>
                        </td>
                        <td>{{ $branch->address ?: 'No address provided.' }}</td>
                        <td class="text-center">{{ number_format((int) ($branch->staff_count ?? 0)) }}</td>
                        <td class="text-center">{{ number_format((int) ($branch->active_loans_count ?? 0)) }}</td>
                        <td>
                            <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }}">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('branches.show', [...$tenantParameter, 'branch' => $branch]) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('branches.edit', [...$tenantParameter, 'branch' => $branch]) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('branches.destroy', [...$tenantParameter, 'branch' => $branch]) }}" method="POST"
                                    data-confirm="Delete this branch?"
                                    data-confirm-title="Delete branch?"
                                    data-confirm-confirm-text="Delete"
                                    data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if($branches->hasPages())
        <div class="card-footer bg-white">
            {{ $branches->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
