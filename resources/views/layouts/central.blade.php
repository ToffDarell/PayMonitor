<?php
    $pageTitle = trim($__env->yieldContent('title', 'Central App')) ?: 'Central App';
    $user = auth()->user();
    $navItemClass = static function (bool $active): string {
        return $active
            ? 'group flex items-center gap-3 rounded-md border-l-[3px] border-emerald-500 bg-emerald-500/[0.08] px-3 py-2 text-sm font-medium text-white'
            : 'group flex items-center gap-3 rounded-md border-l-[3px] border-transparent px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-white';
    };
    $navIconClass = static function (bool $active): string {
        return $active ? 'text-emerald-400' : 'text-slate-500 transition group-hover:text-slate-300';
    };
    $flashMessages = collect([
        ['key' => 'success', 'message' => session('success')],
        ['key' => 'error', 'message' => session('error')],
        ['key' => 'warning', 'message' => session('warning')],
        ['key' => 'success', 'message' => session('status')],
    ])->filter(fn (array $flash): bool => filled($flash['message']))->values();
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} | PayMonitor Central</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['Figtree', 'sans-serif'],
                        sans: ['Figtree', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    <style>
        :root {
            --pm-page-bg: #0d1117;
            --pm-card-bg: #161b22;
            --pm-surface-bg: #0f1319;
            --pm-border: #21262d;
            --pm-border-hover: #30363d;
            --pm-text-primary: #ffffff;
            --pm-text-secondary: #e2e8f0;
            --pm-text-muted: #8b949e;
            --pm-text-subtle: #52525b;
            --pm-accent: #22c55e;
            --pm-accent-hover: #16a34a;
        }

        .legacy-content {
            color: var(--pm-text-secondary);
        }

        .legacy-content .card,
        .legacy-content .modal-content,
        .legacy-content .dropdown-menu,
        .legacy-content .bg-white,
        .legacy-content .bg-light,
        .legacy-content .bg-gray-50,
        .legacy-content .bg-gray-100,
        .legacy-content .bg-slate-50,
        .legacy-content .bg-slate-100 {
            background-color: var(--pm-card-bg) !important;
            color: var(--pm-text-secondary);
            border-color: var(--pm-border) !important;
        }

        .legacy-content .card,
        .legacy-content .modal-content {
            border-radius: 1rem;
            box-shadow: 0 20px 45px rgba(2, 6, 23, 0.2) !important;
        }

        .legacy-content h1,
        .legacy-content .h1 {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
        }

        .legacy-content h2,
        .legacy-content .h2 {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
        }

        .legacy-content h3,
        .legacy-content .h3 {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
        }

        .legacy-content .card.border-0 {
            border: 1px solid var(--pm-border) !important;
        }

        .legacy-content .card-header,
        .legacy-content .card-footer,
        .legacy-content .table-light,
        .legacy-content .table-light > :not(caption) > * > * {
            background-color: var(--pm-card-bg) !important;
            color: var(--pm-text-muted) !important;
            border-color: var(--pm-border) !important;
        }

        .legacy-content .border,
        .legacy-content .border-top,
        .legacy-content .border-end,
        .legacy-content .border-bottom,
        .legacy-content .border-start,
        .legacy-content .border-gray-200,
        .legacy-content .border-gray-300,
        .legacy-content .border-slate-200 {
            border-color: var(--pm-border) !important;
        }

        .legacy-content .card .border.rounded-3,
        .legacy-content .card .border.rounded-4,
        .legacy-content .card .border.rounded-xl,
        .legacy-content .card .form-check.border {
            background-color: var(--pm-surface-bg) !important;
            border-color: var(--pm-border) !important;
        }

        .legacy-content .text-muted,
        .legacy-content .text-gray-500,
        .legacy-content .text-gray-600,
        .legacy-content .text-slate-500,
        .legacy-content .text-slate-600,
        .legacy-content .text-slate-400 {
            color: var(--pm-text-muted) !important;
        }

        .legacy-content .text-gray-400 {
            color: #71717a !important;
        }

        .legacy-content .text-gray-700,
        .legacy-content .text-slate-700 {
            color: var(--pm-text-secondary) !important;
        }

        .legacy-content .text-gray-800,
        .legacy-content .text-gray-900,
        .legacy-content .text-slate-900 {
            color: var(--pm-text-primary) !important;
        }

        .legacy-content a:not(.btn):not(.badge):not(.dropdown-item) {
            color: #93c5fd;
        }

        .legacy-content a:not(.btn):not(.badge):not(.dropdown-item):hover {
            color: #bfdbfe;
        }

        .legacy-content label,
        .legacy-content .form-label {
            color: var(--pm-text-secondary);
        }

        .legacy-content input:not([type='hidden']):not([type='checkbox']):not([type='radio']):not([type='range']):not([type='file']),
        .legacy-content select,
        .legacy-content textarea,
        .legacy-content .form-control,
        .legacy-content .form-select,
        .legacy-content .input-group-text {
            background-color: var(--pm-surface-bg) !important;
            border-color: var(--pm-border) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content select option {
            background-color: var(--pm-surface-bg);
            color: var(--pm-text-primary);
        }

        .legacy-content input:not([type='hidden']):not([type='checkbox']):not([type='radio'])::placeholder,
        .legacy-content textarea::placeholder,
        .legacy-content .placeholder-gray-400::placeholder,
        .legacy-content .placeholder-slate-400::placeholder {
            color: var(--pm-text-subtle) !important;
        }

        .legacy-content input:not([type='hidden']):not([type='checkbox']):not([type='radio']):focus,
        .legacy-content select:focus,
        .legacy-content textarea:focus,
        .legacy-content .form-control:focus,
        .legacy-content .form-select:focus,
        .legacy-content .focus\:border-blue-500:focus {
            background-color: var(--pm-surface-bg) !important;
            border-color: var(--pm-accent) !important;
            color: var(--pm-text-primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.18) !important;
        }

        .legacy-content .form-check-input {
            background-color: var(--pm-surface-bg);
            border-color: var(--pm-border);
        }

        .legacy-content .form-check-input:checked {
            background-color: var(--pm-accent);
            border-color: var(--pm-accent);
        }

        .legacy-content .form-check-input:focus {
            border-color: var(--pm-accent);
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.18);
        }

        .legacy-content .input-group-text {
            color: var(--pm-text-muted) !important;
        }

        .legacy-content .btn {
            border-radius: 0.75rem;
            font-weight: 500;
        }

        .legacy-content .btn-primary {
            background-color: var(--pm-accent) !important;
            border-color: var(--pm-accent) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .btn-primary:hover,
        .legacy-content .btn-primary:focus,
        .legacy-content .btn-primary:active {
            background-color: var(--pm-accent-hover) !important;
            border-color: var(--pm-accent-hover) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .btn-outline-secondary,
        .legacy-content .btn-outline-success,
        .legacy-content .btn-outline-info {
            background-color: transparent !important;
            border-color: var(--pm-border) !important;
            color: var(--pm-text-muted) !important;
        }

        .legacy-content .btn-outline-secondary:hover,
        .legacy-content .btn-outline-secondary:focus,
        .legacy-content .btn-outline-success:hover,
        .legacy-content .btn-outline-success:focus,
        .legacy-content .btn-outline-info:hover,
        .legacy-content .btn-outline-info:focus {
            background-color: rgba(34, 197, 94, 0.1) !important;
            border-color: var(--pm-accent) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .btn-light,
        .legacy-content .btn-light.border,
        .legacy-content form .btn-outline-primary {
            background-color: var(--pm-border) !important;
            border-color: var(--pm-border-hover) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .btn-light:hover,
        .legacy-content .btn-light:focus,
        .legacy-content form .btn-outline-primary:hover,
        .legacy-content form .btn-outline-primary:focus {
            background-color: var(--pm-border-hover) !important;
            border-color: var(--pm-border-hover) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .btn-outline-primary {
            background-color: transparent;
            border-color: var(--pm-border) !important;
            color: var(--pm-text-muted) !important;
        }

        .legacy-content .btn-outline-primary:hover,
        .legacy-content .btn-outline-primary:focus {
            background-color: rgba(34, 197, 94, 0.1);
            border-color: var(--pm-accent) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .btn-outline-danger {
            background-color: transparent !important;
            border-color: rgba(248, 113, 113, 0.35) !important;
            color: #fca5a5 !important;
        }

        .legacy-content .btn-outline-danger:hover,
        .legacy-content .btn-outline-danger:focus {
            background-color: rgba(239, 68, 68, 0.12) !important;
            border-color: rgba(248, 113, 113, 0.55) !important;
            color: #fecaca !important;
        }

        .legacy-content table {
            color: var(--pm-text-secondary);
            border-color: var(--pm-border);
        }

        .legacy-content .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--pm-text-secondary);
            --bs-table-striped-bg: #11161d;
            --bs-table-striped-color: var(--pm-text-secondary);
            --bs-table-hover-bg: #1f2937;
            --bs-table-hover-color: var(--pm-text-primary);
            --bs-table-border-color: var(--pm-border);
            margin-bottom: 0;
        }

        .legacy-content .table > :not(caption) > * > * {
            background-color: transparent;
            border-bottom-color: var(--pm-border);
            color: inherit;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            line-height: 1.45;
        }

        .legacy-content table thead,
        .legacy-content table thead tr,
        .legacy-content table thead th {
            background-color: var(--pm-card-bg) !important;
            color: var(--pm-text-muted) !important;
            border-color: var(--pm-border) !important;
        }

        .legacy-content table tbody tr {
            border-color: var(--pm-border) !important;
            transition: background-color 0.2s ease;
        }

        .legacy-content table tbody tr:hover {
            background-color: #1f2937 !important;
        }

        .legacy-content .divide-gray-200 > :not([hidden]) ~ :not([hidden]),
        .legacy-content .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: var(--pm-border) !important;
        }

        .legacy-content .badge.bg-light,
        .legacy-content .badge.text-dark {
            background-color: rgba(139, 148, 158, 0.12) !important;
            border: 1px solid var(--pm-border);
            color: var(--pm-text-secondary) !important;
        }

        .legacy-content .shadow,
        .legacy-content .shadow-sm {
            box-shadow: 0 20px 45px rgba(2, 6, 23, 0.18) !important;
        }

        .legacy-content .pagination {
            --bs-pagination-bg: var(--pm-card-bg);
            --bs-pagination-border-color: var(--pm-border);
            --bs-pagination-color: var(--pm-text-muted);
            --bs-pagination-hover-color: var(--pm-text-primary);
            --bs-pagination-hover-bg: var(--pm-border-hover);
            --bs-pagination-hover-border-color: var(--pm-border-hover);
            --bs-pagination-active-bg: var(--pm-accent);
            --bs-pagination-active-border-color: var(--pm-accent);
            --bs-pagination-active-color: var(--pm-text-primary);
            --bs-pagination-disabled-bg: var(--pm-card-bg);
            --bs-pagination-disabled-color: var(--pm-text-subtle);
            --bs-pagination-disabled-border-color: var(--pm-border);
        }
    </style>

    @vite(['resources/css/paymonitor.css', 'resources/js/paymonitor-dashboard.js'])
</head>
<body class="min-h-screen bg-[#060B18] text-[#F1F5F9] antialiased" x-data="{ sidebarOpen: false }">
    <div class="relative min-h-screen">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/70 md:hidden" x-on:click="sidebarOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-50 w-56 border-r border-white/[0.06] bg-[#0A1628] px-4 py-6 transition-transform duration-200 md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div class="flex h-full flex-col">
                <div>
                    <div class="overflow-hidden rounded-2xl border border-white/5 bg-white/[0.02] p-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-heading text-base font-bold tracking-tight text-white leading-tight">PayMonitor</p>
                                <div class="mt-1">
                                    <span class="inline-flex shrink-0 rounded-full border border-emerald-400/30 bg-emerald-500/10 px-2.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.18em] text-emerald-300">Central</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <p class="px-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Management</p>
                        <nav class="mt-3 space-y-1.5">
                            @php($dashboardActive = request()->routeIs('central.dashboard'))
                            <a href="{{ route('central.dashboard', absolute: false) }}" class="{{ $navItemClass($dashboardActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($dashboardActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12a8.25 8.25 0 1 1 16.5 0v6.75a1.5 1.5 0 0 1-1.5 1.5h-3.75v-6h-6v6H5.25a1.5 1.5 0 0 1-1.5-1.5V12Z"/></svg>
                                <span class="flex-1">Dashboard</span>
                            </a>
                            @php($applicationsActive = request()->routeIs('central.applications.*'))
                            @php($pendingApplicationsCount = \App\Models\TenantApplication::where('status', 'pending')->count())
                            <a href="{{ route('central.applications.index', absolute: false) }}" class="{{ $navItemClass($applicationsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($applicationsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.84 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                                </svg>
                                <span class="flex-1">Applications</span>
                                @if($pendingApplicationsCount > 0)
                                    <span class="inline-flex items-center justify-center rounded-full bg-emerald-500/20 px-2 py-0.5 text-xs font-medium text-emerald-400">
                                        {{ $pendingApplicationsCount }}
                                    </span>
                                @endif
                            </a>
                            @php($tenantsActive = request()->routeIs('central.tenants.*'))
                            <a href="{{ route('central.tenants.index', absolute: false) }}" class="{{ $navItemClass($tenantsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($tenantsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h4.5v4.5H4.5v-4.5Zm0 6h4.5v4.5H4.5v-4.5Zm10.5-6h4.5v4.5H15v-4.5Zm0 6h4.5v4.5H15v-4.5Z"/></svg>
                                <span>Tenants</span>
                            </a>
                            @php($plansActive = request()->routeIs('central.plans.*'))
                            <a href="{{ route('central.plans.index', absolute: false) }}" class="{{ $navItemClass($plansActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($plansActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 5.25h12A1.5 1.5 0 0 1 19.5 6.75v10.5A1.5 1.5 0 0 1 18 18.75H6A1.5 1.5 0 0 1 4.5 17.25V6.75A1.5 1.5 0 0 1 6 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9h7.5M8.25 12h7.5M8.25 15h4.5"/></svg>
                                <span>Plans</span>
                            </a>
                            @php($paymentsActive = request()->routeIs('central.payments.*'))
                            <a href="{{ route('central.payments.index', absolute: false) }}" class="{{ $navItemClass($paymentsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($paymentsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-12 4.5h3"/></svg>
                                <span>Payments</span>
                            </a>
                            @php($billingActive = request()->routeIs('central.billing.*'))
                            <a href="{{ route('central.billing.index', absolute: false) }}" class="{{ $navItemClass($billingActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($billingActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9A1.5 1.5 0 0 1 18 6v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18V6A1.5 1.5 0 0 1 7.5 4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9h7.5M8.25 12h7.5M8.25 15h4.5"/></svg>
                                <span>Billing</span>
                            </a>
                            @php($versionsActive = request()->routeIs('central.versions.*'))
                            <a href="{{ route('central.versions.index', absolute: false) }}" class="{{ $navItemClass($versionsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($versionsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9A1.5 1.5 0 0 1 18 6v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18V6A1.5 1.5 0 0 1 7.5 4.5Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9h6M9 12h6M9 15h3.75"/>
                                </svg>
                                <span>Versions</span>
                            </a>
                            @php($supportActive = request()->routeIs('central.support.*'))
                            @php($openSupportCount = \App\Models\SupportRequest::where('status', 'open')->count())
                            <a href="{{ route('central.support.index', absolute: false) }}" class="{{ $navItemClass($supportActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($supportActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                                </svg>
                                <span class="flex-1">Support</span>
                                @if($openSupportCount > 0)
                                    <span class="inline-flex items-center justify-center rounded-full bg-yellow-500/20 px-2 py-0.5 text-xs font-medium text-yellow-400">
                                        {{ $openSupportCount }}
                                    </span>
                                @endif
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="mt-4 shrink-0 border-t border-white/[0.1] pt-4">
                    <a href="{{ route('central.logout', absolute: false) }}" class="group flex w-full items-center gap-3 rounded-md border-l-[3px] border-transparent px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-white">
                        <svg class="h-5 w-5 text-slate-500 transition group-hover:text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5V5.25A2.25 2.25 0 0 0 12.75 3h-6A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21h6A2.25 2.25 0 0 0 15 18.75V16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 15 3-3m0 0-3-3m3 3H9"/></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <div class="md:pl-56">
            <header class="fixed left-0 right-0 top-0 z-30 border-b border-white/[0.06] bg-[#060B18]/80 backdrop-blur md:left-56">
                <div class="flex h-16 items-center justify-between px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 text-zinc-300 transition hover:border-white/20 hover:bg-white/[0.04] md:hidden" x-on:click="sidebarOpen = true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        <div>
                            <h1 class="font-heading text-xl font-bold tracking-tight text-white">{{ $pageTitle }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300 sm:inline-flex">Central App</span>
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-white">{{ $user?->name ?? 'Super Admin' }}</p>
                            <p class="text-xs text-zinc-500">Administrator</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-h-screen bg-[#0d1117] p-6 pt-24">
                @if($flashMessages->isNotEmpty())
                    <div class="mx-auto max-w-7xl space-y-3">
                        @foreach($flashMessages as $flash)
                            <?php
                                $flashStyle = match ($flash['key']) {
                                    'success' => 'border-l-green-500 bg-green-500/10 text-green-100',
                                    'error' => 'border-l-red-500 bg-red-500/10 text-red-100',
                                    default => 'border-l-amber-400 bg-amber-500/10 text-amber-100',
                                };
                            ?>
                            <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 4000)" x-show="visible" x-transition.opacity.duration.300ms class="rounded-xl border border-white/10 border-l-4 px-4 py-3 text-sm {{ $flashStyle }}">
                                {{ $flash['message'] }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="legacy-content mx-auto mt-6 max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
