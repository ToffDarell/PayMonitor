@extends('layouts.central')

@section('title', 'Support Request Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('central.support.index', absolute: false) }}" class="inline-flex items-center gap-2 text-sm text-slate-400 transition hover:text-white">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back to Support Requests
    </a>
</div>

@if(session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-heading text-xl font-bold text-white">{{ $supportRequest->subject }}</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Submitted {{ $supportRequest->created_at->format('M d, Y \a\t h:i A') }}
                    </p>
                </div>
                @php
                    $statusConfig = match ($supportRequest->status) {
                        'open' => ['class' => 'bg-yellow-500/15 text-yellow-300 border-yellow-500/20', 'label' => 'Open'],
                        'in_progress' => ['class' => 'bg-blue-500/15 text-blue-300 border-blue-500/20', 'label' => 'In Progress'],
                        'resolved' => ['class' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/20', 'label' => 'Resolved'],
                        default => ['class' => 'bg-slate-500/15 text-slate-300 border-slate-500/20', 'label' => ucfirst($supportRequest->status)],
                    };
                @endphp
                <span class="inline-flex rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.14em] {{ $statusConfig['class'] }}">
                    {{ $statusConfig['label'] }}
                </span>
            </div>

            <div class="mb-6 rounded-lg border border-white/[0.06] bg-white/[0.02] p-4">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Message</h3>
                <div class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200">{{ $supportRequest->message }}</div>
            </div>

            @if($supportRequest->resolved_at)
                <div class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                    <p class="text-sm text-emerald-300">
                        <span class="font-semibold">Resolved:</span> {{ $supportRequest->resolved_at->format('M d, Y \a\t h:i A') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] p-6">
            <h3 class="mb-4 font-heading text-base font-semibold text-white">Request Details</h3>
            
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tenant</p>
                    <p class="mt-1 text-sm font-medium text-white">{{ $supportRequest->tenant_name }}</p>
                    <p class="text-xs text-slate-400">ID: {{ $supportRequest->tenant_id }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Requester</p>
                    <p class="mt-1 text-sm font-medium text-white">{{ $supportRequest->requester_name }}</p>
                    <p class="text-xs text-slate-400">{{ $supportRequest->requester_email }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Category</p>
                    @php
                        $categoryConfig = match ($supportRequest->category) {
                            'technical' => 'bg-red-500/15 text-red-300',
                            'billing' => 'bg-amber-500/15 text-amber-300',
                            'feature' => 'bg-purple-500/15 text-purple-300',
                            'account' => 'bg-blue-500/15 text-blue-300',
                            default => 'bg-slate-500/15 text-slate-300',
                        };
                    @endphp
                    <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $categoryConfig }}">
                        {{ ucfirst($supportRequest->category) }}
                    </span>
                </div>
            </div>

            <div class="mt-6 border-t border-white/[0.06] pt-6">
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Update Status</h4>
                <form method="POST" action="{{ route('central.support.update-status', $supportRequest, false) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="mb-3 w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-white transition focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="open" {{ $supportRequest->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ $supportRequest->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $supportRequest->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">
                        Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
