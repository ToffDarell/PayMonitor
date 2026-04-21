@extends('layouts.tenant')

@section('title', 'Audit Logs')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $actionClasses = [
        'created' => 'bg-success-subtle text-success border border-success-subtle',
        'updated' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'deleted' => 'bg-danger-subtle text-danger border border-danger-subtle',
    ];
@endphp

<div x-data="{ selectedLog: null }">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Audit Logs</h1>
            <p class="text-muted mb-0">Track all system actions and changes.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('tenant.audit-logs', $tenantParameter) }}" class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label for="action" class="form-label fw-semibold">Action</label>
                    <select id="action" name="action" class="form-select">
                        <option value="">All</option>
                        @foreach($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="model" class="form-label fw-semibold">Model</label>
                    <select id="model" name="model" class="form-select">
                        <option value="">All</option>
                        @foreach($modelOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['model'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="user_id" class="form-label fw-semibold">User</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">All</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="date_from" class="form-label fw-semibold">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="date_to" class="form-label fw-semibold">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-1 d-grid d-xl-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </div>
                <div class="col-md-6 col-xl-12 d-flex justify-content-end">
                    <a href="{{ route('tenant.audit-logs', $tenantParameter) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date &amp; Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Record ID</th>
                        <th class="text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLogs->firstItem() + $loop->index }}</td>
                            <td>
                                <span title="{{ $auditLog->created_at?->diffForHumans() }}">
                                    {{ $auditLog->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $auditLog->user?->name ?? 'System' }}</div>
                                @if($auditLog->role_label)
                                    <span class="badge bg-light text-dark mt-1">{{ $auditLog->role_label }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $actionClasses[$auditLog->action] ?? 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                                    {{ ucfirst($auditLog->action) }}
                                </span>
                            </td>
                            <td>{{ $auditLog->module_label }}</td>
                            <td class="fw-semibold">{{ $auditLog->record_reference }}</td>
                            <td class="text-end">
                                @if(count($auditLog->changes) > 0)
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        x-on:click="selectedLog = @js([
                                            'title' => $auditLog->module_label.' '.$auditLog->record_reference,
                                            'action' => ucfirst($auditLog->action),
                                            'created_at' => $auditLog->created_at?->format('M d, Y h:i A') ?? 'N/A',
                                            'changes' => $auditLog->changes,
                                        ])"
                                    >
                                        View Changes
                                    </button>
                                @else
                                    <span class="text-muted small">No changes</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="fw-semibold mb-1">No audit logs found.</div>
                                <p class="text-muted mb-0">Actions performed in this system will be recorded here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($auditLogs->hasPages())
            <div class="border-top px-4 py-3">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>

    <div
        x-cloak
        x-show="selectedLog"
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 px-3 py-4"
        style="display: none;"
    >
        <div
            x-show="selectedLog"
            x-transition.scale
            class="w-full max-w-5xl rounded-4 border border-white/10 bg-slate-950 shadow-2xl"
            @click.away="selectedLog = null"
        >
            <div class="d-flex align-items-start justify-content-between gap-3 border-bottom border-white/10 px-4 py-3">
                <div>
                    <h2 class="h5 fw-bold mb-1 text-white" x-text="selectedLog?.title"></h2>
                    <p class="mb-0 text-sm text-slate-400">
                        <span x-text="selectedLog?.action"></span>
                        <span class="mx-2">•</span>
                        <span x-text="selectedLog?.created_at"></span>
                    </p>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="selectedLog = null">Close</button>
            </div>

            <div class="p-4">
                <template x-if="selectedLog && selectedLog.changes.length">
                    <div class="row g-3">
                        <template x-for="change in selectedLog.changes" :key="change.key">
                            <div class="col-12">
                                <div class="rounded-4 border border-white/10 bg-white/[0.02] p-3">
                                    <div class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500" x-text="change.key"></div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="rounded-3 border border-danger/20 bg-danger/10 p-3">
                                                <div class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-danger">Old Value</div>
                                                <pre class="mb-0 whitespace-pre-wrap text-sm text-danger-emphasis" x-text="change.old"></pre>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="rounded-3 border border-success/20 bg-success/10 p-3">
                                                <div class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-success">New Value</div>
                                                <pre class="mb-0 whitespace-pre-wrap text-sm text-success-emphasis" x-text="change.new"></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="selectedLog && !selectedLog.changes.length">
                    <div class="rounded-4 border border-white/10 bg-white/[0.02] px-4 py-5 text-center text-sm text-slate-400">
                        No field-level changes were captured for this entry.
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
