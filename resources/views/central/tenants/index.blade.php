@extends('layouts.central')

@section('title', 'Tenant Management')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Tenant Management</h2>
        <p class="mt-1 text-sm text-slate-400">Manage cooperative accounts, subscriptions, and provisioning actions.</p>
    </div>
    <a href="{{ route('central.tenants.create', absolute: false) }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Tenant
    </a>
</div>

<div class="overflow-hidden rounded-2xl border border-white/[0.07] bg-white/[0.02]">
    <div class="border-b border-white/[0.07] px-6 py-4">
        <div class="relative max-w-md">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="search" id="tenantSearch" placeholder="Search tenant list..." class="w-full rounded-xl border border-white/10 bg-white/[0.03] py-2.5 pl-10 pr-4 text-sm text-slate-200 placeholder-slate-500 transition focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
        </div>
    </div>

    <div class="overflow-auto">
        <table class="w-full min-w-[1240px] table-fixed text-sm" id="tenantTable">
            <colgroup>
                <col style="width: 56px;">
                <col style="width: 15%;">
                <col style="width: 12%;">
                <col style="width: 13%;">
                <col style="width: 10%;">
                <col style="width: 13%;">
                <col style="width: 8%;">
                <col style="width: 10%;">
                <col style="width: 8%;">
                <col style="width: 6%;">
                <col style="width: 5%;">
            </colgroup>
            <thead class="sticky top-0 z-20 bg-[#0F1729]">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase text-slate-500">#</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cooperative Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Address</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Domain</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Admin Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Admin Email</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Due Date</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">DB Usage</th>
                    <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($tenants as $tenant)
                    @php
                        $domainUrl = $tenant->getFullDomain();
                        $dueDate = $tenant->subscription_due_at;

                        $statusConfig = match ($tenant->status) {
                            'active' => 'bg-emerald-500/15 text-emerald-300',
                            'overdue' => 'bg-red-500/15 text-red-300',
                            'suspended' => 'bg-amber-500/15 text-amber-300',
                            default => 'bg-slate-500/15 text-slate-300',
                        };

                        $planConfig = match (strtolower($tenant->plan?->name ?? '')) {
                            'basic' => 'bg-blue-500/15 text-blue-300',
                            'standard' => 'bg-amber-500/15 text-amber-300',
                            'premium' => 'bg-emerald-500/15 text-emerald-300',
                            default => 'bg-slate-500/15 text-slate-300',
                        };
                    @endphp
                    <tr class="transition hover:bg-white/[0.02]" data-tenant-row="true" x-data="{ dropOpen: false, showDelete: false }">
                        <td class="px-4 py-3 text-slate-500">{{ $tenants->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-semibold text-white" title="{{ $tenant->name }}">
                            <div class="truncate">{{ $tenant->name }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-300" title="{{ $tenant->address ?: 'N/A' }}">
                            <div class="whitespace-normal break-words">{{ $tenant->address ?: 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-3" title="{{ parse_url($domainUrl, PHP_URL_HOST) }}">
                            <a href="{{ $domainUrl }}" target="_blank" class="block truncate text-emerald-400 transition hover:text-emerald-300">
                                {{ parse_url($domainUrl, PHP_URL_HOST) }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-300" title="{{ $tenant->admin_name ?: 'N/A' }}">
                            <div class="truncate">{{ $tenant->admin_name ?: 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-300" title="{{ $tenant->email }}">
                            <div class="whitespace-normal break-all">{{ $tenant->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium {{ $planConfig }}">{{ $tenant->plan?->name ?? 'No Plan' }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap @if($dueDate && $dueDate->isPast()) font-semibold text-red-400 @else text-slate-300 @endif">
                            {{ $dueDate?->format('M d, Y') ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium {{ $statusConfig }}">{{ ucfirst($tenant->status) }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-300">
                            {{ number_format(data_get($tenant->usage, 'total', 0)) }}
                            <span class="text-[10px] text-slate-500">rows</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center" @click.outside="dropOpen = false">
                            <div class="relative inline-block text-left">
                                <button @click="dropOpen = !dropOpen" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zm0 6a.75.75 0 110-1.5.75.75 0 010 1.5zm0 6a.75.75 0 110-1.5.75.75 0 010 1.5z"/></svg>
                                </button>

                                <div x-show="dropOpen" x-transition class="absolute right-0 z-30 mt-1 w-52 rounded-xl border border-white/10 bg-[#0F1729] py-1 shadow-xl" style="display:none;">
                                    <a href="{{ route('central.tenants.show', $tenant, false) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 transition hover:bg-white/[0.04] hover:text-white">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        View
                                    </a>
                                    <a href="{{ route('central.tenants.edit', $tenant, false) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 transition hover:bg-white/[0.04] hover:text-white">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        Edit
                                    </a>

                                    <div class="my-1 border-t border-white/[0.06]"></div>

                                    @if($tenant->status === 'suspended')
                                        <form method="POST" action="{{ route('central.tenants.activate', $tenant, false) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-emerald-400 transition hover:bg-white/[0.04]">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                                                Activate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('central.tenants.suspend', $tenant, false) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-amber-400 transition hover:bg-white/[0.04]">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Suspend
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('central.tenants.resend-credentials', $tenant, false) }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-300 transition hover:bg-white/[0.04] hover:text-white">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                            Resend Credentials
                                        </button>
                                    </form>

                                    <div class="my-1 border-t border-white/[0.06]"></div>

                                    <button @click="showDelete = true; dropOpen = false" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-400 transition hover:bg-red-500/[0.08]">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>

                            <div x-cloak x-show="showDelete" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 text-left whitespace-normal backdrop-blur-sm" style="display:none;">
                                <div class="w-full max-w-md rounded-2xl border border-white/[0.08] bg-[#0F1729] p-6 text-left whitespace-normal shadow-2xl">
                                    <h3 class="font-heading text-lg font-bold text-white">Delete Tenant</h3>
                                    <p class="mt-2 text-sm text-slate-400">Are you sure you want to delete <span class="font-semibold text-white">{{ $tenant->name }}</span>? This action cannot be undone.</p>
                                    <div class="mt-5 flex justify-end gap-3">
                                        <button @click="showDelete = false" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/[0.04]">Cancel</button>
                                        <form method="POST" action="{{ route('central.tenants.destroy', $tenant, false) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">Delete Tenant</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center text-sm text-slate-500">No tenants found.</td>
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
        const searchInput = document.getElementById('tenantSearch');
        const tableRows = document.querySelectorAll('#tenantTable tbody tr[data-tenant-row="true"]');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const keyword = this.value.toLowerCase().trim();

                tableRows.forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush
