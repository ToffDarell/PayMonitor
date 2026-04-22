@extends('layouts.central')
@section('title', 'Versions')

@php
    $publishedLabel = filled($updateInfo['published_at'] ?? null)
        ? \Illuminate\Support\Carbon::parse((string) $updateInfo['published_at'])->format('M d, Y h:i A')
        : 'Unknown';
    $releaseCount   = method_exists($releases, 'total') ? $releases->total() : $releases->count();
    $latestTracked  = $statistics['latest_release'] ?? ($releases->first()?->tag ?? 'None');
    $rolloutState   = (string) ($statistics['rollout_state'] ?? 'healthy');
    $rolloutBadge   = match ($rolloutState) {
        'tracking_incomplete' => 'border border-amber-500/30 bg-amber-500/10 text-amber-300',
        'needs_attention'     => 'border border-yellow-500/30 bg-yellow-500/10 text-yellow-300',
        default               => 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    };
    $rolloutLabel   = match ($rolloutState) {
        'tracking_incomplete' => 'Tracking Incomplete',
        'needs_attention'     => 'Needs Attention',
        default               => 'Healthy',
    };

    $deployCommands = [
        'git pull origin main',
        'composer install --no-dev --optimize-autoloader',
        'php artisan optimize:clear',
        'php artisan tenancy:migrate --force',
    ];

    $totalTenants   = $tenants->count();
    $upToDate       = $tenants->filter(fn($t) => version_compare(ltrim((string)($t->current_version ?? 'v1.0.0'),'v'), ltrim($latestVersion,'v'), '>='))->count();
    $outdated       = $totalTenants - $upToDate;
@endphp

