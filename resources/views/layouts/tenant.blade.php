<?php
    $pageTitle = trim($__env->yieldContent('title', 'Tenant App')) ?: 'Tenant App';
    $user = auth()->user();
    $tenantModel = tenant();
    $tenantName = $tenantModel?->name ?? 'PayMonitor';
    $tenantHost = request()->getHost();
    $tenantParameter = ['tenant' => $tenantModel?->id ?? request()->route('tenant')];
    $tenantSettings = \App\Models\TenantSetting::allKeyed();
    $accentPalette = [
        'green' => ['hex' => '#22c55e', 'hover' => '#16a34a', 'rgb' => '34, 197, 94'],
        'blue' => ['hex' => '#3b82f6', 'hover' => '#2563eb', 'rgb' => '59, 130, 246'],
        'indigo' => ['hex' => '#6366f1', 'hover' => '#4f46e5', 'rgb' => '99, 102, 241'],
        'purple' => ['hex' => '#a855f7', 'hover' => '#9333ea', 'rgb' => '168, 85, 247'],
        'teal' => ['hex' => '#14b8a6', 'hover' => '#0f766e', 'rgb' => '20, 184, 166'],
    ];
    $accentColor = $tenantSettings['accent_color'] ?? 'green';
    $accentConfig = $accentPalette[$accentColor] ?? $accentPalette['green'];
    $accentHex = $accentConfig['hex'];
    $accentHover = $accentConfig['hover'];
    $accentRgb = $accentConfig['rgb'];
    $tagline = $tenantSettings['cooperative_tagline'] ?? '';
    $logoPath = $tenantSettings['logo_path'] ?? null;
    $logoUrl = filled($logoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $logoPath, '/')], false)
        : null;
    $faviconUrl = $logoUrl !== null
        ? $logoUrl.'?v='.rawurlencode((string) $logoPath)
        : asset('favicon.ico');
    $latestVersion = null;
    $latestVersionAcknowledged = false;
    try {
        $centralConnection = config('tenancy.database.central_connection');
        $hasVersionTables = \Illuminate\Support\Facades\Schema::connection($centralConnection)->hasTable('app_versions')
            && \Illuminate\Support\Facades\Schema::connection($centralConnection)->hasTable('tenant_version_acknowledgements');

        if ($hasVersionTables && $tenantModel !== null) {
            $latestVersion = \App\Models\AppVersion::latestActive();

            if ($latestVersion !== null) {
                $latestVersionAcknowledged = \App\Models\TenantVersionAcknowledgement::query()
                    ->where('tenant_id', $tenantModel->id)
                    ->where('version_id', $latestVersion->id)
                    ->exists();
            }
        }
    } catch (\Throwable) {
        $latestVersion = null;
        $latestVersionAcknowledged = false;
    }
    $roleName = $user?->getRoleNames()->first() ?? 'viewer';
    $roleDisplay = match ($roleName) {
        'tenant_admin' => 'Administrator',
        'branch_manager' => 'Branch Manager',
        'loan_officer' => 'Loan Officer',
        'cashier' => 'Cashier',
        default => 'Viewer',
    };
    $navItemClass = static function (bool $active): string {
        return $active
            ? 'tenant-nav-item-active group flex items-center gap-3 rounded-md border-l-[3px] px-4 py-3 text-sm font-medium text-white'
            : 'group flex items-center gap-3 rounded-md border-l-[3px] border-transparent px-4 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-white';
    };
    $navIconClass = static function (bool $active): string {
        return $active ? 'tenant-nav-icon-active' : 'text-slate-500 transition group-hover:text-slate-300';
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
    <title>{{ $pageTitle }} | {{ $tenantName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['"Plus Jakarta Sans"', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
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
            --pm-accent: {{ $accentHex }};
            --pm-accent-hover: {{ $accentHover }};
            --pm-accent-rgb: {{ $accentRgb }};
        }

        .tenant-nav-item-active {
            border-left-color: var(--pm-accent);
            background-color: rgba(var(--pm-accent-rgb), 0.12);
        }

        .tenant-nav-item-active:hover {
            background-color: rgba(var(--pm-accent-rgb), 0.16);
        }

        .tenant-nav-icon-active {
            color: var(--pm-accent);
        }

        .tenant-sidebar-scroll {
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.42) transparent;
        }

        .tenant-sidebar-scroll::-webkit-scrollbar {
            width: 10px;
        }

        .tenant-sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .tenant-sidebar-scroll::-webkit-scrollbar-thumb {
            border: 2px solid transparent;
            border-radius: 9999px;
            background: rgba(148, 163, 184, 0.42);
            background-clip: padding-box;
        }

        .tenant-sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.58);
            background-clip: padding-box;
        }

        .accent-bg {
            background-color: var(--pm-accent) !important;
        }

        .accent-text {
            color: var(--pm-accent) !important;
        }

        .accent-border {
            border-color: rgba(var(--pm-accent-rgb), 0.35) !important;
        }

        .accent-soft-bg {
            background-color: rgba(var(--pm-accent-rgb), 0.1) !important;
        }

        .accent-soft-border {
            border-color: rgba(var(--pm-accent-rgb), 0.18) !important;
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
            box-shadow: 0 0 0 0.25rem rgba(var(--pm-accent-rgb), 0.18) !important;
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
            box-shadow: 0 0 0 0.25rem rgba(var(--pm-accent-rgb), 0.18);
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
            background-color: rgba(var(--pm-accent-rgb), 0.1) !important;
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
            background-color: rgba(var(--pm-accent-rgb), 0.1);
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

        .legacy-content .table-responsive {
            overflow-x: auto;
            overscroll-behavior-x: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.38) transparent;
        }

        .legacy-content .table-responsive::-webkit-scrollbar {
            height: 10px;
        }

        .legacy-content .table-responsive::-webkit-scrollbar-track {
            background: transparent;
        }

        .legacy-content .table-responsive::-webkit-scrollbar-thumb {
            border: 2px solid transparent;
            border-radius: 9999px;
            background: rgba(148, 163, 184, 0.38);
            background-clip: padding-box;
        }

        .legacy-content .table-responsive::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.54);
            background-clip: padding-box;
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
            min-width: 100%;
            width: max-content;
            table-layout: auto;
        }

        .legacy-content .table > :not(caption) > * > * {
            background-color: transparent;
            border-bottom-color: var(--pm-border);
            color: inherit;
            padding: 0.95rem 1rem;
            font-size: 0.95rem;
            line-height: 1.45;
            white-space: nowrap;
            vertical-align: middle;
        }

        .legacy-content table thead,
        .legacy-content table thead tr,
        .legacy-content table thead th {
            background-color: var(--pm-card-bg) !important;
            color: var(--pm-text-muted) !important;
            border-color: var(--pm-border) !important;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .legacy-content table tbody tr {
            border-color: var(--pm-border) !important;
            transition: background-color 0.2s ease;
        }

        .legacy-content table tbody tr:hover {
            background-color: #1f2937 !important;
        }

        .legacy-content .table th.text-end,
        .legacy-content .table td.text-end,
        .legacy-content .table th.text-center,
        .legacy-content .table td.text-center {
            text-align: center !important;
        }

        .legacy-content .table .btn-group,
        .legacy-content .table .btn-group-sm {
            flex-wrap: nowrap;
            white-space: nowrap;
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

        <aside class="fixed inset-y-0 left-0 z-50 w-56 overflow-hidden border-r border-white/[0.06] bg-[#0A1628] px-4 py-6 transition-transform duration-200 md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div class="flex h-full min-h-0 flex-col">
                <div class="tenant-sidebar-scroll min-h-0 flex-1 pr-2">
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-4">
                        <div class="flex items-center gap-3">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $tenantName }} logo" class="h-9 w-9 rounded-xl object-cover ring-1 ring-white/10 flex-shrink-0">
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl shadow-lg flex-shrink-0" style="background: linear-gradient(135deg, var(--pm-accent), var(--pm-accent-hover)); box-shadow: 0 16px 36px rgba(var(--pm-accent-rgb), 0.22);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-heading text-[15px] font-bold tracking-tight text-white leading-tight" title="{{ $tenantName }}">{{ $tenantName }}</p>
                                <p class="truncate text-[10px] uppercase tracking-[0.16em] accent-text" style="opacity: 0.72;" title="{{ $tenantHost }}">{{ $tenantHost }}</p>
                                @if(filled($tagline))
                                    <p class="mt-1 text-[11px] leading-5 text-slate-500">{{ $tagline }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <p class="px-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Overview</p>
                        <nav class="mt-3 space-y-1.5">
                            @php($dashboardActive = request()->routeIs('dashboard'))
                            <a href="{{ route('dashboard', $tenantParameter, false) }}" class="{{ $navItemClass($dashboardActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($dashboardActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12a8.25 8.25 0 1 1 16.5 0v6.75a1.5 1.5 0 0 1-1.5 1.5h-3.75v-6h-6v6H5.25a1.5 1.5 0 0 1-1.5-1.5V12Z"/></svg>
                                <span>Dashboard</span>
                            </a>
                        </nav>
                    </div>

                    <div class="mt-6">
                                        <p class="px-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Lending</p>
                        <nav class="mt-3 space-y-1.5">
                            @php($membersActive = request()->routeIs('members.*'))
                            <a href="{{ route('members.index', $tenantParameter, false) }}" class="{{ $navItemClass($membersActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($membersActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM5.25 18a5.25 5.25 0 0 1 10.5 0"/></svg>
                                <span>Members</span>
                            </a>
                            @php($loansActive = request()->routeIs('loans.*'))
                            <a href="{{ route('loans.index', $tenantParameter, false) }}" class="{{ $navItemClass($loansActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($loansActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                <span>Loans</span>
                            </a>
                            @php($loanTypesActive = request()->routeIs('loan-types.*'))
                            <a href="{{ route('loan-types.index', $tenantParameter, false) }}" class="{{ $navItemClass($loanTypesActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($loanTypesActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9A1.5 1.5 0 0 1 18 6v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18V6A1.5 1.5 0 0 1 7.5 4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9h6M9 12h6M9 15h3.75"/></svg>
                                <span>Loan Types</span>
                            </a>
                            @php($paymentsActive = request()->routeIs('loan-payments.*'))
                            <a href="{{ route('loan-payments.index', $tenantParameter, false) }}" class="{{ $navItemClass($paymentsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($paymentsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-12 4.5h3"/></svg>
                                <span>Payments</span>
                            </a>
                        </nav>
                    </div>

                    @role('tenant_admin')
                        <div class="mt-6">
                            <p class="px-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Management</p>
                            <nav class="mt-3 space-y-1.5">
                                @php($branchesActive = request()->routeIs('branches.*'))
                                <a href="{{ route('branches.index', $tenantParameter, false) }}" class="{{ $navItemClass($branchesActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($branchesActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h4.5v4.5H4.5v-4.5Zm0 6h4.5v4.5H4.5v-4.5Zm10.5-6h4.5v4.5H15v-4.5Zm0 6h4.5v4.5H15v-4.5Z"/></svg>
                                    <span>Branches</span>
                                </a>
                                @php($usersActive = request()->routeIs('users.*'))
                                <a href="{{ route('users.index', $tenantParameter, false) }}" class="{{ $navItemClass($usersActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($usersActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM5.25 18a5.25 5.25 0 0 1 10.5 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 8.25h3m-1.5-1.5v3"/></svg>
                                    <span>Users</span>
                                </a>
                            </nav>
                        </div>
                    @endrole

                    <div class="mt-6">
                        <p class="px-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Insights</p>
                        <nav class="mt-3 space-y-1.5">
                            @php($reportsActive = request()->routeIs('reports.*'))
                            <a href="{{ route('reports.index', $tenantParameter, false) }}" class="{{ $navItemClass($reportsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($reportsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5h15"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 16.5v-4.5M12 16.5V9M16.5 16.5V6"/></svg>
                                <span>Reports</span>
                            </a>
                        </nav>
                    </div>

                    <div class="mt-6 border-t border-white/[0.06] pt-6">
                        <p class="px-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Workspace</p>
                        <nav class="mt-3 space-y-1.5">
                            @php($settingsActive = request()->routeIs('settings.*'))
                            <a href="{{ route('settings.index', $tenantParameter, false) }}" class="{{ $navItemClass($settingsActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($settingsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3m-7.72 1.22 2.12-2.12m8.64 0 2.12 2.12M18 10.5v3m-1.22 7.72-2.12-2.12m-8.64 0-2.12 2.12M6 13.5v-3m6 1.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z" />
                                </svg>
                                <span>Settings</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="mt-4 shrink-0 border-t border-white/[0.1] pt-4">
                    <a href="{{ route('tenant.logout', $tenantParameter, false) }}" class="group flex w-full items-center gap-3 rounded-md border-l-[3px] border-transparent px-4 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-white">
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
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-white">{{ $user?->name ?? 'Tenant User' }}</p>
                            <p class="text-xs text-zinc-500">{{ $roleDisplay }}</p>
                        </div>
                        <span class="hidden rounded-full px-3 py-1 text-xs font-medium sm:inline-flex" style="border: 1px solid rgba(var(--pm-accent-rgb), 0.28); background-color: rgba(var(--pm-accent-rgb), 0.12); color: var(--pm-accent);" title="{{ $tenantName }}">{{ $tenantName }}</span>
                    </div>
                </div>
            </header>

            <main class="min-h-screen bg-[#0d1117] p-6 pt-24">
                @if($latestVersion && ! $latestVersionAcknowledged)
                    <div x-data="{ showChangelog: false }" class="mb-6">
                        <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-4 text-indigo-200">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold">New update available: v{{ $latestVersion->version_number }} — {{ $latestVersion->title }}</p>
                                    <p class="mt-1 text-sm text-indigo-200/80">Review the changelog and mark it as updated once your tenant has acknowledged the release.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" x-on:click="showChangelog = true" class="inline-flex items-center gap-2 rounded-lg border border-indigo-400/25 px-4 py-2 text-sm font-medium text-indigo-100 transition hover:border-indigo-300/40 hover:bg-indigo-400/10">
                                        View Changelog
                                    </button>
                                    <form method="POST" action="{{ route('settings.acknowledge', [...$tenantParameter, 'version' => $latestVersion], false) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-400/20 px-4 py-2 text-sm font-medium text-indigo-100 transition hover:bg-indigo-400/30">
                                            Dismiss
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div x-show="showChangelog" x-transition class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" style="display: none;">
                            <div class="w-full max-w-2xl rounded-2xl border border-white/[0.08] bg-[#0f1319] shadow-2xl">
                                <div class="flex items-start justify-between gap-4 border-b border-white/[0.06] px-6 py-5">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Update Notice</p>
                                        <h2 class="mt-2 font-heading text-2xl font-bold text-white">v{{ $latestVersion->version_number }} — {{ $latestVersion->title }}</h2>
                                        <p class="mt-1 text-sm text-slate-500">Released {{ $latestVersion->released_at?->format('M d, Y') ?? 'Not scheduled' }}</p>
                                    </div>
                                    <button type="button" x-on:click="showChangelog = false" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 text-slate-400 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="px-6 py-5">
                                    <h3 class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Changelog</h3>
                                    <ul class="mt-4 space-y-3 text-sm text-slate-300">
                                        @foreach($latestVersion->changelog_items as $change)
                                            <li class="flex gap-3">
                                                <span class="mt-1.5 h-2 w-2 rounded-full bg-indigo-300"></span>
                                                <span>{{ $change }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($flashMessages->isNotEmpty())
                    <div class="space-y-3">
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

                <div class="legacy-content mt-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
