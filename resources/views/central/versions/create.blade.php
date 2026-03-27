@extends('layouts.central')

@section('title', 'Add App Version')

@section('content')
<div class="mb-8">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Add App Version</h2>
    <p class="mt-1 text-sm text-slate-400">Publish a new release note entry for tenant update notifications and changelog tracking.</p>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
    <form method="POST" action="{{ route('central.versions.store', absolute: false) }}">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="version_number" class="mb-2 block text-sm font-medium text-slate-200">Version Number</label>
                <input type="text" id="version_number" name="version_number" value="{{ old('version_number') }}" placeholder="1.2.0" required
                    class="block w-full rounded-xl border @error('version_number') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">Use semantic version style like 1.0.0 or 1.2.0.</p>
                @error('version_number') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="title" class="mb-2 block text-sm font-medium text-slate-200">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="January 2026 Update" required
                    class="block w-full rounded-xl border @error('title') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('title') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="released_at" class="mb-2 block text-sm font-medium text-slate-200">Released At</label>
                <input type="date" id="released_at" name="released_at" value="{{ old('released_at') }}"
                    class="block w-full rounded-xl border @error('released_at') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    style="color-scheme: dark;">
                @error('released_at') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-white/[0.07] bg-[#0f1319] p-4">
                <label for="is_active" class="flex items-start gap-3">
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active'))
                        class="mt-1 h-4 w-4 rounded border-white/10 bg-white/[0.03] text-emerald-500 focus:ring-emerald-500/40">
                    <span>
                        <span class="block text-sm font-semibold text-white">Set as Active</span>
                        <span class="mt-1 block text-sm text-slate-500">The active version is the one shown to tenants as the latest available update.</span>
                    </span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label for="changelog" class="mb-2 block text-sm font-medium text-slate-200">Changelog</label>
                <textarea id="changelog" name="changelog" rows="8" required placeholder="Add one change per line"
                    class="block w-full rounded-xl border @error('changelog') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('changelog') }}</textarea>
                <p class="mt-1.5 text-xs text-slate-500">Each line becomes a changelog item in the tenant update notification and settings page.</p>
                @error('changelog') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Save Version
            </button>
            <a href="{{ route('central.versions.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
