@extends('layouts.tenant')

@section('title', 'Dashboard')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $metricCards = [
        [
            'label' => 'Active loans',
            'value' => number_format($activeLoansCount),
            'detail' => 'Currently active loan accounts.',
            'border' => 'border-l-emerald-500',
            'dot' => 'bg-emerald-500',
            'value_class' => 'text-emerald-300',
        ],
        [
            'label' => 'Outstanding balance',
            'value' => '&#8369;'.number_format($totalOutstandingBalance, 2),
            'detail' => 'Remaining balance across active loans.',
            'border' => 'border-l-sky-500',
            'dot' => 'bg-sky-500',
            'value_class' => 'text-sky-300',
        ],
        [
            'label' => 'Overdue loans',
            'value' => number_format($overdueLoansCount),
            'detail' => 'Loans already past their due date.',
            'border' => 'border-l-red-500',
            'dot' => 'bg-red-500',
            'value_class' => 'text-red-300',
        ],
        [
            'label' => 'Members count',
            'value' => number_format($totalMembersCount),
            'detail' => 'Borrowers currently tracked in this tenant.',
            'border' => 'border-l-indigo-500',
            'dot' => 'bg-indigo-500',
            'value_class' => 'text-indigo-300',
        ],
        [
            'label' => 'Collections this month',
            'value' => '&#8369;'.number_format($totalPaymentsThisMonth, 2),
            'detail' => 'Payments recorded in the current month.',
            'border' => 'border-l-amber-500',
            'dot' => 'bg-amber-500',
            'value_class' => 'text-amber-300',
        ],
        [
            'label' => 'Loan types available',
            'value' => number_format($loanTypesCount),
            'detail' => 'Active loan products ready for use.',
            'border' => 'border-l-emerald-400',
            'dot' => 'bg-emerald-400',
            'value_class' => 'text-white',
        ],
    ];

    $loanStatusClasses = [
        'active' => 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'fully_paid' => 'border border-sky-500/30 bg-sky-500/10 text-sky-300',
        'overdue' => 'border border-red-500/30 bg-red-500/10 text-red-300',
        'restructured' => 'border border-amber-500/30 bg-amber-500/10 text-amber-300',
    ];
@endphp

<div class="space-y-8">
    <section class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Tenant Workspace</p>
            <h2 class="mt-3 font-heading text-3xl font-bold tracking-tight text-white">Dashboard</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                {{ tenant()?->name ?? 'Lending Cooperative' }} lending activity, collections, and portfolio visibility in one place.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('members.index', $tenantParameter, false) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                <i class="bi bi-people text-base"></i>
                <span>Members</span>
            </a>
            @can('create', \App\Models\Loan::class)
                <a href="{{ route('loans.create', $tenantParameter, false) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110" style="background-color: var(--pm-accent); box-shadow: 0 16px 36px rgba(var(--pm-accent-rgb), 0.22);">
                    <i class="bi bi-cash-coin text-base"></i>
                    <span>New Loan</span>
                </a>
            @endcan
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach($metricCards as $metric)
            <div class="rounded-xl border border-white/[0.05] border-l-4 {{ $metric['border'] }} bg-white/[0.02] p-4">
                <div class="mb-1 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full {{ $metric['dot'] }}"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metric['label'] }}</span>
                </div>
                <div class="font-heading text-2xl font-bold {!! $metric['value_class'] !!}">{!! $metric['value'] !!}</div>
                <p class="mt-2 text-xs text-slate-500">{{ $metric['detail'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-white/[0.07] px-6 py-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <h3 class="font-heading text-base font-semibold text-white">Recent Loans</h3>
                <p class="mt-1 text-sm text-slate-400">Latest loan records released or updated in this workspace.</p>
            </div>
            <a href="{{ route('loans.index', $tenantParameter, false) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                View All
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1040px] text-sm">
                <thead class="sticky top-0 z-10 bg-[#0F1729]">
                    <tr class="border-b border-white/[0.06]">
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Member Name</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Loan Type</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Monthly Payment</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Balance</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Date Released</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @forelse($recentLoans as $loan)
                        <tr class="transition hover:bg-white/[0.02]">
                            <td class="px-4 py-4 align-top">
                                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan], false) }}" class="font-semibold text-white transition hover:text-emerald-300">
                                    {{ $loan->member?->full_name ?? 'Unknown Member' }}
                                </a>
                            </td>
                            <td class="px-4 py-4 align-top text-slate-300">{{ $loan->loanType?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-4 align-top text-slate-300">&#8369;{{ number_format((float) $loan->principal_amount, 2) }}</td>
                            <td class="px-4 py-4 align-top text-slate-300">&#8369;{{ number_format((float) $loan->monthly_payment, 2) }}</td>
                            <td class="px-4 py-4 align-top font-semibold text-red-300">&#8369;{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] {{ $loanStatusClasses[$loan->status] ?? 'border border-slate-500/30 bg-slate-500/10 text-slate-300' }}">
                                    {{ str_replace('_', ' ', $loan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-top text-slate-400">{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No recent loans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
        <div class="border-b border-white/[0.07] px-6 py-4">
            <h3 class="font-heading text-base font-semibold text-white">Overdue Loans</h3>
            <p class="mt-1 text-sm text-slate-400">Loans requiring immediate collection follow-up.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-sm">
                <thead class="sticky top-0 z-10 bg-[#0F1729]">
                    <tr class="border-b border-white/[0.06]">
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Member</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Loan #</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Balance</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Due Date</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Days Overdue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @forelse($topOverdueLoans as $loan)
                        <tr class="transition hover:bg-white/[0.02]">
                            <td class="px-4 py-4 align-top text-slate-300">{{ $loan->member?->full_name ?? 'Unknown Member' }}</td>
                            <td class="px-4 py-4 align-top">
                                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan], false) }}" class="font-semibold text-white transition hover:text-emerald-300">
                                    {{ $loan->loan_number }}
                                </a>
                            </td>
                            <td class="px-4 py-4 align-top font-semibold text-red-300">&#8369;{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                            <td class="px-4 py-4 align-top text-slate-400">{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-4 align-top text-slate-300">{{ $loan->due_date ? $loan->due_date->diffInDays(today()) : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">No overdue loans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
