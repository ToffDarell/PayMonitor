@extends('layouts.central')

@section('title', 'Payments')

@section('content')
@php
    $allTenantsForStats = \App\Models\Tenant::with('plan')->get();
    $totalSubs = $allTenantsForStats->count();
    $currentSum = $allTenantsForStats->filter(function($t) {
        return $t->subscription_due_at && $t->subscription_due_at->gte(today()) && $t->subscription_due_at->lte(today()->copy()->addDays(7)) == false;
    })->sum(fn($t) => (float)($t->plan?->price ?? 0));
    $currentSum += $allTenantsForStats->filter(function($t) {
        return $t->subscription_due_at && $t->subscription_due_at->gte(today()) && $t->subscription_due_at->lte(today()->copy()->addDays(7));
    })->sum(fn($t) => (float)($t->plan?->price ?? 0));
    // Above calculates all current & due soon. Let's just simply check gte(today()) for "current" revenue
    $currentSum = $allTenantsForStats->filter(fn($t) => $t->subscription_due_at && $t->subscription_due_at->gte(today()))->sum(fn($t) => (float)($t->plan?->price ?? 0));
    
    $overdueSum = $allTenantsForStats->filter(fn($t) => !$t->subscription_due_at || $t->subscription_due_at->lt(today()))->sum(fn($t) => (float)($t->plan?->price ?? 0));
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Payments</h2>
        <p class="mt-1 text-sm text-slate-400">Track current subscriptions, due accounts, and mark tenant payments.</p>
    </div>
    <a href="{{ route('central.billing.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
        Open Billing Ledger
    </a>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-blue-500 bg-white/[0.02] p-4 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                <span class="text-sm font-medium text-slate-400">Total Subscriptions</span>
            </div>
            <div class="font-heading text-2xl font-bold text-white">{{ number_format($totalSubs) }}</div>
        </div>
    </div>
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-emerald-500 bg-white/[0.02] p-4 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span class="text-sm font-medium text-slate-400">Collected This Month</span>
            </div>
            <div class="font-heading text-2xl font-bold text-emerald-300">&#8369;{{ number_format($currentSum, 2) }}</div>
        </div>
    </div>
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-red-500 bg-white/[0.02] p-4 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                <span class="text-sm font-medium text-slate-400">Overdue Amount</span>
            </div>
            <div class="font-heading text-2xl font-bold text-red-400">&#8369;{{ number_format($overdueSum, 2) }}</div>
        </div>
    </div>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    {{-- Filter bar --}}
    <div class="border-b border-white/[0.07] px-6 py-3.5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Payments overview</p>
        <div class="flex items-center gap-2">
            <label for="paymentStatusFilter" class="text-xs font-medium text-slate-400">Filter by status</label>
            <select id="paymentStatusFilter" class="rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-200 transition focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="">All Statuses</option>
                <option value="current">Current</option>
                <option value="due_soon">Due Soon</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="paymentsTable">
            <thead class="sticky top-0 z-20 bg-[#0F1729]">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cooperative Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Amount (&#8369;)</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Due Date</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Days Overdue</th>
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($tenants as $tenant)
                <?php
                    $dueDate    = $tenant->subscription_due_at;
                    $daysOverdue = $dueDate && $dueDate->isPast() ? $dueDate->diffInDays(today()) : 0;
                    $statusMap = [
                        'current'  => 'bg-emerald-500/15 text-emerald-300',
                        'due_soon' => 'bg-amber-500/15 text-amber-300',
                        'overdue'  => 'bg-red-500/15 text-red-300',
                    ];
                    $statusConfig = $statusMap[$tenant->payment_status] ?? 'bg-slate-500/15 text-slate-300';
                ?>
                <tr class="transition hover:bg-white/[0.02]" data-status="{{ $tenant->payment_status }}">
                    <td class="px-4 py-3 text-slate-500">{{ $tenants->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3 font-semibold text-white">{{ $tenant->name }}</td>
                    <td class="px-4 py-3 text-slate-300">
                        <span class="inline-flex rounded-full bg-slate-500/10 px-2 py-0.5 text-[10px] font-medium text-slate-300">
                            {{ $tenant->plan?->name ?? 'No Plan' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-200">&#8369;{{ number_format((float) ($tenant->plan?->price ?? 0), 2) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap @if($dueDate && $dueDate->isPast()) text-red-400 font-semibold @else text-slate-300 @endif">
                        {{ $dueDate?->format('M d, Y') ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium {{ $statusConfig }}">
                            {{ ucfirst(str_replace('_', ' ', $tenant->payment_status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 @if($tenant->payment_status === 'overdue') text-red-400 font-semibold @else text-slate-500 @endif">
                        {{ $tenant->payment_status === 'overdue' ? number_format($daysOverdue) : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('central.billing.index', ['tenant_id' => $tenant->id], false) }}" class="inline-flex items-center rounded-md border border-white/10 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                                View Billing
                            </a>
                            <form method="POST" action="{{ route('central.payments.mark-paid', $tenant, false) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-emerald-500">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Mark Paid
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500">No payment records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tenants->hasPages())
    <div class="border-t border-white/[0.06] px-6 py-4">
        {{ $tenants->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filter = document.getElementById('paymentStatusFilter');
        const rows = document.querySelectorAll('#paymentsTable tbody tr[data-status]');
        filter.addEventListener('change', function () {
            const selected = this.value;
            rows.forEach(function (row) {
                row.style.display = !selected || row.dataset.status === selected ? '' : 'none';
            });
        });
    });
</script>
@endpush
