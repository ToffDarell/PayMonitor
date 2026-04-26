@extends('layouts.central')

@section('title', 'Plans')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Plans</h2>
        <p class="mt-1 text-sm text-slate-400">Manage subscription tiers for lending cooperative tenants.</p>
    </div>
    <a href="{{ route('central.plans.create', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#22c55e] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-green-500/20 transition hover:brightness-110">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Plan
    </a>
</div>

<?php
$featureNames = [
    'basic_members' => 'Member Management',
    'loan_management' => 'Loan Management',
    'loan_types' => 'Loan Types',
    'payment_tracking' => 'Payment Tracking',
    'basic_reports' => 'Basic Reports',
    'branch_management' => 'Branch Management',
    'multi_user' => 'Multi-User Access',
    'collections_dashboard' => 'Collections Dashboard',
    'advanced_reports' => 'Advanced Reports',
    'audit_logs' => 'Audit Logs',
    'member_documents' => 'Member Documents',
    'loan_documents' => 'Loan Documents',
    'custom_roles' => 'Custom Roles',
    'advanced_analytics' => 'Advanced Analytics',
];

// Determine the most popular plan by tenant count
$mostPopularPlanId = null;
if ($plans->count() > 0) {
    $mostPopularPlanId = $plans->sortByDesc('tenants_count')->first()->id;
}
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @forelse($plans as $plan)
    <?php $hasTenants = $plan->tenants_count > 0; ?>
    <div class="bg-[#161b22] border border-[#21262d] rounded-2xl p-6 flex flex-col relative">
        
        <!-- TOP SECTION -->
        <div class="mb-4">
            @if($plan->id === $mostPopularPlanId && $hasTenants)
            <div class="absolute top-6 right-6">
                <span class="inline-flex items-center rounded-full bg-green-500/10 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-green-400 border border-green-500/20">
                    Most Popular
                </span>
            </div>
            @endif
            
            <h3 class="text-xl font-bold text-white">{{ $plan->name }}</h3>
            <div class="mt-4 flex items-baseline gap-1">
                <span class="text-3xl font-black text-white">&#8369;{{ number_format((float) $plan->price, 0) }}</span>
                <span class="text-sm font-medium text-[#8b949e]">/monthly</span>
            </div>
            <p class="text-sm text-[#8b949e] mt-2 min-h-[40px]">{{ $plan->description }}</p>
        </div>

        <hr class="border-[#21262d] my-5">

        <!-- LIMITS SECTION -->
        <div class="space-y-3 mb-2">
            <div class="flex items-center gap-3 text-sm text-slate-300">
                <svg class="h-4 w-4 text-[#8b949e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125 1.125 1.125 3.375M10.5 10.5h3m-3 3h3m-3 3h3"/></svg>
                <span>{{ (int)$plan->max_branches === 0 ? 'Unlimited Branches' : 'Max ' . number_format($plan->max_branches) . ' Branches' }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-300">
                <svg class="h-4 w-4 text-[#8b949e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                <span>{{ (int)$plan->max_users === 0 ? 'Unlimited Users' : 'Max ' . number_format($plan->max_users) . ' Users' }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-300">
                <svg class="h-4 w-4 text-[#8b949e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632l-.04-.034m15.042-1.306a9.096 9.096 0 00-3.741-.479 3 3 0 00-4.682-2.72m.94 3.198a12.002 12.002 0 00-3.32-3.03 3 3 0 00-3.32 3.03m-9.04-1.306A9.096 9.096 0 014.5 18.72m3.741-.479A9.096 9.096 0 0112 18.72m-3.741-.479a3 3 0 00-4.682-2.72m.94 3.198A11.944 11.944 0 0112 21.75M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <span>Unlimited Members</span>
            </div>
        </div>

        <hr class="border-[#21262d] my-5">

        <!-- FEATURES SECTION -->
        <div class="flex-grow">
            <h4 class="text-xs uppercase text-[#8b949e] font-semibold mb-3">Features</h4>
            <div class="flex flex-wrap gap-2">
                @if(is_array($plan->features) && count($plan->features) > 0)
                    @foreach($plan->features as $featureKey)
                        @if(array_key_exists($featureKey, $featureNames))
                            <span class="inline-flex items-center rounded-full bg-[#1f2937] px-2.5 py-1 text-xs text-[#8b949e] border border-[#2a2a2a]">
                                {{ $featureNames[$featureKey] }}
                            </span>
                        @endif
                    @endforeach
                @else
                    <span class="text-xs text-slate-500 italic">No specific features selected</span>
                @endif
            </div>
        </div>

        <!-- BOTTOM SECTION -->
        <div class="mt-8 pt-4 border-t border-[#21262d] flex items-center justify-between">
            <div class="text-xs text-[#8b949e]">
                {{ number_format($plan->tenants_count) }} cooperative(s) on this plan
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('central.plans.edit', $plan, false) }}" class="inline-flex items-center rounded border border-[#21262d] bg-[#161b22] px-2.5 py-1.5 text-xs font-medium text-[#8b949e] transition hover:bg-[#1f2937] hover:text-white">
                    Edit
                </a>
                
                @if($hasTenants)
                    <button type="button" disabled class="inline-flex items-center rounded border border-red-900/30 bg-[#161b22] px-2.5 py-1.5 text-xs font-medium text-red-900/50 cursor-not-allowed" title="Cannot delete plan with active tenants">
                        Delete
                    </button>
                @else
                    <form method="POST" action="{{ route('central.plans.destroy', $plan, false) }}" class="inline"
                        data-confirm="Delete this plan?"
                        data-confirm-title="Delete plan?"
                        data-confirm-confirm-text="Delete"
                        data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded border border-red-500/20 bg-[#161b22] px-2.5 py-1.5 text-xs font-medium text-red-400 transition hover:bg-red-500/10 hover:border-red-500/40">
                            Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full py-12 text-center text-sm text-slate-500 bg-[#161b22] border border-[#21262d] rounded-2xl">
        No plans found.
    </div>
    @endforelse
</div>
@endsection
