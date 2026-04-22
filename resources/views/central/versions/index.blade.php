@extends('layouts.central')

@section('title', 'Versions')

@php
    $publishedLabel = filled($updateInfo['published_at'] ?? null)
        ? \Illuminate\Support\Carbon::parse((string) $updateInfo['published_at'])->format('M d, Y h:i A')
        : 'Unknown';
    $releaseCount = method_exists($releases, 'total') ? $releases->total() : $releases->count();
    $latestTrackedRelease = $statistics['latest_release'] ?? ($releases->first()?->tag ?? 'None');
    $historyCount = count($updateHistory);
    $trackedTenants = (int) ($statistics['tracked_tenants'] ?? 0);
    $untrackedTenants = (int) ($statistics['untracked'] ?? 0);
    $rolloutState = (string) ($statistics['rollout_state'] ?? 'healthy');
    $rolloutBadgeClasses = match ($rolloutState) {
        'tracking_incomplete' => 'border border-amber-500/30 bg-amber-500/10 text-amber-300',
        'needs_attention' => 'border border-yellow-500/30 bg-yellow-500/10 text-yellow-300',
        default => 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    };
    $rolloutBadgeLabel = match ($rolloutState) {
        'tracking_incomplete' => 'Tracking Incomplete',
        'needs_attention' => 'Needs Attention',
        default => 'Healthy',
    };
@endphp

