@extends('layouts.central')

@section('title', 'Central Dashboard')

@section('content')
@php
    $metricToneMap = [
        'mrr' => [
            'border' => 'border-l-emerald-500',
            'dot' => 'bg-emerald-500',
            'value' => 'text-emerald-300',
        ],
        'collections' => [
            'border' => 'border-l-sky-500',
            'dot' => 'bg-sky-500',
            'value' => 'text-sky-300',
        ],
        'new_applications' => [
            'border' => 'border-l-indigo-500',
            'dot' => 'bg-indigo-500',
            'value' => 'text-indigo-300',
        ],
        'churn_rate' => [
            'border' => 'border-l-amber-500',
            'dot' => 'bg-amber-500',
            'value' => 'text-amber-300',
        ],
        'overdue_rate' => [
            'border' => 'border-l-red-500',
            'dot' => 'bg-red-500',
            'value' => 'text-red-300',
        ],
        'pending_payments' => [
            'border' => 'border-l-yellow-500',
            'dot' => 'bg-yellow-500',
            'value' => ($pendingPayments ?? 0) > 0 ? 'text-yellow-300' : 'text-emerald-300',
        ],
    ];
@endphp
<div class="mb-5">
    <h2 class="font-heading text-2xl font-bold tracking-tight text-white">Central Dashboard</h2>
    <p class="mt-1 text-sm text-slate-400">Revenue, billing risk, application flow, and tenant health in one place.</p>
</div>

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
    @foreach($dashboardMetrics as $metricKey => $metric)
        @php
            $tone = $metricToneMap[$metricKey] ?? [
                'border' => 'border-l-slate-500',
                'dot' => 'bg-slate-500',
                'value' => 'text-white',
            ];

            $value = match ($metricKey) {
                'mrr', 'collections' => '&#8369;'.number_format((float) $metric['value'], 2),
                'churn_rate', 'overdue_rate' => number_format((float) $metric['value'], 1).'%',
                default => number_format((int) $metric['value']),
            };
        @endphp
        <div class="rounded-xl border border-white/[0.05] border-l-4 {{ $tone['border'] }} bg-white/[0.02] p-4">
            <div class="mb-1 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full {{ $tone['dot'] }}"></span>
                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metric['label'] }}</span>
            </div>
            <div class="font-heading text-2xl font-bold {!! $tone['value'] !!}">{!! $value !!}</div>
            <p class="mt-2 text-xs text-slate-500">{{ $metric['detail'] }}</p>
        </div>
    @endforeach
</div>

<div class="mb-8 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h3 class="font-heading text-base font-semibold text-white">Portfolio Status</h3>
            <p class="mt-1 text-sm text-slate-400">Keep the status mix visible while the KPI row tracks business performance.</p>
        </div>
        <span class="inline-flex w-fit whitespace-nowrap rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
            {{ number_format($totalTenants) }} total tenants
        </span>
    </div>
    <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Active</p>
            <p class="mt-2 font-heading text-2xl font-bold text-emerald-300">{{ number_format($activeTenants) }}</p>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Overdue</p>
            <p class="mt-2 font-heading text-2xl font-bold text-red-300">{{ number_format($overdueTenants) }}</p>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Suspended</p>
            <p class="mt-2 font-heading text-2xl font-bold text-amber-300">{{ number_format($suspendedTenants) }}</p>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Inactive</p>
            <p class="mt-2 font-heading text-2xl font-bold text-slate-300">{{ number_format($inactiveTenants) }}</p>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Avg Health</p>
            <p class="mt-2 font-heading text-2xl font-bold text-white">{{ number_format((float) $healthSummary['average_score'], 1) }}</p>
        </div>
    </div>
</div>

