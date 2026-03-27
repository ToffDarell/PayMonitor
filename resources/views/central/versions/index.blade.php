@extends('layouts.central')

@section('title', 'App Versions')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">App Versions</h2>
        <p class="mt-1 text-sm text-slate-400">Publish release notes and track which tenants have acknowledged the latest version.</p>
    </div>
    <a href="{{ route('central.versions.create', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Version
    </a>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Published Versions</p>
        <p class="mt-3 font-heading text-3xl font-bold text-white">{{ number_format($versions->count()) }}</p>
        <p class="mt-2 text-sm text-slate-400">Total recorded releases in the central app.</p>
    </div>
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Latest Active</p>
        <p class="mt-3 font-heading text-3xl font-bold text-white">{{ $latestActiveVersion?->version_number ? 'v'.$latestActiveVersion->version_number : 'None' }}</p>
        <p class="mt-2 text-sm text-slate-400">{{ $latestActiveVersion?->title ?? 'No active release has been published yet.' }}</p>
    </div>
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tenant Base</p>
        <p class="mt-3 font-heading text-3xl font-bold text-white">{{ number_format($totalTenants) }}</p>
        <p class="mt-2 text-sm text-slate-400">Acknowledgement counts below are compared against this tenant total.</p>
    </div>
</div>

<div class="space-y-4">
    @forelse($versions as $version)
        @php
            $pendingCount = max($totalTenants - $version->acknowledgements_count, 0);
        @endphp
        <div class="overflow-hidden rounded-2xl border border-white/[0.07] bg-white/[0.02]">
            <div class="flex flex-col gap-4 border-b border-white/[0.06] px-6 py-5 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="font-heading text-xl font-bold text-white">v{{ $version->version_number }}</h3>
                        @if($version->is_active)
                            <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-300">Active</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm font-medium text-slate-200">{{ $version->title }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Released {{ $version->released_at?->format('M d, Y') ?? 'Not scheduled' }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl border border-white/[0.06] bg-[#0f1319] px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Acknowledged</p>
                        <p class="mt-2 text-lg font-bold text-white">{{ number_format($version->acknowledgements_count) }}</p>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-[#0f1319] px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Pending</p>
                        <p class="mt-2 text-lg font-bold text-indigo-300">{{ number_format($pendingCount) }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5">
                <h4 class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Changelog</h4>
                <ul class="mt-4 space-y-2 text-sm text-slate-300">
                    @foreach($version->changelog_items as $change)
                        <li class="flex gap-3">
                            <span class="mt-1 h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <span>{{ $change }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-white/[0.08] bg-white/[0.02] px-6 py-12 text-center">
            <p class="text-lg font-semibold text-white">No app versions found.</p>
            <p class="mt-2 text-sm text-slate-500">Create your first version to start showing tenant update notifications.</p>
        </div>
    @endforelse
</div>
@endsection
