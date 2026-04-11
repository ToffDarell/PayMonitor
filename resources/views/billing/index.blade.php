@extends('layouts.tenant')

@section('title', 'Billing & Subscription')

@section('content')
@php
    $invoices = $invoices ?? collect();
@endphp

<div class="mb-5">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Billing & Subscription</h2>
    <p class="mt-1 text-sm text-slate-400">Manage your subscription payments</p>
</div>

<div x-data="{ copied: null }" class="mb-6 space-y-4">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5 transition-colors hover:border-green-500/30">
            <div class="mb-4 flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0070DC]/20 text-sm font-bold text-[#0070DC]">G</span>
                <h3 class="text-sm font-semibold text-white">GCash</h3>
            </div>
            <p class="text-xs uppercase tracking-[0.14em] text-[#8b949e]">Account Name</p>
            <p class="mt-1 text-sm font-medium text-slate-200">PayMonitor Systems</p>
            <p class="mt-3 text-xs uppercase tracking-[0.14em] text-[#8b949e]">Number</p>
            <p class="mt-1 text-sm font-medium text-white">{{ env('GCASH_NUMBER', '09XX-XXX-XXXX') }}</p>
            <button
                type="button"
                class="mt-4 inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                @click="
                    navigator.clipboard.writeText('{{ env('GCASH_NUMBER', '09XX-XXX-XXXX') }}');
                    copied = 'gcash';
                    setTimeout(() => copied = null, 2000)
                "
                :class="copied === 'gcash' ? 'bg-green-500/20 text-green-400' : 'bg-[#21262d] text-[#8b949e]'"
                x-text="copied === 'gcash' ? 'Copied!' : 'Copy Number'"
            ></button>
        </div>

        <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5 transition-colors hover:border-green-500/30">
            <div class="mb-4 flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#CC0000]/20 text-xs font-bold text-[#CC0000]">BDO</span>
                <h3 class="text-sm font-semibold text-white">BDO Bank Transfer</h3>
            </div>
            <p class="text-xs uppercase tracking-[0.14em] text-[#8b949e]">Account Name</p>
            <p class="mt-1 text-sm font-medium text-slate-200">PayMonitor Systems</p>
            <p class="mt-3 text-xs uppercase tracking-[0.14em] text-[#8b949e]">Account Number</p>
            <p class="mt-1 text-sm font-medium text-white">{{ env('BDO_ACCOUNT', 'XXXX-XXXX-XXXX') }}</p>
            <button
                type="button"
                class="mt-4 inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                @click="
                    navigator.clipboard.writeText('{{ env('BDO_ACCOUNT', 'XXXX-XXXX-XXXX') }}');
                    copied = 'bdo';
                    setTimeout(() => copied = null, 2000)
                "
                :class="copied === 'bdo' ? 'bg-green-500/20 text-green-400' : 'bg-[#21262d] text-[#8b949e]'"
                x-text="copied === 'bdo' ? 'Copied!' : 'Copy Account'"
            ></button>
        </div>

        <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5 transition-colors hover:border-green-500/30">
            <div class="mb-4 flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#C41230]/20 text-xs font-bold text-[#C41230]">BPI</span>
                <h3 class="text-sm font-semibold text-white">BPI Bank Transfer</h3>
            </div>
            <p class="text-xs uppercase tracking-[0.14em] text-[#8b949e]">Account Name</p>
            <p class="mt-1 text-sm font-medium text-slate-200">PayMonitor Systems</p>
            <p class="mt-3 text-xs uppercase tracking-[0.14em] text-[#8b949e]">Account Number</p>
            <p class="mt-1 text-sm font-medium text-white">{{ env('BPI_ACCOUNT', 'XXXX-XXXX-XXXX') }}</p>
            <button
                type="button"
                class="mt-4 inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                @click="
                    navigator.clipboard.writeText('{{ env('BPI_ACCOUNT', 'XXXX-XXXX-XXXX') }}');
                    copied = 'bpi';
                    setTimeout(() => copied = null, 2000)
                "
                :class="copied === 'bpi' ? 'bg-green-500/20 text-green-400' : 'bg-[#21262d] text-[#8b949e]'"
                x-text="copied === 'bpi' ? 'Copied!' : 'Copy Account'"
            ></button>
        </div>
    </div>

    <div class="rounded-xl border border-green-500/20 bg-green-500/5 p-4">
        <p class="text-sm font-semibold text-green-400">How to complete your payment</p>
        <ul class="mt-2 space-y-1 text-sm text-[#8b949e]">
            <li>Step 1: Send the exact amount to any payment method above</li>
            <li>Step 2: Take a screenshot or photo of your payment receipt</li>
            <li>Step 3: Click "Submit Payment Proof" on your invoice below</li>
            <li>Step 4: Wait up to 24 hours for admin verification</li>
            <li>Step 5: You will receive a confirmation email once verified</li>
        </ul>

        <div class="mt-3 rounded-xl border border-yellow-500/20 bg-yellow-500/5 p-3 text-xs text-yellow-400">
            ⚠ Always include your Invoice Number as the payment reference/note when sending payment.
        </div>
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
                            ? 'Pending Review'
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
                                <button type="button" disabled class="inline-flex items-center rounded-lg bg-emerald-600/40 px-3 py-1.5 text-xs font-medium text-white/80">
                                    Submit Payment Proof
                                </button>
                            @elseif($invoice->status === 'pending_verification')
                                <button type="button" disabled class="inline-flex items-center rounded-lg border border-blue-400/25 px-3 py-1.5 text-xs font-medium text-blue-200/80">
                                    Proof Submitted
                                </button>
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
@endsection