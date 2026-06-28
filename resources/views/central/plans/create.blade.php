@extends('layouts.central')

@section('title', 'Create Subscription Plan')

@section('content')
<div class="mb-8">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Create Subscription Plan</h2>
    <p class="mt-1 text-sm text-slate-400">Create a new subscription tier for central tenant billing.</p>
</div>

<?php
$availableFeatures = \App\Models\Plan::getAvailableFeatures() + [
    'overdue_loan_management' => ['name' => 'Overdue Loan Management', 'desc' => 'Send reminders, apply penalties, restructure, and write off overdue loans.', 'requires' => 'loan_management'],
];
if (isset($availableFeatures['overdue_loan_management']) && ! array_key_exists('requires', $availableFeatures['overdue_loan_management'])) {
    $availableFeatures['overdue_loan_management']['requires'] = 'loan_management';
}
?>

<form method="POST" action="{{ route('central.plans.store', absolute: false) }}" x-data="{
    name: '{{ old('name') }}',
    slug: '{{ old('slug') }}',
    updateSlug() {
        this.slug = this.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}">
    @csrf

    <!-- SECTION 1: Plan Details -->
    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-6 mb-6">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-white">Plan Details</h3>
        </div>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mb-6">
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Plan Name <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" x-model="name" @input="updateSlug" placeholder="e.g. Basic, Standard, Premium" required
                    class="block w-full rounded-xl border @error('name') border-red-500/50 @else border-[#21262d] @enderror bg-[#0d1117] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/20">
                @error('name') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="slug" class="mb-2 block text-sm font-medium text-slate-200">Slug</label>
                <input type="text" id="slug" name="slug" x-model="slug" readonly
                    class="block w-full rounded-xl border border-[#21262d] bg-[#0d1117]/50 px-4 py-3 text-sm text-slate-400 focus:outline-none cursor-not-allowed">
                <p class="mt-1.5 text-xs text-slate-500">URL-friendly identifier</p>
            </div>
        </div>

        <div>
            <label for="description" class="mb-2 block text-sm font-medium text-slate-200">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Brief description of this plan..."
                class="block w-full rounded-xl border @error('description') border-red-500/50 @else border-[#21262d] @enderror bg-[#0d1117] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/20">{{ old('description', \App\Models\Plan::defaultDescription()) }}</textarea>
            @error('description') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    <!-- SECTION 2: Pricing -->
    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-6 mb-6">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-white">Pricing</h3>
        </div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="price" class="mb-2 block text-sm font-medium text-slate-200">Price (&#8369;) <span class="text-red-400">*</span></label>
                <input type="number" step="0.01" id="price" name="price" value="{{ old('price', '0') }}" required
                    class="block w-full rounded-xl border @error('price') border-red-500/50 @else border-[#21262d] @enderror bg-[#0d1117] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">Set to 0 for a free plan.</p>
                @error('price') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="billing_cycle" class="mb-2 block text-sm font-medium text-slate-200">Billing Cycle</label>
                <select id="billing_cycle" name="billing_cycle"
                    class="block w-full rounded-xl border border-[#21262d] bg-[#0d1117] px-4 py-3 text-sm text-slate-100 transition focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/20">
                    <option value="monthly" class="bg-[#0d1117] text-white">Monthly</option>
                    <option value="yearly" class="bg-[#0d1117] text-white">Yearly</option>
                </select>
            </div>
        </div>
    </div>

    <!-- SECTION 3: Usage Limits -->
    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-6 mb-6">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-white">Limits</h3>
        </div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
                <label for="max_branches" class="mb-2 block text-sm font-medium text-slate-200">Max Branches <span class="text-red-400">*</span></label>
                <input type="number" id="max_branches" name="max_branches" value="{{ old('max_branches', 0) }}" required placeholder="0"
                    class="block w-full rounded-xl border @error('max_branches') border-red-500/50 @else border-[#21262d] @enderror bg-[#0d1117] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">Enter 0 for unlimited</p>
                @error('max_branches') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="max_users" class="mb-2 block text-sm font-medium text-slate-200">Max Users <span class="text-red-400">*</span></label>
                <input type="number" id="max_users" name="max_users" value="{{ old('max_users', 0) }}" required placeholder="0"
                    class="block w-full rounded-xl border @error('max_users') border-red-500/50 @else border-[#21262d] @enderror bg-[#0d1117] px-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/20">
                <p class="mt-1.5 text-xs text-slate-500">Enter 0 for unlimited</p>
                @error('max_users') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="max_members" class="mb-2 block text-sm font-medium text-slate-200">Max Members</label>
                <input type="number" id="max_members" name="max_members" value="0" placeholder="0" disabled
                    class="block w-full rounded-xl border border-[#21262d] bg-[#0d1117]/50 px-4 py-3 text-sm text-slate-400 focus:outline-none cursor-not-allowed">
                <p class="mt-1.5 text-xs text-slate-500">Enter 0 for unlimited</p>
            </div>
        </div>
    </div>

    <!-- SECTION 4: Included Features -->
    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-6 mb-6" 
         x-data="{ 
             selected: {{ json_encode(old('features', [])) }},
             toggle(key) {
                 if (this.selected.includes(key)) {
                     this.selected = this.selected.filter(f => f !== key);
                 } else {
                     this.selected.push(key);
                 }
             },
             isSelected(key) {
                 return this.selected.includes(key);
             }
         }">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-white">Included Features</h3>
            <p class="mt-1 text-xs text-slate-400">Select which features are included in this plan.</p>
        </div>
        
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach($availableFeatures as $key => $feature)
            <div @click="toggle('{{ $key }}')"
                 class="relative cursor-pointer transition-colors duration-200 ease-in-out"
                 :class="isSelected('{{ $key }}') ? 
                     'bg-green-500/10 border border-green-500 rounded-xl p-4' : 
                     'bg-[#0f1319] border border-[#21262d] hover:border-[#3f3f46] rounded-xl p-4'">
                
                <input type="checkbox" name="features[]" value="{{ $key }}" class="hidden"
                       :checked="isSelected('{{ $key }}')" x-effect="$el.checked = isSelected('{{ $key }}')">
                
                <div class="flex items-start">
                    <div class="flex-1">
                        <label class="font-medium text-white cursor-pointer">{{ $feature['name'] }}</label>
                        <p class="text-xs text-[#8b949e] mt-1">{{ $feature['desc'] }}</p>
                        @if($feature['requires'])
                        <p class="text-xs text-orange-400 mt-1">Requires: {{ $availableFeatures[$feature['requires']]['name'] }}</p>
                        @endif
                    </div>
                    <div class="ml-3 flex h-6 items-center">
                        <div class="h-4 w-4 rounded border flex items-center justify-center transition-colors"
                             :class="isSelected('{{ $key }}') ? 'bg-green-500 border-green-500' : 'border-[#3f3f46] bg-[#161b22]'">
                            <svg x-show="isSelected('{{ $key }}')" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Form Actions -->
    <div class="mt-8 mb-16 flex items-center justify-end gap-4">
        <a href="{{ route('central.plans.index', absolute: false) }}" class="text-sm font-medium text-slate-400 transition hover:text-white">
            Cancel
        </a>
        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#22c55e] px-6 py-3 text-sm font-bold tracking-wide text-white shadow-lg shadow-green-500/20 transition hover:brightness-110">
            Save Plan
        </button>
    </div>

</form>
@endsection
