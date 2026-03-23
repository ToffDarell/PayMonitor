@extends('layouts.central')

@section('title', 'Plans')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Plans</h2>
        <p class="mt-1 text-sm text-slate-400">Manage subscription tiers for lending cooperative tenants.</p>
    </div>
    <a href="{{ route('central.plans.create', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Plan
    </a>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/[0.06]">
                    <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                    <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Name</th>
                    <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Price</th>
                    <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Max Branches</th>
                    <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Max Users</th>
                    <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tenants Count</th>
                    <th class="px-6 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($plans as $plan)
                <?php $hasTenants = $plan->tenants_count > 0; ?>
                <tr class="transition hover:bg-white/[0.02]">
                    <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                    <td class="px-6 py-4 font-semibold text-white">{{ $plan->name }}</td>
                    <td class="px-6 py-4 text-emerald-400">&#8369;{{ number_format((float) $plan->price, 2) }}</td>
                    <td class="px-6 py-4 text-slate-300">
                        @if((int) $plan->max_branches === 0)
                            <span class="text-emerald-400">Unlimited</span>
                        @else
                            {{ number_format($plan->max_branches) }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-300">
                        @if((int) $plan->max_users === 0)
                            <span class="text-emerald-400">Unlimited</span>
                        @else
                            {{ number_format($plan->max_users) }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-300">{{ number_format($plan->tenants_count) }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('central.plans.edit', $plan, false) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-emerald-500/40 hover:bg-emerald-500/[0.08] hover:text-emerald-300">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                Edit
                            </a>
                            @if($hasTenants)
                                <span class="inline-flex items-center gap-1.5 cursor-not-allowed rounded-lg border border-white/5 px-3 py-1.5 text-xs font-medium text-slate-600" title="Cannot delete plan with active tenants">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                    Delete
                                </span>
                            @else
                                <form method="POST" action="{{ route('central.plans.destroy', $plan, false) }}" class="inline" onsubmit="return confirm('Delete this plan?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-500/20 px-3 py-1.5 text-xs font-medium text-red-400 transition hover:border-red-500/40 hover:bg-red-500/[0.08]">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No plans found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