@section('content')
<div x-data="versionsPage()" class="space-y-8">

    {{-- PAGE HEADER --}}
    <section class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Central Management</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-white">Versions</h2>
            <p class="mt-2 text-sm text-slate-400">Manage releases, tenant rollout, and update notifications.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('central.versions.sync', [], false) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.867-5.303M19.5 12a7.5 7.5 0 0 1-12.867 5.303M19.5 4.5v3.75h-3.75M4.5 19.5v-3.75h3.75"/></svg>
                    Sync Releases
                </button>
            </form>
            <button type="button" x-on:click="checkForUpdates" x-bind:disabled="checking"
                class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:text-white disabled:opacity-60">
                <svg x-show="!checking" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.867-5.303M19.5 12a7.5 7.5 0 0 1-12.867 5.303M19.5 4.5v3.75h-3.75M4.5 19.5v-3.75h3.75"/></svg>
                <svg x-show="checking" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3"/></svg>
                <span x-text="checking ? 'Checking...' : 'Check for Updates'"></span>
            </button>
        </div>
    </section>

    {{-- VERSION STATUS CARD --}}
    <section class="rounded-2xl border border-[#273142] bg-[#161b22] p-6 shadow-sm">
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
        @if(($updateInfo['update_available'] ?? false) && filled($updateInfo['release_url'] ?? null))
        <div class="mt-4 flex items-center gap-3">
            <a href="{{ $updateInfo['release_url'] }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500 hover:text-white transition">
                View Release on GitHub
            </a>
        </div>
        @endif
    </section>

    {{-- DEPLOYMENT INSTRUCTIONS CARD --}}
    <section class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
        <div class="mb-4">
            <p class="text-white font-semibold text-sm">How to Deploy Updates to Server</p>
            <p class="text-[#8b949e] text-xs mt-1">Run these commands in your server terminal to deploy new code.</p>
        </div>
        <div class="space-y-2">
            @foreach($deployCommands as $cmd)
            <div x-data="{ copied: false }" class="flex items-center justify-between bg-[#0d1117] border border-[#21262d] rounded-lg px-4 py-2.5">
                <code class="font-mono text-sm text-green-400">{{ $cmd }}</code>
                <button type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $cmd }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="ml-4 flex-shrink-0 text-xs text-[#8b949e] hover:text-white transition">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" class="text-green-400">Copied!</span>
                </button>
            </div>
            @endforeach
        </div>
        <p class="text-[#8b949e] text-xs mt-3">After deploying, tenants can update their own portal version from their Settings → Updates page.</p>
    </section>

    {{-- TENANT VERSION TABLE --}}
    <section x-data="{ filter: 'all' }">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <p class="text-white font-semibold text-sm">Tenant Version Status</p>
                <div class="flex gap-3 mt-2">
                    <span class="text-xs text-[#8b949e] bg-[#0f1319] border border-[#21262d] rounded-full px-3 py-1">Total: <strong class="text-white">{{ $totalTenants }}</strong></span>
                    <span class="text-xs text-green-400 bg-green-500/10 border border-green-500/20 rounded-full px-3 py-1">Up to Date: <strong>{{ $upToDate }}</strong></span>
                    <span class="text-xs text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 rounded-full px-3 py-1">Outdated: <strong>{{ $outdated }}</strong></span>
                </div>
            </div>
            <div class="flex gap-2">
                @foreach(['all' => 'All', 'uptodate' => 'Up to Date', 'outdated' => 'Outdated'] as $key => $label)
                <button type="button"
                    x-on:click="filter = '{{ $key }}'"
                    :class="filter === '{{ $key }}' ? 'bg-[#21262d] text-white border-[#30363d]' : 'text-[#8b949e] border-[#21262d] hover:text-white'"
                    class="text-xs px-3 py-1.5 rounded-lg border transition">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>

        <div class="bg-[#161b22] border border-[#21262d] rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead>
                        <tr class="border-b border-[#21262d] text-xs uppercase tracking-[0.14em] text-slate-500">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Cooperative</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Version</th>
                            <th class="px-4 py-3">Latest</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last Updated</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $i => $tenant)
                        @php
                            $tv = (string) ($tenant->current_version ?? 'v1.0.0');
                            $isUpToDate = version_compare(ltrim($tv,'v'), ltrim($latestVersion,'v'), '>=');
                            $rowFilter  = $isUpToDate ? 'uptodate' : 'outdated';
                            $domain     = $tenant->domains->first()?->domain ?? '';
                        @endphp
                        <tr class="border-b border-[#21262d]/60 hover:bg-white/[0.02] transition align-middle"
                            x-show="filter === 'all' || filter === '{{ $rowFilter }}'">
                            <td class="px-4 py-3 text-[#8b949e] text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-white text-sm">{{ $tenant->name }}</p>
                                @if($domain)
                                <p class="text-xs text-[#8b949e] mt-0.5">{{ $domain }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-[#0f1319] border border-[#21262d] px-2.5 py-0.5 text-xs text-slate-300">
                                    {{ $tenant->plan?->name ?? 'No Plan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <code class="font-mono text-xs text-slate-200 bg-[#0d1117] border border-[#21262d] rounded px-2 py-0.5">{{ $tv }}</code>
                            </td>
                            <td class="px-4 py-3">
                                <code class="font-mono text-xs text-slate-400 bg-[#0d1117] border border-[#21262d] rounded px-2 py-0.5">{{ $latestVersion }}</code>
                            </td>
                            <td class="px-4 py-3">
                                @if($isUpToDate)
                                <span class="inline-flex rounded-full bg-green-500/10 text-green-400 border border-green-500/20 px-2.5 py-0.5 text-xs font-semibold">Up to Date</span>
                                @else
                                <span class="inline-flex rounded-full bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 px-2.5 py-0.5 text-xs font-semibold">Outdated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-[#8b949e]">
                                @if($tenant->last_updated_at)
                                    {{ \Illuminate\Support\Carbon::parse((string) $tenant->last_updated_at)->format('M d, Y') }}
                                @else
                                    <span class="italic">Never updated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!$isUpToDate)
                                    <form method="POST" action="{{ route('central.versions.notify', ['tenant' => $tenant->id], false) }}">
                                        @csrf
                                        <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition">
                                            Notify
                                        </button>
                                    </form>
                                    @endif
                                    <form method="POST" action="{{ route('central.versions.toggle-required', ['tenant' => $tenant->id], false) }}">
                                        @csrf
                                        @if($tenant->update_required)
                                        <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-yellow-500/10 text-yellow-400 border border-yellow-500/30 transition">
                                            Required ✓
                                        </button>
                                        @else
                                        <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-[#21262d] text-[#8b949e] hover:border-yellow-500 hover:text-yellow-400 transition">
                                            Set Required
                                        </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">No tenants found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- TRACKED RELEASES --}}
    <section class="rounded-2xl border border-[#273142] bg-[#161b22] p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Release Registry</p>
                <h3 class="mt-2 text-2xl font-semibold text-white">Tracked Versions</h3>
            </div>
            <button type="button" x-on:click="registryOpen = !registryOpen"
                class="inline-flex items-center gap-2 rounded-xl border border-[#2a3340] bg-[#111827] px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500 hover:text-white transition">
                <span x-text="registryOpen ? 'Hide' : 'Show Tracked Versions'"></span>
                <svg class="h-4 w-4 transition-transform duration-200" x-bind:class="registryOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </button>
        </div>

        <div x-cloak x-show="registryOpen" x-transition.opacity.duration.200ms class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm text-slate-300">
                <thead>
                    <tr class="border-b border-[#273142] text-xs uppercase tracking-[0.16em] text-slate-500">
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3">Details</th>
                        <th class="px-4 py-3">Published</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($releases as $release)
                    <tr class="border-b border-[#273142]/80 hover:bg-white/[0.02] transition">
                        <td class="px-4 py-4">
                            <p class="font-semibold text-white">{{ $release->tag }}</p>
                            @if(filled($release->release_url))
                            <a href="{{ $release->release_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-emerald-400 hover:text-emerald-300 mt-1 inline-flex">View release</a>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-white">{{ $release->title }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ \Illuminate\Support\Str::limit(strip_tags((string) $release->changelog), 100) }}</p>
                        </td>
                        <td class="px-4 py-4 text-slate-400 text-xs">{{ $release->published_at?->format('M d, Y') ?? 'Unknown' }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-1">
                                @if($release->is_required)<span class="inline-flex rounded-full border border-red-500/20 bg-red-500/10 px-2 py-0.5 text-xs text-red-300">Required</span>@endif
                                @if($release->is_stable)<span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-300">Stable</span>@else<span class="inline-flex rounded-full border border-yellow-500/20 bg-yellow-500/10 px-2 py-0.5 text-xs text-yellow-300">Pre-release</span>@endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('central.versions.notify-all', ['release' => $release], false) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-[#2a3340] bg-[#111827] text-slate-200 hover:border-slate-500 hover:text-white transition">Notify All</button>
                                </form>
                                @if(!$release->is_required)
                                <button type="button" class="text-xs px-3 py-1.5 rounded-lg border border-red-500/30 text-red-300 hover:bg-red-500/5 transition" data-bs-toggle="modal" data-bs-target="#markRequiredModal{{ $release->id }}">Mark Required</button>
                                @else
                                <form method="POST" action="{{ route('central.versions.unmark-required', ['release' => $release], false) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-[#2a3340] bg-[#111827] text-slate-200 hover:border-slate-500 hover:text-white transition">Clear Required</button>
                                </form>
                                @endif
                            </div>
                            {{-- Mark Required Modal --}}
                            <div class="modal fade" id="markRequiredModal{{ $release->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('central.versions.mark-required', ['release' => $release], false) }}">
                                        @csrf
                                        <div class="modal-header"><h5 class="modal-title">Mark {{ $release->tag }} as required</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <p class="mb-3">Tenants behind this version will be blocked after the grace period ends.</p>
                                            <label class="form-label">Grace period (days)</label>
                                            <input type="number" name="grace_days" value="7" min="0" max="90" class="form-control">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Mark Required</button>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No releases found. Sync GitHub to populate.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($releases->hasPages())<div class="mt-4">{{ $releases->links() }}</div>@endif
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
            async checkForUpdates() {
                if (this.checking) return;
                this.checking = true;
                try {
                    const res = await fetch('{{ route('central.versions.check', [], false) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    });
                    if (!res.ok) throw new Error('Failed to check.');
                    window.location.reload();
                } catch (e) {
                    this.checking = false;
                    alert(e.message || 'Unable to check for updates.');
                }
            },
        };
    }
</script>
@endpush
