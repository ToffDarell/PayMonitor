@extends('layouts.central')

@section('title', 'Release Management')

@push('styles')
<style>
    .release-page .release-subtitle {
        color: #8b949e;
    }

    .release-page .release-sync-btn {
        min-width: 188px;
    }

    .release-page .release-stat-card {
        min-height: 126px;
        background: linear-gradient(160deg, rgba(22, 27, 34, 0.95), rgba(15, 19, 25, 0.95));
    }

    .release-page .release-stat-label {
        color: #8b949e;
        letter-spacing: 0.12em;
    }

    .release-page .release-stat-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 700;
        color: #f8fafc;
    }

    .release-page .release-stat-value.release-success {
        color: #22c55e;
    }

    .release-page .release-stat-value.release-warning {
        color: #facc15;
    }

    .release-page .release-stat-value.release-danger {
        color: #f43f5e;
    }

    .release-page .release-table-card {
        overflow: hidden;
    }

    .release-page .release-table-header {
        letter-spacing: 0.04em;
    }

    .release-page .release-version {
        border: 1px solid rgba(236, 72, 153, 0.2);
        background: rgba(236, 72, 153, 0.1);
        color: #f472b6;
        border-radius: 0.5rem;
        padding: 0.3rem 0.6rem;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.06em;
    }

    .release-page .release-status-badges {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .release-page .release-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.45rem;
    }

    .release-page .release-action-btn {
        min-width: 130px;
    }

    .release-page .release-empty {
        color: #8b949e;
    }

    @media (max-width: 991.98px) {
        .release-page .release-actions {
            justify-content: flex-start;
        }

        .release-page .release-action-btn {
            min-width: 116px;
        }
    }
</style>
@endpush

@section('content')
<div class="release-page container-fluid py-2">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Release Management</h1>
            <p class="release-subtitle mb-0">Manage application releases and tenant rollout</p>
        </div>
        <form action="{{ route('central.releases.sync') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary release-sync-btn">
                <i class="bi bi-arrow-repeat me-2"></i>Sync from GitHub
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card release-stat-card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="release-stat-label text-uppercase small mb-3">Total Tenants</h6>
                    <p class="release-stat-value mb-0">{{ $statistics['total_tenants'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card release-stat-card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="release-stat-label text-uppercase small mb-3">Up to Date</h6>
                    <p class="release-stat-value release-success mb-0">{{ $statistics['up_to_date'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card release-stat-card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="release-stat-label text-uppercase small mb-3">Needs Update</h6>
                    <p class="release-stat-value release-warning mb-0">{{ $statistics['needs_update'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card release-stat-card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="release-stat-label text-uppercase small mb-3">Failed</h6>
                    <p class="release-stat-value release-danger mb-0">{{ $statistics['failed'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card release-table-card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="release-table-header mb-0">Available Releases</h5>
            <span class="badge bg-light text-dark">{{ method_exists($releases, 'total') ? $releases->total() : count($releases) }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Version</th>
                            <th>Title</th>
                            <th>Published</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($releases as $release)
                            <tr>
                                <td>
                                    <code class="release-version">{{ $release->version }}</code>
                                </td>
                                <td>
                                    <span class="fw-semibold text-white">{{ $release->title }}</span>
                                </td>
                                <td class="text-nowrap">{{ $release->published_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="release-status-badges">
                                        @if($release->is_required)
                                            <span class="badge bg-danger">Required</span>
                                        @endif
                                        @if($release->is_stable)
                                            <span class="badge bg-success">Stable</span>
                                        @else
                                            <span class="badge bg-warning">Pre-release</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="release-actions">
                                        @if(!$release->is_required)
                                            <button type="button" class="btn btn-sm btn-outline-danger release-action-btn" data-bs-toggle="modal" data-bs-target="#markRequiredModal{{ $release->id }}">
                                                Mark Required
                                            </button>
                                        @else
                                            <form action="{{ route('central.releases.unmark-required', $release) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary release-action-btn">Unmark Required</button>
                                            </form>
                                        @endif
                                        
                                        <form action="{{ route('central.releases.notify-all', $release) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary release-action-btn">Notify All</button>
                                        </form>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-warning release-action-btn" data-bs-toggle="modal" data-bs-target="#forceMarkModal{{ $release->id }}">
                                            Force Mark All
                                        </button>
                                    </div>

                                    <!-- Mark Required Modal -->
                                    <div class="modal fade" id="markRequiredModal{{ $release->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('central.releases.mark-required', $release) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Mark as Required</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Mark version <strong>{{ $release->version }}</strong> as required?</p>
                                                        <div class="mb-3">
                                                            <label for="grace_days_{{ $release->id }}" class="form-label">Grace Period (days)</label>
                                                            <input type="number" class="form-control" id="grace_days_{{ $release->id }}" name="grace_days" value="7" min="0" max="90">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Mark Required</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Force Mark Modal -->
                                    <div class="modal fade" id="forceMarkModal{{ $release->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('central.releases.force-mark-all', $release) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Force Mark All Tenants</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <strong>Warning:</strong> This will mark ALL tenants as updated to version {{ $release->version }} without running the actual update pipeline.
                                                        </div>
                                                        <p>Use this only for state correction, not for actual deployments.</p>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="confirm{{ $release->id }}" name="confirm" value="1" required>
                                                            <label class="form-check-label" for="confirm{{ $release->id }}">
                                                                I understand this is a state-only change
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">Force Mark All</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="release-empty text-center py-4">
                                    No releases found. Sync from GitHub to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($releases->hasPages())
            <div class="card-footer bg-white">
                {{ $releases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
