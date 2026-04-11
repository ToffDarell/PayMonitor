@extends('layouts.central')

@section('title', 'Billing')

@section('content')
<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Billing</h2>
        <p class="mt-1 text-sm text-slate-400">Manage subscription invoices, payment receipts, and tenant billing follow-ups.</p>
    </div>
    <a href="{{ route('central.payments.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-12 4.5h3"/></svg>
        Payments Overview
    </a>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Invoiced</p>
        <p class="mt-3 text-2xl font-bold text-white">P{{ number_format($summary['total_invoiced'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Paid</p>
        <p class="mt-3 text-2xl font-bold text-emerald-300">P{{ number_format($summary['total_paid'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Unpaid</p>
        <p class="mt-3 text-2xl font-bold text-amber-300">P{{ number_format($summary['total_unpaid'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Overdue</p>
        <p class="mt-3 text-2xl font-bold text-red-300">P{{ number_format($summary['total_overdue'], 2) }}</p>
    </div>
</div>

<div class="mb-6 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-4">
    <form method="GET" action="{{ route('central.billing.index', absolute: false) }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div>
            <label for="tenant_id" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tenant</label>
            <select id="tenant_id" name="tenant_id" class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-200">
                <option value="">All tenants</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(request('tenant_id') === $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</label>
            <select id="status" name="status" class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-200">
                <option value="">All statuses</option>
                @foreach(['unpaid', 'paid', 'overdue'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="month" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Month</label>
            <select id="month" name="month" class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-200">
                <option value="">All months</option>
                @foreach(range(1, 12) as $month)
                    <option value="{{ $month }}" @selected((int) request('month') === $month)>{{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Date From</label>
            <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-200">
        </div>
        <div>
            <label for="date_to" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Date To</label>
            <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-200">
        </div>
        <div class="flex items-end gap-3 xl:col-span-5">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500">
                Apply Filters
            </button>
            <a href="{{ route('central.billing.index', absolute: false) }}" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="overflow-hidden rounded-2xl border border-white/[0.07] bg-white/[0.02]">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1260px] text-sm">
            <thead class="bg-[#0F1729]">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Invoice #</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tenant</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Invoice Due Date</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Paid At</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Renewal Date</th>
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($invoices as $invoice)
                    @php
                        $statusClass = match ($invoice->status) {
                            'paid' => 'bg-emerald-500/15 text-emerald-300',
                            'overdue' => 'bg-red-500/15 text-red-300',
                            default => 'bg-amber-500/15 text-amber-300',
                        };
                        $renewalDate = $invoice->status === 'paid'
                            ? $invoice->tenant?->subscription_due_at?->format('M d, Y')
                            : null;
                    @endphp
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3 font-semibold text-white">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $invoice->tenant?->name ?? 'Unknown Tenant' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $invoice->tenant?->plan?->name ?? 'No Plan' }}</td>
                        <td class="px-4 py-3 text-slate-200">P{{ number_format((float) $invoice->amount, 2) }}</td>
                        <td class="px-4 py-3 {{ $invoice->status === 'overdue' ? 'font-semibold text-red-300' : 'text-slate-300' }}">
                            {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ $invoice->paid_at?->format('M d, Y h:i A') ?? 'Not Paid' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $renewalDate ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('central.billing.show', $invoice, false) }}" class="inline-flex items-center rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                                    View
                                </a>
                                @if($invoice->status !== 'paid')
                                    <form method="POST" action="{{ route('central.billing.mark-paid', $invoice, false) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-500">
                                            Mark Paid
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('central.billing.send-invoice', $invoice->tenant, false) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-amber-400/25 px-3 py-1.5 text-xs font-medium text-amber-200 transition hover:bg-amber-400/10">
                                            Resend Invoice
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('central.billing.send-receipt', $invoice, false) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-emerald-400/25 px-3 py-1.5 text-xs font-medium text-emerald-200 transition hover:bg-emerald-400/10">
                                            Send Receipt
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-sm text-slate-500">No billing invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div class="border-t border-white/[0.06] px-6 py-4">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