@section('content')
<div x-data="versionsPage()" class="space-y-8">
    <section class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Central Management</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-white">Versions</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">Manage the central application release, tenant rollout coverage, and recovery actions from one screen.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('central.versions.sync', [], false) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.867-5.303M19.5 12a7.5 7.5 0 0 1-12.867 5.303M19.5 4.5v3.75h-3.75M4.5 19.5v-3.75h3.75"/>
                    </svg>
                    <span>Sync Releases</span>
                </button>
            </form>

            <button
                type="button"
                x-on:click="checkForUpdates"
                x-bind:disabled="checking"
                class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
            >
                <svg x-show="!checking" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.867-5.303M19.5 12a7.5 7.5 0 0 1-12.867 5.303M19.5 4.5v3.75h-3.75M4.5 19.5v-3.75h3.75"/>
                </svg>
                <svg x-show="checking" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m0-12.728 2.121 2.121m8.486 8.486 2.121 2.121"/>
                </svg>
                <span x-text="checking ? 'Checking...' : 'Check Central App'"></span>
            </button>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.9fr)]">
        <div class="rounded-2xl border border-[#273142] bg-[#161b22] p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Central Release Status</p>
                    <h3 class="mt-2 text-2xl font-semibold text-white">{{ $updateInfo['release_name'] ?? 'Latest release' }}</h3>
                    <p class="mt-2 text-sm text-slate-400">Published {{ $publishedLabel }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full border border-white/10 bg-[#0f1319] px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-300">
                        Current {{ $updateInfo['current_version'] }}
                    </span>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ ($updateInfo['update_available'] ?? false) ? 'border border-yellow-500/30 bg-yellow-500/10 text-yellow-300' : 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300' }}">
                        {{ ($updateInfo['update_available'] ?? false) ? 'Update Available' : 'Up to Date' }}
                    </span>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-[#273142] bg-[#0f1319] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Installed</p>
                    <p class="mt-3 text-2xl font-semibold text-white">{{ $updateInfo['current_version'] }}</p>
                </div>
                <div class="rounded-xl border border-[#273142] bg-[#0f1319] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Latest</p>
                    <p class="mt-3 text-2xl font-semibold {{ ($updateInfo['update_available'] ?? false) ? 'text-yellow-300' : 'text-emerald-300' }}">{{ $updateInfo['latest_version'] }}</p>
                </div>
                <div class="rounded-xl border border-[#273142] bg-[#0f1319] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tracked Releases</p>
                    <p class="mt-3 text-2xl font-semibold text-white">{{ $releaseCount }}</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border {{ ($updateInfo['update_available'] ?? false) ? 'border-yellow-500/20 bg-yellow-500/5' : 'border-emerald-500/20 bg-emerald-500/5' }} p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold {{ ($updateInfo['update_available'] ?? false) ? 'text-yellow-300' : 'text-emerald-300' }}">
                            {{ ($updateInfo['update_available'] ?? false) ? 'A newer central release is available.' : 'The central app is already on the latest release.' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-400">
                            {{ ($updateInfo['update_available'] ?? false) ? 'Review the changelog below, then apply the update when you are ready.' : 'You can still sync releases or review tenant rollout history below.' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if(filled($updateInfo['release_url'] ?? null))
                            <a href="{{ $updateInfo['release_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white">
                                View Release
                            </a>
                        @endif

                        @if($updateInfo['update_available'] ?? false)
                            <form method="POST" action="{{ route('central.versions.apply', [], false) }}" onsubmit="return confirm('Apply update to {{ $updateInfo['latest_version'] }}? This will run git pull and clear cache.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-400">
                                    Apply Central Update
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if(($updateInfo['update_available'] ?? false) && $changelogItems !== [])
                    <div class="mt-5 border-t border-white/10 pt-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Latest Changelog</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-300">
                            @foreach($changelogItems as $item)
                                <li class="flex gap-3">
                                    <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-yellow-300"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-[#273142] bg-[#161b22] p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tenant Rollout</p>
                    <h3 class="mt-2 text-2xl font-semibold text-white">Latest stable {{ $latestTrackedRelease }}</h3>
                    <p class="mt-2 text-sm text-slate-400">Snapshot of tenant update coverage across the latest stable version.</p>
                    <p class="mt-3 text-xs font-medium uppercase tracking-[0.16em] text-slate-500">
                        {{ number_format($trackedTenants) }} of {{ number_format((int) ($statistics['total_tenants'] ?? 0)) }} tenants tracked
                    </p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $rolloutBadgeClasses }}">
                    {{ $rolloutBadgeLabel }}
                </span>
            </div>

            @if($untrackedTenants > 0)
                <div class="mt-6 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-amber-300">Tenant rollout tracking needs repair.</p>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ number_format($untrackedTenants) }} tenant{{ $untrackedTenants === 1 ? '' : 's' }} {{ $untrackedTenants === 1 ? 'is' : 'are' }} missing a current release record, so coverage cannot be summarized correctly yet.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('central.versions.backfill-tracking', [], false) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-200 transition hover:border-amber-400/50 hover:bg-amber-500/15 hover:text-amber-100">
                                Backfill Tracking
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-[#273142] bg-[#0f1319] p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Tenants</p>
                    <p class="mt-3 text-3xl font-semibold text-white">{{ $statistics['total_tenants'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Up to Date</p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-200">{{ $statistics['up_to_date'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-yellow-500/20 bg-yellow-500/5 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-yellow-300">Needs Update</p>
                    <p class="mt-3 text-3xl font-semibold text-yellow-200">{{ $statistics['needs_update'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-red-500/20 bg-red-500/5 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-red-300">Failed</p>
                    <p class="mt-3 text-3xl font-semibold text-red-200">{{ $statistics['failed'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-[#273142] bg-[#161b22] p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Release Registry</p>
                <h3 class="mt-2 text-2xl font-semibold text-white">Tracked Versions</h3>
                <p class="mt-2 text-sm text-slate-400">Borrowing the cleaner admin-table structure from the reference app: roomy rows, obvious hierarchy, and consistent action spacing.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex rounded-full border border-white/10 bg-[#0f1319] px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-300">
                    {{ $releaseCount }} total
                </span>
                <button
                    type="button"
                    x-on:click="registryOpen = !registryOpen"
                    class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white"
                >
                    <span x-text="registryOpen ? 'Hide Tracked Versions' : 'Show Tracked Versions'"></span>
                    <svg class="h-4 w-4 transition-transform duration-200" x-bind:class="registryOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-cloak x-show="registryOpen" x-transition.opacity.duration.200ms class="mt-6 space-y-5">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1040px] text-left text-sm text-slate-300">
                    <thead>
                        <tr class="border-b border-[#273142] text-xs uppercase tracking-[0.16em] text-slate-500">
                            <th class="px-4 py-3">Version</th>
                            <th class="px-4 py-3">Details</th>
                            <th class="px-4 py-3">Published</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="w-[220px] px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($releases as $release)
                            <tr class="border-b border-[#273142]/80 align-top transition hover:bg-white/[0.02]">
                                <td class="px-4 py-5">
                                    <p class="text-base font-semibold text-white">{{ $release->tag }}</p>
                                    @if(filled($release->release_url))
                                        <a href="{{ $release->release_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
                                            View release
                                        </a>
                                    @endif
                                </td>
                                <td class="px-4 py-5">
                                    <p class="text-base font-medium text-white">{{ $release->title }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $release->changelog)), 150) ?: 'No changelog summary available.' }}</p>
                                </td>
                                <td class="px-4 py-5 text-sm text-slate-400">{{ $release->published_at?->format('M d, Y') ?? 'Unknown' }}</td>
                                <td class="px-4 py-5">
                                    <div class="flex flex-wrap gap-2">
                                        @if(($statistics['latest_release'] ?? null) === $release->tag)
                                            <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-300">Latest Stable</span>
                                        @endif
                                        @if($release->is_required)
                                            <span class="inline-flex rounded-full border border-red-500/20 bg-red-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-red-300">Required</span>
                                        @endif
                                        @if($release->is_stable)
                                            <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-300">Stable</span>
                                        @else
                                            <span class="inline-flex rounded-full border border-yellow-500/20 bg-yellow-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-yellow-300">Pre-release</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="w-[220px] px-4 py-5">
                                    <div class="ml-auto flex w-full max-w-[190px] flex-col items-stretch gap-2">
                                        @if(! $release->is_required)
                                            <button type="button" class="inline-flex w-full items-center justify-center rounded-lg border border-red-500/30 bg-transparent px-3 py-2 text-center text-sm font-semibold text-red-300 transition hover:border-red-400/50 hover:bg-red-500/5 hover:text-red-200" data-bs-toggle="modal" data-bs-target="#markRequiredModal{{ $release->id }}">
                                                Mark Required
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('central.versions.unmark-required', ['release' => $release], false) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-[#2a3340] bg-[#111827] px-3 py-2 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white">
                                                    Clear Required
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('central.versions.notify-all', ['release' => $release], false) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-[#2a3340] bg-[#111827] px-3 py-2 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white">
                                                Notify Tenants
                                            </button>
                                        </form>

                                        <button type="button" class="inline-flex w-full items-center justify-center rounded-lg border border-yellow-500/30 bg-transparent px-3 py-2 text-center text-sm font-semibold text-yellow-300 transition hover:border-yellow-400/50 hover:bg-yellow-500/5 hover:text-yellow-200" data-bs-toggle="modal" data-bs-target="#forceMarkModal{{ $release->id }}">
                                            Force Mark All
                                        </button>
                                    </div>

                                    <div class="modal fade" id="markRequiredModal{{ $release->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('central.versions.mark-required', ['release' => $release], false) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Mark {{ $release->tag }} as required</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-3">Tenants behind this version will be blocked after the grace period ends.</p>
                                                        <label for="grace_days_{{ $release->id }}" class="form-label">Grace period (days)</label>
                                                        <input id="grace_days_{{ $release->id }}" type="number" name="grace_days" value="7" min="0" max="90" class="form-control">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Mark Required</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="forceMarkModal{{ $release->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('central.versions.force-mark-all', ['release' => $release], false) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Force mark all tenants</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning mb-3">
                                                            This only changes rollout state. It does not run the actual tenant update pipeline.
                                                        </div>
                                                        <p class="mb-3">Use this only when the tenant versions are already correct and the tracking data needs to be repaired.</p>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="confirm{{ $release->id }}" name="confirm" value="1" required>
                                                            <label class="form-check-label" for="confirm{{ $release->id }}">
                                                                I understand this is a state-only correction
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
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
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No releases found yet. Sync GitHub releases to build the version registry.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($releases->hasPages())
                <div>
                    {{ $releases->links() }}
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-2xl border border-[#273142] bg-[#161b22] p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Audit Trail</p>
                <h3 class="mt-2 text-2xl font-semibold text-white">Update History</h3>
                <p class="mt-2 text-sm text-slate-400">
                    {{ $historyCount > 0 ? $historyCount.' update attempt'.($historyCount === 1 ? '' : 's').' recorded.' : 'No updates applied yet.' }}
                </p>
            </div>

            <button
                type="button"
                x-on:click="historyOpen = !historyOpen"
                class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-[#172033] hover:text-white"
            >
                <span x-text="historyOpen ? 'Hide Update History' : 'Show Update History'"></span>
                <svg class="h-4 w-4 transition-transform duration-200" x-bind:class="historyOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>
        </div>

        <div x-cloak x-show="historyOpen" x-transition.opacity.duration.200ms class="mt-6">
            @if($updateHistory === [])
                <p class="text-sm text-slate-500">No updates applied yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm text-slate-300">
                        <thead>
                            <tr class="border-b border-[#273142] text-xs uppercase tracking-[0.16em] text-slate-500">
                                <th class="px-4 py-3">Version</th>
                                <th class="px-4 py-3">Applied At</th>
                                <th class="px-4 py-3">Applied By</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($updateHistory as $entry)
                                <tr class="border-b border-[#273142]/80 transition hover:bg-white/[0.02]">
                                    <td class="px-4 py-4 font-semibold text-white">{{ $entry['version'] ?? 'Unknown' }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ $entry['applied_at'] ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ $entry['applied_by'] ?? '-' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] {{ ($entry['status'] ?? '') === 'success' ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border border-red-500/30 bg-red-500/10 text-red-300' }}">
                                            {{ $entry['status'] ?? 'unknown' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function versionsPage() {
        return {
            checking: false,
            registryOpen: false,
            historyOpen: false,
            async checkForUpdates() {
                if (this.checking) {
                    return;
                }

                this.checking = true;

                try {
                    const response = await fetch('{{ route('central.versions.check', [], false) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    if (! response.ok) {
                        throw new Error('Failed to refresh release info.');
                    }

                    window.location.reload();
                } catch (error) {
                    this.checking = false;
                    alert(error.message || 'Unable to check for updates right now.');
                }
            },
        };
    }
</script>
@endpush
