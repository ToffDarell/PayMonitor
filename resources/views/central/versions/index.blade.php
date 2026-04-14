@extends('layouts.central')

@section('title', 'System Updates')

@php
    $publishedLabel = filled($updateInfo['published_at'] ?? null)
        ? \Illuminate\Support\Carbon::parse((string) $updateInfo['published_at'])->format('M d, Y h:i A')
        : 'Unknown';
@endphp

@section('content')
<div x-data="systemUpdates()" class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-heading text-2xl font-bold tracking-tight text-white">System Updates</h2>
            <p class="mt-1 text-sm text-slate-400">Manage PayMonitor version and updates</p>
        </div>
        <button
            type="button"
            x-on:click="checkForUpdates"
            x-bind:disabled="checking"
            class="inline-flex items-center gap-2 rounded-xl border border-[#21262d] px-4 py-2.5 text-sm font-semibold text-[#8b949e] transition hover:border-[#30363d] hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
        >
            <svg x-show="!checking" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.867-5.303M19.5 12a7.5 7.5 0 0 1-12.867 5.303M19.5 4.5v3.75h-3.75M4.5 19.5v-3.75h3.75"/></svg>
            <svg x-show="checking" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m0-12.728 2.121 2.121m8.486 8.486 2.121 2.121"/></svg>
            <span x-text="checking ? 'Checking...' : 'Check for Updates'"></span>
        </button>
    </div>

    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#8b949e]">Current Version</p>
                <p class="mt-2 text-2xl font-bold text-white">{{ $updateInfo['current_version'] }}</p>
                <span class="mt-3 inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-300">Installed</span>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#8b949e]">Latest Version</p>
                <p class="mt-2 text-2xl font-bold {{ $updateInfo['update_available'] ? 'text-yellow-300' : 'text-emerald-300' }}">{{ $updateInfo['latest_version'] }}</p>
                @if($updateInfo['update_available'])
                    <span class="mt-3 inline-flex rounded-full border border-yellow-500/30 bg-yellow-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-yellow-300">Update Available</span>
                @else
                    <span class="mt-3 inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-300">Up to Date</span>
                @endif
            </div>
        </div>
    </div>

    @if($updateInfo['update_available'])
        <div class="rounded-xl border border-yellow-500/20 bg-yellow-500/5 p-5">
            <p class="text-sm font-semibold text-yellow-400">New Update Available: {{ $updateInfo['latest_version'] }}</p>
            <p class="mt-1 text-sm text-slate-200">{{ $updateInfo['release_name'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Published: {{ $publishedLabel }}</p>

            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Changelog</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-300">
                    @forelse($changelogItems as $item)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-yellow-300"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No changelog details available.</li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                @if(filled($updateInfo['release_url'] ?? null))
                    <a href="{{ $updateInfo['release_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-[#21262d] px-4 py-2 text-sm font-medium text-[#8b949e] transition hover:border-[#30363d] hover:text-white">
                        View on GitHub
                    </a>
                @endif

                <form method="POST" action="{{ route('central.versions.apply', absolute: false) }}" onsubmit="return confirm('Apply update to {{ $updateInfo['latest_version'] }}? This will run git pull and clear cache.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl bg-green-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-400">
                        Apply Update
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-green-500/20 bg-green-500/5 p-4">
            <p class="text-sm font-semibold text-green-400">PayMonitor is up to date</p>
        </div>
    @endif

    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Update History</h3>
        </div>

        @if($updateHistory === [])
            <p class="text-sm text-slate-500">No updates applied yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[580px] text-left text-sm text-slate-300">
                    <thead>
                        <tr class="border-b border-[#21262d] text-xs uppercase tracking-[0.14em] text-slate-500">
                            <th class="px-3 py-2">Version</th>
                            <th class="px-3 py-2">Applied At</th>
                            <th class="px-3 py-2">Applied By</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($updateHistory as $entry)
                            <tr class="border-b border-[#21262d]/70">
                                <td class="px-3 py-2 font-semibold text-white">{{ $entry['version'] ?? 'Unknown' }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $entry['applied_at'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $entry['applied_by'] ?? '-' }}</td>
                                <td class="px-3 py-2">
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

    <div class="mt-4 rounded-xl border border-[#21262d] bg-[#161b22] p-5">
        <h3 class="text-sm font-semibold text-white">How to publish a new update</h3>
        <div class="mt-4 space-y-3 text-sm text-slate-300">
            <p>1. Make your code changes and commit</p>
            <pre class="overflow-x-auto rounded-lg border border-[#21262d] bg-[#0f1319] px-3 py-2 text-xs text-slate-300">git add . && git commit -m "Your changes"</pre>

            <p>2. Push to GitHub</p>
            <pre class="overflow-x-auto rounded-lg border border-[#21262d] bg-[#0f1319] px-3 py-2 text-xs text-slate-300">git push origin main</pre>

            <p>3. Create a new tag</p>
            <pre class="overflow-x-auto rounded-lg border border-[#21262d] bg-[#0f1319] px-3 py-2 text-xs text-slate-300">git tag v1.2.0
git push origin v1.2.0</pre>

            <p>4. Go to GitHub Releases and publish the release</p>
            <pre class="overflow-x-auto rounded-lg border border-[#21262d] bg-[#0f1319] px-3 py-2 text-xs text-slate-300">GitHub > Releases > Draft new release
Select your tag
Add release title and changelog
Click Publish release</pre>

            <p>5. Come back here and click Apply Update</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function systemUpdates() {
        return {
            checking: false,
            async checkForUpdates() {
                if (this.checking) {
                    return;
                }

                this.checking = true;

                try {
                    const response = await fetch('{{ route('central.versions.check', absolute: false) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
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
