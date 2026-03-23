@extends('layouts.central')

@section('title', 'Add Plan')

@section('content')
<div class="mb-8">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Add Plan</h2>
    <p class="mt-1 text-sm text-slate-400">Create a new subscription tier for central tenant billing.</p>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
    <form method="POST" action="{{ route('central.plans.store', absolute: false) }}">
        @csrf
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="block w-full rounded-xl border @error('name') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('name') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="price" class="mb-2 block text-sm font-medium text-slate-200">Price (&#8369;)</label>
                <input type="number" step="0.01" id="price" name="price" value="{{ old('price') }}" required
                    class="block w-full rounded-xl border @error('price') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('price') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="max_branches" class="mb-2 block text-sm font-medium text-slate-200">Max Branches</label>
                <input type="number" id="max_branches" name="max_branches" value="{{ old('max_branches', 0) }}" required
                    class="block w-full rounded-xl border @error('max_branches') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">Enter 0 for unlimited.</p>
                @error('max_branches') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="max_users" class="mb-2 block text-sm font-medium text-slate-200">Max Users</label>
                <input type="number" id="max_users" name="max_users" value="{{ old('max_users', 0) }}" required
                    class="block w-full rounded-xl border @error('max_users') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">Enter 0 for unlimited.</p>
                @error('max_users') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="mb-2 block text-sm font-medium text-slate-200">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="block w-full rounded-xl border @error('description') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('description') }}</textarea>
                @error('description') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Save Plan
            </button>
            <a href="{{ route('central.plans.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
