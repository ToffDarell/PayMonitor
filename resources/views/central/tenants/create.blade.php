@extends('layouts.central')

@section('title', 'Add New Tenant')

@section('content')
<?php $tenantBaseDomain = config('tenancy.tenant_base_domain', 'localhost'); ?>
<div class="mb-8">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Add New Tenant</h2>
    <p class="mt-1 text-sm text-slate-400">Create a new lending cooperative tenant and send its admin credentials.</p>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
    <form method="POST" action="{{ route('central.tenants.store', absolute: false) }}">
        @csrf
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

            {{-- Cooperative Name --}}
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Business / Cooperative Name <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="block w-full rounded-xl border @error('name') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('name') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Address --}}
            <div>
                <label for="address" class="mb-2 block text-sm font-medium text-slate-200">Address <span class="text-red-400">*</span></label>
                <input type="text" id="address" name="address" value="{{ old('address') }}" required
                    class="block w-full rounded-xl border @error('address') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('address') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Subdomain --}}
            <div>
                <label for="domain" class="mb-2 block text-sm font-medium text-slate-200">Subdomain <span class="text-red-400">*</span></label>
                <input type="text" id="domain" name="domain" value="{{ old('domain') }}" required
                    class="block w-full rounded-xl border @error('domain') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">e.g. <span class="font-mono text-slate-400">alpha</span> → alpha.{{ $tenantBaseDomain }}</p>
                @error('domain') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Plan --}}
            <div>
                <label for="plan_id" class="mb-2 block text-sm font-medium text-slate-200">Plan <span class="text-red-400">*</span></label>
                <select id="plan_id" name="plan_id" required
                    class="block w-full rounded-xl border @error('plan_id') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="" class="bg-[#0F1729]">Select a plan</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" class="bg-[#0F1729]" @selected(old('plan_id') == $plan->id)>
                            {{ $plan->name }} — &#8369;{{ number_format((float) $plan->price, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('plan_id') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Admin Name --}}
            <div>
                <label for="admin_name" class="mb-2 block text-sm font-medium text-slate-200">Admin Name <span class="text-red-400">*</span></label>
                <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required
                    class="block w-full rounded-xl border @error('admin_name') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('admin_name') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Admin Email --}}
            <div>
                <label for="admin_email" class="mb-2 block text-sm font-medium text-slate-200">Admin Email <span class="text-red-400">*</span></label>
                <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required
                    class="block w-full rounded-xl border @error('admin_email') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('admin_email') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Due Date --}}
            <div>
                <label for="subscription_due_at" class="mb-2 block text-sm font-medium text-slate-200">Subscription Due Date <span class="text-red-400">*</span></label>
                <input type="date" id="subscription_due_at" name="subscription_due_at" value="{{ old('subscription_due_at') }}" required
                    class="block w-full rounded-xl border @error('subscription_due_at') border-red-500/50 @else border-white/10 @enderror bg-white/[0.03] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    style="color-scheme: dark;">
                @error('subscription_due_at') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                Create Tenant &amp; Send Credentials
            </button>
            <a href="{{ route('central.tenants.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
