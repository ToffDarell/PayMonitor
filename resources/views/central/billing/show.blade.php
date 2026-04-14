@extends('layouts.central')

@section('title', 'Invoice Details')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Invoice Details</h2>
        <p class="mt-1 text-sm text-slate-400">Review billing details, payment status, and send invoice or receipt communications.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($invoice->status !== 'paid')
            <form method="POST" action="{{ route('central.billing.send-invoice', $invoice->tenant, false) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-amber-400/25 px-4 py-2 text-sm font-medium text-amber-200 transition hover:bg-amber-400/10">
                    Send Invoice Email
                </button>
            </form>
            <form method="POST" action="{{ route('central.billing.mark-paid', $invoice, false) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500">
                    Mark as Paid
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('central.billing.send-receipt', $invoice, false) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/25 px-4 py-2 text-sm font-medium text-emerald-200 transition hover:bg-emerald-400/10">
                    Send Receipt
                </button>
            </form>
        @endif
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
            Print
        </button>
    </div>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
    <div class="flex flex-col gap-6 border-b border-white/[0.06] pb-6 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">PayMonitor</p>
            <h3 class="mt-2 font-heading text-xl font-bold text-white">Subscription Invoice</h3>
            <p class="mt-1 text-sm text-slate-400">Invoice #{{ $invoice->invoice_number }}</p>
        </div>
        <div class="text-sm text-slate-400 md:text-right">
            <p><span class="font-medium text-white">Invoice Date:</span> {{ $invoice->created_at?->format('M d, Y') ?? 'N/A' }}</p>
            <p class="mt-1"><span class="font-medium text-white">Invoice Due Date:</span> {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-xl border border-white/[0.06] bg-[#0f1319] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Bill To</p>
            <h4 class="mt-3 text-lg font-semibold text-white">{{ $invoice->tenant?->name ?? 'Unknown Tenant' }}</h4>
            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $invoice->tenant?->address ?: 'No billing address on record.' }}</p>
            <p class="mt-2 text-sm text-slate-400">{{ $invoice->tenant?->email ?? 'No billing email on record.' }}</p>
        </div>

        <div class="rounded-xl border border-white/[0.06] bg-[#0f1319] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</p>
            @php
                $statusClass = match ($invoice->status) {
                    'paid' => 'bg-emerald-500/15 text-emerald-300',
                    'overdue' => 'bg-red-500/15 text-red-300',
                    'pending_verification' => 'bg-blue-500/15 text-blue-300',
                    default => 'bg-amber-500/15 text-amber-300',
                };
                $statusLabel = $invoice->status === 'pending_verification'
                    ? 'Pending Verification'
                    : ucfirst((string) $invoice->status);
            @endphp
            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
            <div class="mt-5 space-y-2 text-sm text-slate-400">
                <p><span class="font-medium text-white">Plan:</span> {{ $invoice->tenant?->plan?->name ?? 'No Plan' }}</p>
                <p><span class="font-medium text-white">Amount:</span> P{{ number_format((float) $invoice->amount, 2) }}</p>
                <p><span class="font-medium text-white">Paid At:</span> {{ $invoice->paid_at?->format('M d, Y h:i A') ?? 'Not yet paid' }}</p>
                <p><span class="font-medium text-white">Current Subscription Due Date:</span> {{ $invoice->tenant?->subscription_due_at?->format('M d, Y') ?? 'Not set' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-white/[0.06] bg-[#0f1319]">
        <table class="w-full text-sm">
            <thead class="bg-[#11161d]">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Description</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-white/[0.06]">
                    <td class="px-4 py-3 text-slate-300">PayMonitor SaaS subscription invoice</td>
                    <td class="px-4 py-3 text-slate-300">{{ $invoice->tenant?->plan?->name ?? 'No Plan' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-white">P{{ number_format((float) $invoice->amount, 2) }}</td>
                </tr>
            </tbody>
            <tfoot class="border-t border-white/[0.06] bg-[#11161d]">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Due</td>
                    <td class="px-4 py-3 text-right text-lg font-bold text-emerald-300">P{{ number_format((float) $invoice->amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(filled($invoice->paymongo_link_id) || filled($invoice->paymongo_payment_id) || $invoice->paid_via === 'paymongo')
        @php
            $methodClass = match ($invoice->payment_method) {
                'gcash' => 'bg-blue-500/10 text-blue-400',
                'maya' => 'bg-green-500/10 text-green-400',
                'card' => 'bg-purple-500/10 text-purple-400',
                default => 'bg-white/5 text-slate-300',
            };
        @endphp
        <div class="mt-6 rounded-xl border border-white/[0.06] bg-[#0f1319] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">PayMongo Payment Info</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-white/[0.06] bg-[#11161d] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Payment ID</p>
                    <p class="mt-2 break-all text-sm text-white">{{ $invoice->paymongo_payment_id ?? 'Not available' }}</p>
                </div>
                <div class="rounded-xl border border-white/[0.06] bg-[#11161d] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Payment Method</p>
                    <div class="mt-2">
                        <span class="inline-flex rounded px-2 py-1 text-xs font-semibold {{ $methodClass }}">
                            {{ strtoupper((string) ($invoice->payment_method ?: 'N/A')) }}
                        </span>
                    </div>
                </div>
                <div class="rounded-xl border border-white/[0.06] bg-[#11161d] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Paid Via</p>
                    <p class="mt-2 text-sm text-white">{{ $invoice->paid_via === 'paymongo' ? 'PayMongo' : ($invoice->paid_via ? ucfirst((string) $invoice->paid_via) : 'Not available') }}</p>
                </div>
                <div class="rounded-xl border border-white/[0.06] bg-[#11161d] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Transaction Reference</p>
                    <p class="mt-2 break-all text-sm text-white">{{ $invoice->paymongo_link_id ?? 'Not available' }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(filled($invoice->notes))
        <div class="mt-6 rounded-xl border border-white/[0.06] bg-[#0f1319] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Notes</p>
            <p class="mt-3 text-sm leading-6 text-slate-300">{{ $invoice->notes }}</p>
        </div>
    @endif
</div>
@endsection
