@extends('layouts.tenant')

@section('title', 'Billing & Subscription')

@push('styles')
<style>
    .billing-shell [class*="bg-[#161b22]"],
    .billing-shell [class*="bg-[#0F1729]"] {
        background-color: var(--pm-card-bg) !important;
    }

    .billing-shell [class*="bg-[#0f1319]"],
    .billing-shell [class*="bg-white/[0.02]"] {
        background-color: var(--pm-surface-bg) !important;
    }

    .billing-shell [class*="border-[#21262d]"],
    .billing-shell [class*="border-white/[0.07]"],
    .billing-shell [class*="border-white/[0.06]"],
    .billing-shell [class*="divide-white/[0.04]"] {
        border-color: var(--pm-border) !important;
    }

    .billing-shell .text-white {
        color: var(--pm-text-primary) !important;
    }

    .billing-shell .text-slate-200,
    .billing-shell .text-slate-300,
    .billing-shell [class*="text-[#8b949e]"] {
        color: var(--pm-text-secondary) !important;
    }

    .billing-shell .text-slate-400,
    .billing-shell .text-slate-500 {
        color: var(--pm-text-muted) !important;
    }

    .billing-shell tbody tr:hover {
        background-color: var(--pm-table-hover-bg) !important;
    }
</style>
@endpush

@section('content')
@php
    $invoices = $invoices ?? collect();
    $tenantParameter = ['tenant' => tenant()?->id ?? request()->route('tenant')];
@endphp

<div class="billing-shell">

<div class="mb-5">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Billing & Subscription</h2>
    <p class="mt-1 text-sm text-slate-400">Manage your subscription payments</p>
</div>

<div class="mb-6 rounded-xl border border-[#21262d] bg-[#161b22] p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-white">Secure Payment via PayMongo</p>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[#8b949e]">
                Your payment is processed securely by PayMongo, a trusted Philippine payment gateway. Click "Pay Now" on your invoice below and you will be redirected to a secure checkout page.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded bg-blue-500/10 px-2 py-1 text-xs font-semibold text-blue-400">GCash</span>
            <span class="rounded bg-green-500/10 px-2 py-1 text-xs font-semibold text-green-400">Maya</span>
            <span class="rounded bg-purple-500/10 px-2 py-1 text-xs font-semibold text-purple-400">Credit / Debit Card</span>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-white/[0.06] bg-[#0f1319] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">How to Pay</p>
        <ul class="mt-3 space-y-2 text-sm text-[#8b949e]">
            <li>Step 1: Review the invoice amount and click "Pay Now via GCash/Card".</li>
            <li>Step 2: Complete the payment using GCash, Maya, or your card in PayMongo test mode.</li>
            <li>Step 3: Return to this page and use "Check Status" if your invoice is still processing.</li>
            <li>Step 4: Once verified, your subscription is renewed and your receipt is emailed automatically.</li>
        </ul>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-white/[0.07] bg-white/[0.02]">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[980px] text-sm">
            <thead class="bg-[#0F1729]">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Invoice #</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Period</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Due Date</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($invoices as $invoice)
                    @php
                        $statusClass = match ($invoice->status) {
                            'paid' => 'bg-emerald-500/15 text-emerald-300',
                            'overdue' => 'bg-red-500/15 text-red-300',
                            'pending_verification' => 'bg-blue-500/15 text-blue-300',
                            default => 'bg-amber-500/15 text-amber-300',
                        };
                        $statusLabel = $invoice->status === 'pending_verification'
                            ? 'Processing'
                            : ucfirst((string) $invoice->status);
                    @endphp
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3 text-slate-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 font-semibold text-white">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $invoice->due_date?->format('M Y') }}</td>
                        <td class="px-4 py-3 text-slate-200">P{{ number_format((float) $invoice->amount, 2) }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $invoice->due_date?->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(in_array($invoice->status, ['unpaid', 'overdue'], true))
                                <form method="POST" action="{{ route('billing.pay', [...$tenantParameter, 'invoiceId' => $invoice->id], false) }}" class="inline-flex">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-green-500 px-3 py-1.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-green-400">
                                        Pay Now via GCash/Card
                                    </button>
                                </form>
                            @elseif($invoice->status === 'pending_verification')
                                <div class="flex justify-end gap-2">
                                    <span class="inline-flex items-center rounded-lg border border-blue-400/25 px-3 py-1.5 text-xs font-medium text-blue-200/80">
                                        Processing...
                                    </span>
                                    <form method="POST" action="{{ route('billing.verify', [...$tenantParameter, 'invoiceId' => $invoice->id], false) }}" class="inline-flex">
                                        @csrf
                                        <button type="submit" class="rounded border border-[#21262d] px-2 py-1 text-xs text-[#8b949e] transition hover:text-white">
                                            Check Status
                                        </button>
                                    </form>
                                </div>
                            @else
                                <button type="button" disabled class="inline-flex items-center rounded-lg border border-emerald-400/25 px-3 py-1.5 text-xs font-medium text-emerald-200/80">
                                    View Receipt
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
