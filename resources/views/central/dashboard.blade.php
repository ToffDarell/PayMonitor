    @extends('layouts.central')

@section('title', 'Central Dashboard')

@section('content')
<div class="mb-5">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Central Dashboard</h2>
    <p class="mt-1 text-sm text-slate-400">Overview of tenant health, billing, and recent onboardings.</p>
</div>

{{-- Stat Cards --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
    {{-- Total --}}
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-indigo-500 bg-white/[0.02] p-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Tenants</span>
        </div>
        <div class="font-heading text-2xl font-bold text-white">{{ number_format($totalTenants) }}</div>
    </div>
    {{-- Active --}}
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-emerald-500 bg-white/[0.02] p-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Active</span>
        </div>
        <div class="font-heading text-2xl font-bold text-white">{{ number_format($activeTenants) }}</div>
    </div>
    {{-- Overdue --}}
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-red-500 bg-white/[0.02] p-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-2 w-2 rounded-full bg-red-500"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Overdue</span>
        </div>
        <div class="font-heading text-2xl font-bold text-white">{{ number_format($overdueTenants) }}</div>
    </div>
    {{-- Suspended --}}
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-yellow-500 bg-white/[0.02] p-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Suspended</span>
        </div>
        <div class="font-heading text-2xl font-bold text-white">{{ number_format($suspendedTenants) }}</div>
    </div>
    {{-- Inactive --}}
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-slate-500 bg-white/[0.02] p-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-2 w-2 rounded-full bg-slate-500"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Inactive</span>
        </div>
        <div class="font-heading text-2xl font-bold text-white">{{ number_format($inactiveTenants) }}</div>
    </div>
    {{-- Revenue --}}
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-emerald-400 bg-white/[0.02] p-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Monthly Revenue</span>
        </div>
        <div class="font-heading text-2xl font-bold text-emerald-300">&#8369;{{ number_format($monthlyRevenue, 2) }}</div>
    </div>
</div>

{{-- Recent Tenants --}}
<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.07]">
        <h3 class="font-heading text-base font-semibold text-white">Recent Tenants</h3>
        <a href="{{ route('central.tenants.index', absolute: false) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            Manage Tenants
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-[#0F1729] z-10">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cooperative Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Domain</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($recentTenants as $tenant)
                @php
                    $statusConfig = match ($tenant->status) {
                        'active'    => ['class' => 'bg-emerald-500/15 text-emerald-300', 'label' => 'Active'],
                        'overdue'   => ['class' => 'bg-red-500/15 text-red-300', 'label' => 'Overdue'],
                        'suspended' => ['class' => 'bg-amber-500/15 text-amber-300', 'label' => 'Suspended'],
                        default     => ['class' => 'bg-slate-500/15 text-slate-300', 'label' => ucfirst($tenant->status)],
                    };
                    $planConfig = match (strtolower($tenant->plan?->name ?? '')) {
                        'basic'    => 'bg-blue-500/15 text-blue-300',
                        'standard' => 'bg-amber-500/15 text-amber-300',
                        'premium'  => 'bg-emerald-500/15 text-emerald-300',
                        default    => 'bg-slate-500/15 text-slate-300',
                    };
                @endphp
                <tr class="transition hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3 font-semibold text-white">{{ $tenant->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $planConfig }}">{{ $tenant->plan?->name ?? 'No Plan' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ $tenant->getFullDomain() }}" target="_blank" class="text-emerald-400 hover:text-emerald-300 transition">
                            {{ parse_url($tenant->getFullDomain(), PHP_URL_HOST) }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-400">{{ $tenant->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">No tenants found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