<div class="mb-8 rounded-2xl border border-white/[0.07] bg-white/[0.02] p-5">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h3 class="font-heading text-base font-semibold text-white">Operations Queue</h3>
            <p class="mt-1 text-sm text-slate-400">Central follow-ups that need review this cycle.</p>
        </div>
        <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Review items at a glance</p>
    </div>
    <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">New Applications</p>
                    <p class="mt-2 font-heading text-2xl font-bold text-indigo-300">{{ number_format($newApplicationsThisMonth) }}</p>
                </div>
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h12A2.25 2.25 0 0 1 20.25 6.75v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 7.5 8.25 5.25 8.25-5.25"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Applications received this month.</p>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pending Verification</p>
            <p class="mt-2 font-heading text-2xl font-bold {{ $pendingPayments > 0 ? 'text-yellow-300' : 'text-emerald-300' }}">
                {{ number_format($pendingPayments) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">Awaiting admin confirmation.</p>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Attention Needed</p>
            <p class="mt-2 font-heading text-2xl font-bold text-amber-300">{{ number_format($healthSummary['attention_needed']) }}</p>
            <p class="mt-2 text-xs text-slate-500">Watchlist tenants in watch or critical state.</p>
        </div>
    </div>
</div>

<div class="mb-8 rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    <div class="flex flex-col gap-4 border-b border-white/[0.07] px-6 py-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h3 class="font-heading text-base font-semibold text-white">Tenant Health Watchlist</h3>
            <p class="mt-1 text-sm text-slate-400">Score starts at 100 and drops when quota usage, database size, unresolved support, or billing risk increases.</p>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs font-semibold uppercase tracking-[0.16em] sm:w-fit xl:grid-cols-4">
            <span class="inline-flex w-full justify-center whitespace-nowrap rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-emerald-300">{{ number_format($healthSummary['healthy']) }} healthy</span>
            <span class="inline-flex w-full justify-center whitespace-nowrap rounded-full border border-sky-500/20 bg-sky-500/10 px-3 py-1 text-sky-300">{{ number_format($healthSummary['stable']) }} stable</span>
            <span class="inline-flex w-full justify-center whitespace-nowrap rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-amber-300">{{ number_format($healthSummary['watch']) }} watch</span>
            <span class="inline-flex w-full justify-center whitespace-nowrap rounded-full border border-rose-500/20 bg-rose-500/10 px-3 py-1 text-rose-300">{{ number_format($healthSummary['critical']) }} critical</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10 bg-[#0F1729]">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tenant</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Health</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Quota Usage</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">DB Size</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Support</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Billing Risk</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($tenantHealthWatchlist as $snapshot)
                    @php
                        $penalties = array_filter([
                            $snapshot['quota_penalty'] > 0 ? 'quota -'.$snapshot['quota_penalty'] : null,
                            $snapshot['database_penalty'] > 0 ? 'db -'.$snapshot['database_penalty'] : null,
                            $snapshot['support_penalty'] > 0 ? 'support -'.$snapshot['support_penalty'] : null,
                            $snapshot['billing_penalty'] > 0 ? 'billing -'.$snapshot['billing_penalty'] : null,
                        ]);
                        $quotaSegments = array_map('trim', explode(',', $snapshot['quota_summary']));
                        $billingClasses = match ($snapshot['billing_label']) {
                            'Current' => 'bg-emerald-500/15 text-emerald-300',
                            'Due soon', 'Billing date missing' => 'bg-amber-500/15 text-amber-300',
                            'Verifying payment', 'Inactive' => 'bg-sky-500/15 text-sky-300',
                            default => 'bg-rose-500/15 text-rose-300',
                        };
                    @endphp
                    <tr class="transition hover:bg-white/[0.02]">
                        <td class="px-4 py-4 align-top">
                            <a href="{{ route('central.tenants.show', $snapshot['tenant'], false) }}" class="font-semibold text-white transition hover:text-emerald-300">
                                {{ $snapshot['tenant_name'] }}
                            </a>
                            <p class="mt-1 text-xs text-slate-500">{{ $snapshot['plan_name'] }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $snapshot['health_classes'] }}">
                                    {{ $snapshot['health_label'] }}
                                </span>
                                <span class="font-heading text-2xl font-bold text-white">{{ number_format((int) $snapshot['health_score']) }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                @if($penalties !== [])
                                    Penalties: {{ implode(', ', $penalties) }}
                                @else
                                    No active penalties.
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            @foreach($quotaSegments as $segment)
                                <p class="font-medium text-slate-200">{{ $segment }}</p>
                            @endforeach
                            <p class="mt-1 text-xs text-slate-500">Peak usage {{ number_format((float) $snapshot['quota_peak_percent'], 1) }}%</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="font-medium text-slate-200">{{ $snapshot['db_size_formatted'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ number_format((int) $snapshot['db_total_rows']) }} rows tracked</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="font-medium text-slate-200">{{ number_format($snapshot['unresolved_support']) }} open ticket(s)</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $snapshot['unresolved_support'] > 0 ? 'Needs follow-up from central support.' : 'No unresolved support concerns.' }}
                            </p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $billingClasses }}">
                                {{ $snapshot['billing_label'] }}
                            </span>
                            <p class="mt-2 text-xs text-slate-500">{{ $snapshot['billing_detail'] }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">No tenant health signals available yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rounded-2xl border border-white/[0.07] bg-white/[0.02] overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.07]">
        <h3 class="font-heading text-base font-semibold text-white">Recent Tenants</h3>
        <a href="{{ route('central.tenants.index', absolute: false) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            Manage Tenants
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-[#0F1729] z-10">
                <tr class="border-b border-white/[0.06]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cooperative Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Domain</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($recentTenants as $tenant)
                @php
                    $statusConfig = match ($tenant->status) {
                        'active'    => ['class' => 'bg-emerald-500/15 text-emerald-300', 'label' => 'Active'],
                        'overdue'   => ['class' => 'bg-red-500/15 text-red-300', 'label' => 'Overdue'],
                        'suspended' => ['class' => 'bg-amber-500/15 text-amber-300', 'label' => 'Suspended'],
                        default     => ['class' => 'bg-slate-500/15 text-slate-300', 'label' => ucfirst($tenant->status)],
                    };
                    $planConfig = match (strtolower($tenant->plan?->name ?? '')) {
                        'basic'    => 'bg-blue-500/15 text-blue-300',
                        'standard' => 'bg-amber-500/15 text-amber-300',
                        'premium'  => 'bg-emerald-500/15 text-emerald-300',
                        default    => 'bg-slate-500/15 text-slate-300',
                    };
                @endphp
                <tr class="transition hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3 font-semibold text-white">{{ $tenant->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $planConfig }}">{{ $tenant->plan?->name ?? 'No Plan' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ $tenant->getFullDomain() }}" target="_blank" class="text-emerald-400 hover:text-emerald-300 transition">
                            {{ parse_url($tenant->getFullDomain(), PHP_URL_HOST) }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-400">{{ $tenant->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">No tenants found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
