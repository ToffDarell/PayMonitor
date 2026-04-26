@extends('layouts.tenant')

@section('title', 'System Updates')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">System Updates</h1>
            <p class="text-muted mb-0">Manage your application version and apply updates</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-2">Running Version</h6>
                    <p class="h4 fw-bold mb-1">{{ config('app.version', 'Unknown') }}</p>
                    <small class="text-muted">Currently deployed code version</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-2">Last Applied via Pipeline</h6>
                    @if($current)
                        <p class="h4 fw-bold mb-1">{{ $current->appRelease->version }}</p>
                        <small class="text-muted">Applied {{ $current->applied_at?->diffForHumans() }}</small>
                    @else
                        <p class="h4 fw-bold mb-1 text-warning">Not Set</p>
                        <small class="text-muted">No update history</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-2">Latest Published Release</h6>
                    @if(count($available) > 0)
                        <p class="h4 fw-bold mb-1 text-success">{{ $available[0]['version'] ?? $available[0]['tag'] ?? 'N/A' }}</p>
                        <small class="text-muted">Update available</small>
                    @else
                        <p class="h4 fw-bold mb-1">{{ $current?->appRelease->version ?? 'N/A' }}</p>
                        <small class="text-muted">You're up to date</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($required)
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div>
                <strong>Required Update</strong><br>
                Version {{ $required->appRelease->version }} is required.
                @if($required->grace_until && now()->isBefore($required->grace_until))
                    Grace period ends {{ $required->grace_until->diffForHumans() }}.
                @else
                    Grace period has expired.
                @endif
            </div>
        </div>
    @endif

    @if(count($available) > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Available Updates</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Version</th>
                                <th>Title</th>
                                <th>Published</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($available as $release)
                                <tr>
                                    <td><code>{{ $release['version'] ?? $release['tag'] }}</code></td>
                                    <td>{{ $release['title'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($release['published_at'])->format('M d, Y') }}</td>
                                    <td>
                                        @if($release['is_required'])
                                            <span class="badge bg-danger">Required</span>
                                        @elseif($release['is_stable'])
                                            <span class="badge bg-success">Stable</span>
                                        @else
                                            <span class="badge bg-warning">Pre-release</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('tenant.updates.apply', ['tenant' => request()->route('tenant')]) }}" method="POST" class="d-inline"
                                            x-data="{ isUpdating: false }"
                                            x-on:submit="if (isUpdating) { $event.preventDefault(); return; }"
                                            x-on:pm:confirmed-submit="isUpdating = true"
                                            data-confirm="This will run migrations for your tenant."
                                            data-confirm-title="Apply this update?"
                                            data-confirm-confirm-text="Apply update"
                                            data-pm-confirm-loading="true">
                                            @csrf
                                            <input type="hidden" name="release_id" value="{{ $release['id'] }}">
                                            <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5" x-bind:disabled="isUpdating">
                                                <i x-show="!isUpdating" class="bi bi-download small"></i>
                                                <span x-cloak x-show="isUpdating" class="spinner-border spinner-border-sm" style="width: .8rem; height: .8rem;" role="status" aria-hidden="true"></span>
                                                <span x-text="isUpdating ? 'Applying Update...' : 'Apply Update'"></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-3">You're Up to Date</h5>
                <p class="text-muted">No updates available at this time.</p>
            </div>
        </div>
    @endif
</div>
@endsection
