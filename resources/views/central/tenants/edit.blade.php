@extends('layouts.central')

@section('title', 'Edit Tenant')

@section('content')
<?php
    $domainRecord     = $tenant->domains()->value('domain');
    $subdomain        = $domainRecord ? explode('.', $domainRecord)[0] : $tenant->id;
    $tenantBaseDomain = config('tenancy.tenant_base_domain', 'localhost');
?>
<div class="mb-8">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Edit Tenant</h2>
    <p class="mt-1 text-sm text-slate-400">Update the cooperative subscription and account details.</p>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
    <form method="POST" action="{{ route('central.tenants.update', $tenant, false) }}">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

            {{-- Cooperative Name --}}
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Business / Cooperative Name <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $tenant->name) }}" required
                    class="block w-full rounded-xl border @error('name') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('name') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Address --}}
            <div>
                <label for="address" class="mb-2 block text-sm font-medium text-slate-200">Address <span class="text-red-400">*</span></label>
                <input type="text" id="address" name="address" value="{{ old('address', $tenant->address) }}" required
                    class="block w-full rounded-xl border @error('address') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('address') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Subdomain (read-only) --}}
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-400">Subdomain (read-only)</label>
                <div class="flex w-full items-center rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 text-sm text-slate-500 font-mono">
                    {{ $subdomain }}.{{ $tenantBaseDomain }}
                </div>
            </div>

            {{-- Plan --}}
            <div>
                <label for="plan_id" class="mb-2 block text-sm font-medium text-slate-200">Plan <span class="text-red-400">*</span></label>
                <select id="plan_id" name="plan_id" required
                    class="block w-full rounded-xl border @error('plan_id') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" class="bg-[#0F1729]" @selected(old('plan_id', $tenant->plan_id) == $plan->id)>
                            {{ $plan->name }} — &#8369;{{ number_format((float) $plan->price, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('plan_id') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Admin Name (read-only) --}}
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-400">Admin Name (read-only)</label>
                <div class="flex w-full items-center rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 text-sm text-slate-500">
                    {{ $tenant->admin_name ?: '—' }}
                </div>
            </div>

            {{-- Admin Email (read-only) --}}
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-400">Admin Email (read-only)</label>
                <div class="flex w-full items-center rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 text-sm text-slate-500">
                    {{ $tenant->email }}
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-slate-200">Status</label>
                <select id="status" name="status"
                    class="block w-full rounded-xl border @error('status') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @foreach(['active', 'overdue', 'suspended', 'inactive'] as $s)
                        <option value="{{ $s }}" class="bg-[#0F1729]" @selected(old('status', $tenant->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Subscription Due Date --}}
            <div>
                <label for="subscription_due_at" class="mb-2 block text-sm font-medium text-slate-200">Subscription Due Date</label>
                <input type="date" id="subscription_due_at" name="subscription_due_at"
                    value="{{ old('subscription_due_at', $tenant->subscription_due_at?->toDateString()) }}"
                    class="block w-full rounded-xl border @error('subscription_due_at') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    style="color-scheme: dark;">
                @error('subscription_due_at') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Update Tenant
            </button>
            <a href="{{ route('central.tenants.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
