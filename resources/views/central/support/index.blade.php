@extends('layouts.central')

@section('title', 'Support Requests')

@section('content')
<div class="mb-6">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Support Requests</h2>
    <p class="mt-1 text-sm text-slate-400">Manage support tickets from all tenants in one place.</p>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-white/[0.05] bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Requests</p>
        <p class="mt-2 font-heading text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-yellow-500 bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Open</p>
        <p class="mt-2 font-heading text-2xl font-bold text-yellow-300">{{ number_format($stats['open']) }}</p>
    </div>
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-blue-500 bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">In Progress</p>
        <p class="mt-2 font-heading text-2xl font-bold text-blue-300">{{ number_format($stats['in_progress']) }}</p>
    </div>
    <div class="rounded-xl border border-white/[0.05] border-l-4 border-l-emerald-500 bg-white/[0.02] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Resolved</p>
        <p class="mt-2 font-heading text-2xl font-bold text-emerald-300">{{ number_format($stats['resolved']) }}</p>
    </div>
</div>

<div class="mb-6 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
    <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tenant, subject, email..." class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-white placeholder-slate-500 transition focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Status</label>
            <select name="status" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-white transition focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="">All Statuses</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Category</label>
            <select name="category" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-white transition focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="">All Categories</option>
                <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>General</option>
                <option value="technical" {{ request('category') === 'technical' ? 'selected' : '' }}>Technical</option>
                <option value="billing" {{ request('category') === 'billing' ? 'selected' : '' }}>Billing</option>
                <option value="account" {{ request('category') === 'account' ? 'selected' : '' }}>Account</option>
                <option value="feature" {{ request('category') === 'feature' ? 'selected' : '' }}>Feature Request</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">
                Filter
            </button>
            <a href="{{ route('central.support.index', absolute: false) }}" class="rounded-lg border border-white/10 bg-white/[0.03] px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/[0.05]">
                Clear
            </a>
        </div>
    </form>
</div>

@if(session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-[#0F1729] z-10">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tenant</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Category</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Submitted</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($supportRequests as $request)
                    @php
                        $statusConfig = match ($request->status) {
                            'open' => ['class' => 'bg-yellow-500/15 text-yellow-300', 'label' => 'Open'],
                            'in_progress' => ['class' => 'bg-blue-500/15 text-blue-300', 'label' => 'In Progress'],
                            'resolved' => ['class' => 'bg-emerald-500/15 text-emerald-300', 'label' => 'Resolved'],
                            default => ['class' => 'bg-slate-500/15 text-slate-300', 'label' => ucfirst($request->status)],
                        };
                        $categoryConfig = match ($request->category) {
                            'technical' => 'bg-red-500/15 text-red-300',
                            'billing' => 'bg-amber-500/15 text-amber-300',
                            'feature' => 'bg-purple-500/15 text-purple-300',
                            'account' => 'bg-blue-500/15 text-blue-300',
                            default => 'bg-slate-500/15 text-slate-300',
                        };
                    @endphp
                    <tr class="transition hover:bg-white/[0.02]">
                        <td class="px-4 py-4">
                            <p class="font-semibold text-white">{{ $request->tenant_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $request->requester_name }}</p>
                            <p class="text-xs text-slate-500">{{ $request->requester_email }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ route('central.support.show', $request, false) }}" class="font-medium text-emerald-400 hover:text-emerald-300 transition">
                                {{ Str::limit($request->subject, 50) }}
                            </a>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $categoryConfig }}">
                                {{ ucfirst($request->category) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusConfig['class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-400">
                            {{ $request->created_at->format('M d, Y') }}
                            <p class="text-xs text-slate-500">{{ $request->created_at->format('h:i A') }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ route('central.support.show', $request, false) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">No support requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($supportRequests->hasPages())
    <div class="mt-6">
        {{ $supportRequests->links() }}
    </div>
@endif
@endsection
