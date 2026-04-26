@extends('layouts.app')

@section('title', 'Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Plans</h5>
    <a href="{{ route('superadmin.plans.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Plan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Max Branches</th>
                    <th>Max Users</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>{{ $plan->id }}</td>
                        <td class="fw-semibold">{{ $plan->name }}</td>
                        <td>₱{{ number_format($plan->price, 2) }}</td>
                        <td>{{ $plan->max_branches }}</td>
                        <td>{{ $plan->max_users }}</td>
                        <td>
                            @if($plan->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('superadmin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST" class="d-inline"
                                data-confirm="Delete this plan?"
                                data-confirm-title="Delete plan?"
                                data-confirm-confirm-text="Delete"
                                data-confirm-tone="danger">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No plans found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($plans->hasPages())
        <div class="card-footer bg-white">{{ $plans->links() }}</div>
    @endif
</div>
@endsection
