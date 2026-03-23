@extends('layouts.central')

@section('title', 'Tenant Profile')

@section('content')
<?php
    $domainUrl      = $primaryDomain?->domain ? ((str_starts_with($primaryDomain->domain, 'http://') || str_starts_with($primaryDomain->domain, 'https://')) ? $primaryDomain->domain : 'https://'.$primaryDomain->domain) : $tenant->getFullDomain();
    $dueDate        = $tenant->subscription_due_at;
    $daysDifference = $dueDate ? today()->diffInDays($dueDate, false) : null;

    $statusConfig = match ($tenant->status) {
        'active'    => 'bg-emerald-500/15 text-emerald-300',
        'overdue'   => 'bg-red-500/15 text-red-300',
        'suspended' => 'bg-amber-500/15 text-amber-300',
        default     => 'bg-slate-500/15 text-slate-300',
    };
    $planConfig = match (strtolower($tenant->plan?->name ?? '')) {
        'basic'    => 'bg-blue-500/15 text-blue-300',
        'standard' => 'bg-amber-500/15 text-amber-300',
        'premium'  => 'bg-emerald-500/15 text-emerald-300',
        default    => 'bg-slate-500/15 text-slate-300',
    };
?>

{{-- Page Header --}}
<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">{{ $tenant->name }}</h2>
        <p class="mt-1 text-sm text-slate-400">Tenant profile, subscription details, and database usage snapshot.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2" x-data="{ showDelete: false }">
        <a href="{{ route('central.tenants.edit', $tenant, false) }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
            Edit
        </a>
        @if($tenant->status === 'suspended')
        <form method="POST" action="{{ route('central.tenants.activate', $tenant, false) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-sm font-semibold text-emerald-300 transition hover:border-emerald-500/50 hover:bg-emerald-500/20">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                Activate
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('central.tenants.suspend', $tenant, false) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-300 transition hover:border-amber-500/50 hover:bg-amber-500/20">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Suspend
            </button>
        </form>
        @endif

        <form method="POST" action="{{ route('central.tenants.resend-credentials', $tenant, false) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-4 py-2.5 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                Resend Credentials
            </button>
        </form>
        <button @click="showDelete = true" class="inline-flex items-center gap-2 rounded-xl border border-red-500/20 px-4 py-2.5 text-sm font-medium text-red-400 transition hover:border-red-500/40 hover:bg-red-500/[0.08]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            Delete
        </button>

        {{-- Delete confirm --}}
        <div x-show="showDelete" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display:none;">
            <div class="w-full max-w-md rounded-2xl border border-white/[0.08] bg-[#0F1729] p-6 shadow-2xl">
                <h3 class="font-heading text-lg font-bold text-white">Delete Tenant</h3>
                <p class="mt-2 text-sm text-slate-400">Are you sure you want to delete <span class="font-semibold text-white">{{ $tenant->name }}</span>? This action cannot be undone.</p>
                <div class="mt-5 flex justify-end gap-3">
                    <button @click="showDelete = false" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/[0.04]">Cancel</button>
                    <form method="POST" action="{{ route('central.tenants.destroy', $tenant, false) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">Delete Tenant</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Details Cards --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mb-5">
    {{-- Tenant Profile --}}
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
        <div class="border-b border-white/[0.07] px-6 py-4">
            <h3 class="font-heading text-sm font-semibold text-white">Tenant Profile</h3>
        </div>
        <div class="divide-y divide-white/[0.04]">
            @foreach([
                ['Cooperative', $tenant->name],
                ['Address', $tenant->address ?: '—'],
                ['Status', null],
                ['Plan', null],
                ['Created At', $tenant->created_at?->format('M d, Y h:i A') ?? 'N/A'],
            ] as $row)
            @if($row[0] === 'Status')
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Status</span>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusConfig }}">{{ ucfirst($tenant->status) }}</span>
            </div>
            @elseif($row[0] === 'Plan')
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Plan</span>
                @if($tenant->plan)
                <a href="{{ route('central.tenants.show', $tenant, false) }}" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $planConfig }}">{{ $tenant->plan->name }}</a>
                @else
                <span class="text-sm text-slate-500">No Plan</span>
                @endif
            </div>
            @else
            <div class="flex items-start justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">{{ $row[0] }}</span>
                <span class="text-sm font-medium text-slate-200 text-right max-w-xs">{{ $row[1] }}</span>
            </div>
            @endif
            @endforeach
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Domain</span>
                <a href="{{ $domainUrl }}" target="_blank" class="text-sm text-emerald-400 hover:text-emerald-300 transition">{{ parse_url($domainUrl, PHP_URL_HOST) }}</a>
            </div>
        </div>
    </div>

    {{-- Subscription --}}
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
        <div class="border-b border-white/[0.07] px-6 py-4">
            <h3 class="font-heading text-sm font-semibold text-white">Subscription</h3>
        </div>
        <div class="divide-y divide-white/[0.04]">
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Plan Name</span>
                <span class="text-sm font-medium text-slate-200">{{ $tenant->plan?->name ?? 'No Plan' }}</span>
            </div>
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Price</span>
                <span class="text-sm font-semibold text-emerald-400">&#8369;{{ number_format((float) ($tenant->plan?->price ?? 0), 2) }}</span>
            </div>
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Due Date</span>
                <span class="text-sm font-medium @if($dueDate && $dueDate->isPast()) text-red-400 @else text-slate-200 @endif">
                    {{ $dueDate?->format('M d, Y') ?? 'N/A' }}
                </span>
            </div>
            <div class="flex items-center justify-between px-6 py-3.5">
                <span class="text-sm text-slate-400">Status Window</span>
                @if($daysDifference === null)
                    <span class="text-sm text-slate-500">No due date set</span>
                @elseif($daysDifference >= 0)
                    <span class="text-sm font-semibold text-emerald-400">{{ $daysDifference }} day{{ $daysDifference === 1 ? '' : 's' }} remaining</span>
                @else
                    <span class="text-sm font-semibold text-red-400">{{ abs($daysDifference) }} day{{ abs($daysDifference) === 1 ? '' : 's' }} overdue</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Admin Info --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 mb-5">
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-2">Admin Name</div>
        <div class="text-base font-semibold text-slate-200">{{ $tenant->admin_name ?: '—' }}</div>
    </div>
    <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-2">Admin Email</div>
        <div class="text-base font-semibold text-slate-200">{{ $tenant->email }}</div>
    </div>
</div>

{{-- Usage Stats --}}
<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    <div class="border-b border-white/[0.07] px-6 py-4">
        <h3 class="font-heading text-sm font-semibold text-white">Database Usage Stats</h3>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-x divide-y divide-white/[0.05]">
        @foreach([
            ['Branches', $usage['branches']],
            ['Users', $usage['users']],
            ['Members', $usage['members']],
            ['Loan Types', $usage['loan_types']],
            ['Loans', $usage['loans']],
            ['Total Records', $usage['total']],
        ] as $stat)
        <div class="p-5 text-center">
            <div class="font-heading text-2xl font-bold text-white">{{ number_format($stat[1]) }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $stat[0] }}</div>
        </div>
        @endforeach
    </div>
</div>
@endsection
