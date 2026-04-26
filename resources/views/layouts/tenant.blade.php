<?php
    $rawPageTitle = trim((string) $__env->yieldContent('title', 'Tenant App'));
    $pageTitle = html_entity_decode($rawPageTitle !== '' ? $rawPageTitle : 'Tenant App', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $user = auth()->user();
    $tenantModel = tenant();
    $tenantName = $tenantModel?->name ?? 'PayMonitor';
    $tenantSupportsAuditLogs = $tenantModel?->supportsAuditLogs() ?? false;
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
    $themeMode = $tenantSettings['theme_mode'] ?? 'dark';
    $fontScale = $tenantSettings['font_scale'] ?? 'comfortable';
    $fontScaleBase = match ($fontScale) {
        'compact' => 14,
        'large' => 18,
        default => 16,
    };
    $themePalette = [
        'dark' => [
            'shell_bg' => '#060B18',
            'page_bg' => '#0d1117',
            'card_bg' => '#161b22',
            'surface_bg' => '#0f1319',
            'input_bg' => '#0f1319',
            'input_border' => '#334155',
            'sidebar_bg' => '#0A1628',
            'header_bg' => 'rgba(6, 11, 24, 0.82)',
            'border' => '#21262d',
            'border_hover' => '#30363d',
            'text_primary' => '#ffffff',
            'text_secondary' => '#e2e8f0',
            'text_muted' => '#8b949e',
            'text_subtle' => '#52525b',
            'panel_bg' => 'rgba(255,255,255,0.02)',
            'panel_border' => 'rgba(255,255,255,0.07)',
            'nav_text' => '#94a3b8',
            'nav_hover_bg' => 'rgba(255,255,255,0.04)',
            'nav_hover_text' => '#ffffff',
            'table_striped_bg' => '#11161d',
            'table_hover_bg' => '#1f2937',
        ],
        'light' => [
            'shell_bg' => '#e5edf5',
            'page_bg' => '#f8fafc',
            'card_bg' => '#ffffff',
            'surface_bg' => '#f1f5f9',
            'input_bg' => '#ffffff',
            'input_border' => '#b8c5d6',
            'sidebar_bg' => '#ffffff',
            'header_bg' => 'rgba(248, 250, 252, 0.9)',
            'border' => '#dbe4ee',
            'border_hover' => '#cbd5e1',
            'text_primary' => '#0f172a',
            'text_secondary' => '#1e293b',
            'text_muted' => '#64748b',
            'text_subtle' => '#94a3b8',
            'panel_bg' => '#ffffff',
            'panel_border' => 'rgba(148,163,184,0.28)',
            'nav_text' => '#475569',
            'nav_hover_bg' => 'rgba(15,23,42,0.05)',
            'nav_hover_text' => '#0f172a',
            'table_striped_bg' => '#f1f5f9',
            'table_hover_bg' => '#e2e8f0',
        ],
    ];
    $themeConfig = $themePalette[$themeMode] ?? $themePalette['dark'];
    $tagline = $tenantSettings['cooperative_tagline'] ?? '';
    $logoPath = $tenantSettings['logo_path'] ?? null;
    $logoUrl = filled($logoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $logoPath, '/')], false)
        : null;
    $faviconUrl = $logoUrl !== null
        ? $logoUrl.'?v='.rawurlencode((string) $logoPath)
        : asset('favicon.ico');
    $updateInfo = [
        'update_available' => false,
        'latest_version' => 'Unknown',
        'release_name' => 'Unable to check',
    ];

    try {
        $tenantId = (string) ($tenantModel?->id ?? request()->route('tenant'));
        $tenantUpdateService = app(\App\Services\TenantUpdateService::class);
        $availableUpdates = $tenantUpdateService->getAvailableUpdates($tenantId);
        $latestAvailable = $availableUpdates[0] ?? null;

        if (is_array($latestAvailable)) {
            $updateInfo = [
                'update_available' => true,
                'latest_version' => (string) ($latestAvailable['tag'] ?? $latestAvailable['version'] ?? 'Unknown'),
                'release_name' => (string) ($latestAvailable['title'] ?? 'Update available'),
            ];
        }
    } catch (\Throwable) {
        $updateInfo = [
            'update_available' => false,
            'latest_version' => 'Unknown',
            'release_name' => 'Unable to check',
        ];
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
            ? 'tenant-nav-item tenant-nav-item-active group flex items-center gap-3 rounded-md border-l-[3px] px-3 py-2 text-sm font-medium'
            : 'tenant-nav-item group flex items-center gap-3 rounded-md border-l-[3px] border-transparent px-3 py-2 text-sm font-medium transition';
    };
    $navIconClass = static function (bool $active): string {
        return $active ? 'tenant-nav-icon tenant-nav-icon-active' : 'tenant-nav-icon transition';
    };
    $flashMessages = collect([
        ['key' => 'success', 'message' => session('success')],
        ['key' => 'error', 'message' => session('error')],
        ['key' => 'warning', 'message' => session('warning')],
        ['key' => 'success', 'message' => session('status')],
    ])->filter(fn (array $flash): bool => filled($flash['message']))->values();

    $billingUnpaidCount = 0;

    if ($tenantModel !== null) {
        try {
            $billingUnpaidCount = \App\Models\BillingInvoice::query()
                ->where('tenant_id', (string) $tenantModel->id)
                ->whereIn('status', ['unpaid', 'overdue'])
                ->count();
        } catch (\Throwable) {
            $billingUnpaidCount = 0;
        }
    }

    $subscriptionAlert = null;

    if ($tenantModel?->subscription_due_at !== null) {
        $dueDate = $tenantModel->subscription_due_at instanceof \Carbon\CarbonInterface
            ? $tenantModel->subscription_due_at->copy()
            : \Illuminate\Support\Carbon::parse((string) $tenantModel->subscription_due_at);

        if ($dueDate->lt(today())) {
            $daysPastDue = $dueDate->diffInDays(today());
            $subscriptionAlert = [
                'tone' => 'danger',
                'title' => 'Subscription overdue',
                'message' => "Your subscription passed its due date {$daysPastDue} day(s) ago. Please coordinate payment immediately to avoid interruption.",
            ];
        } elseif ($dueDate->isToday()) {
            $subscriptionAlert = [
                'tone' => 'danger',
                'title' => 'Subscription ends today',
                'message' => 'Your subscription is due today. Please settle your payment to keep your portal active.',
            ];
        } elseif ($dueDate->lte(today()->copy()->addDays(3))) {
            $daysLeft = today()->diffInDays($dueDate);
            $subscriptionAlert = [
                'tone' => 'warning',
                'title' => "Subscription ends in {$daysLeft} day(s)",
                'message' => 'Your subscription is close to expiry. Please coordinate payment as soon as possible.',
            ];
        } elseif ($dueDate->lte(today()->copy()->addDays(7))) {
            $daysLeft = today()->diffInDays($dueDate);
            $subscriptionAlert = [
                'tone' => 'info',
                'title' => "Subscription ends in {$daysLeft} day(s)",
                'message' => 'Your subscription is approaching its due date. Please prepare payment to avoid service interruption.',
            ];
        }
    }
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
            --pm-shell-bg: {{ $themeConfig['shell_bg'] }};
            --pm-page-bg: {{ $themeConfig['page_bg'] }};
            --pm-card-bg: {{ $themeConfig['card_bg'] }};
            --pm-surface-bg: {{ $themeConfig['surface_bg'] }};
            --pm-input-bg: {{ $themeConfig['input_bg'] }};
            --pm-input-border: {{ $themeConfig['input_border'] }};
            --pm-sidebar-bg: {{ $themeConfig['sidebar_bg'] }};
            --pm-header-bg: {{ $themeConfig['header_bg'] }};
            --pm-border: {{ $themeConfig['border'] }};
            --pm-border-hover: {{ $themeConfig['border_hover'] }};
            --pm-text-primary: {{ $themeConfig['text_primary'] }};
            --pm-text-secondary: {{ $themeConfig['text_secondary'] }};
            --pm-text-muted: {{ $themeConfig['text_muted'] }};
            --pm-text-subtle: {{ $themeConfig['text_subtle'] }};
            --pm-panel-bg: {{ $themeConfig['panel_bg'] }};
            --pm-panel-border: {{ $themeConfig['panel_border'] }};
            --pm-nav-text: {{ $themeConfig['nav_text'] }};
            --pm-nav-hover-bg: {{ $themeConfig['nav_hover_bg'] }};
            --pm-nav-hover-text: {{ $themeConfig['nav_hover_text'] }};
            --pm-table-striped-bg: {{ $themeConfig['table_striped_bg'] }};
            --pm-table-hover-bg: {{ $themeConfig['table_hover_bg'] }};
            --pm-accent: {{ $accentHex }};
            --pm-accent-hover: {{ $accentHover }};
            --pm-accent-rgb: {{ $accentRgb }};
        }

        html {
            font-size: {{ $fontScaleBase }}px;
        }

        body {
            background-color: var(--pm-shell-bg);
            color: var(--pm-text-secondary);
            font-family: 'Figtree', sans-serif;
        }

        body.tenant-theme-dark {
            color-scheme: dark;
        }

        body.tenant-theme-light {
            color-scheme: light;
        }

        .tenant-sidebar-surface {
            background-color: var(--pm-sidebar-bg);
            border-color: rgba(15, 23, 42, 0.08);
        }

        .tenant-topbar-surface {
            background-color: var(--pm-header-bg);
            border-color: rgba(15, 23, 42, 0.08);
        }

        .tenant-main-surface {
            background-color: var(--pm-page-bg);
        }

        .tenant-panel {
            background-color: var(--pm-panel-bg);
            border-color: var(--pm-panel-border);
            box-shadow: 0 20px 45px rgba(2, 6, 23, 0.18);
        }

        .tenant-heading {
            color: var(--pm-text-primary);
        }

        .tenant-muted {
            color: var(--pm-text-muted);
        }

        .tenant-subtle {
            color: var(--pm-text-subtle);
        }

        .tenant-nav-item {
            color: var(--pm-nav-text);
        }

        .tenant-nav-item:hover {
            background-color: var(--pm-nav-hover-bg);
            color: var(--pm-nav-hover-text);
        }

        .tenant-nav-item-active {
            border-left-color: var(--pm-accent);
            background-color: rgba(var(--pm-accent-rgb), 0.12);
            color: var(--pm-nav-hover-text);
        }

        .tenant-nav-item-active:hover {
            background-color: rgba(var(--pm-accent-rgb), 0.16);
        }

        .tenant-nav-icon {
            color: var(--pm-text-muted);
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
            background-color: var(--pm-input-bg) !important;
            border-color: var(--pm-input-border) !important;
            color: var(--pm-text-primary) !important;
        }

        .legacy-content select option {
            background-color: var(--pm-input-bg);
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
            background-color: var(--pm-input-bg) !important;
            border-color: var(--pm-accent) !important;
            color: var(--pm-text-primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(var(--pm-accent-rgb), 0.18) !important;
        }

        .legacy-content .form-check-input {
            background-color: var(--pm-input-bg);
            border-color: var(--pm-input-border);
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
            --bs-table-striped-bg: var(--pm-table-striped-bg);
            --bs-table-striped-color: var(--pm-text-secondary);
            --bs-table-hover-bg: var(--pm-table-hover-bg);
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
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            line-height: 1.45;
            white-space: nowrap;
            vertical-align: middle;
        }

        /* ── Global text-color overrides for light/dark theme ── */
        .legacy-content .text-dark,
        .legacy-content .text-body {
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .text-white {
            color: var(--pm-text-primary) !important;
        }

        .legacy-content .text-slate-200,
        .legacy-content .text-slate-300 {
            color: var(--pm-text-secondary) !important;
        }

        .legacy-content .table td,
        .legacy-content .table th {
            color: var(--pm-text-secondary);
        }

        .legacy-content .table td a:not(.btn):not(.badge) {
            color: var(--pm-accent) !important;
        }

        .legacy-content .table td a:not(.btn):not(.badge):hover {
            opacity: 0.8;
        }

        .legacy-content .table td .fw-semibold,
        .legacy-content .table td .fw-bold,
        .legacy-content .table td strong {
            color: var(--pm-text-primary);
        }

        .legacy-content .table thead th {
            color: var(--pm-text-muted) !important;
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
            background-color: var(--pm-table-hover-bg) !important;
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

        body.tenant-theme-light .tenant-sidebar-surface,
        body.tenant-theme-light .tenant-topbar-surface {
            border-color: var(--pm-border);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        body.tenant-theme-light .tenant-panel {
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        body.tenant-theme-light .legacy-content h1,
        body.tenant-theme-light .legacy-content h2,
        body.tenant-theme-light .legacy-content h3,
        body.tenant-theme-light .legacy-content h4,
        body.tenant-theme-light .legacy-content h5,
        body.tenant-theme-light .legacy-content h6 {
            color: var(--pm-text-primary) !important;
        }

        body.tenant-theme-light .legacy-content [class~="bg-white/[0.02]"],
        body.tenant-theme-light .legacy-content [class~="bg-white/[0.04]"] {
            background-color: var(--pm-panel-bg) !important;
        }

        body.tenant-theme-light .legacy-content [class~="bg-white/[0.03]"],
        body.tenant-theme-light .legacy-content [class~="bg-white/[0.06]"],
        body.tenant-theme-light .legacy-content [class~="bg-[#161b22]"],
        body.tenant-theme-light .legacy-content [class~="bg-[#0f1319]"],
        body.tenant-theme-light .legacy-content [class~="bg-[#0F1729]"],
        body.tenant-theme-light .legacy-content [class~="bg-[#0b1120]"],
        body.tenant-theme-light .legacy-content [class~="bg-slate-950"] {
            background-color: var(--pm-surface-bg) !important;
        }

        body.tenant-theme-light .legacy-content [class~="border-[#21262d]"],
        body.tenant-theme-light .legacy-content [class~="border-white/[0.05]"],
        body.tenant-theme-light .legacy-content [class~="border-white/[0.06]"],
        body.tenant-theme-light .legacy-content [class~="border-white/[0.07]"],
        body.tenant-theme-light .legacy-content [class~="border-white/[0.08]"],
        body.tenant-theme-light .legacy-content [class~="border-white/10"] {
            border-color: var(--pm-panel-border) !important;
        }

        body.tenant-theme-light .legacy-content [class~="divide-white/[0.04]"] > :not([hidden]) ~ :not([hidden]) {
            border-color: var(--pm-panel-border) !important;
        }

        body.tenant-theme-light .legacy-content [class~="text-white"]:not(.btn):not(button):not([role='button']):not([style*='background-color']):not([class*='bg-']) {
            color: var(--pm-text-primary) !important;
        }

        body.tenant-theme-light .legacy-content [class~="text-slate-200"],
        body.tenant-theme-light .legacy-content [class~="text-slate-300"],
        body.tenant-theme-light .legacy-content [class~="text-[#8b949e]"] {
            color: var(--pm-text-secondary) !important;
        }

        body.tenant-theme-light .legacy-content [class~="text-slate-400"],
        body.tenant-theme-light .legacy-content [class~="text-slate-500"] {
            color: var(--pm-text-muted) !important;
        }

        body.tenant-theme-light .legacy-content [class~="hover:bg-white/[0.02]"]:hover {
            background-color: var(--pm-table-hover-bg) !important;
        }

        body.tenant-theme-light .legacy-content [class~="hover:bg-white/[0.04]"]:hover,
        body.tenant-theme-light .legacy-content [class~="hover:bg-white/[0.05]"]:hover {
            background-color: var(--pm-surface-bg) !important;
        }

        body.tenant-theme-light .legacy-content [class~="hover:border-white/20"]:hover,
        body.tenant-theme-light .legacy-content [class~="hover:border-white/25"]:hover {
            border-color: var(--pm-border-hover) !important;
        }

        body.tenant-theme-light .legacy-content [class~="hover:text-white"]:hover {
            color: var(--pm-text-primary) !important;
        }

        /* Light mode overrides for badge and alert colors */
        body.tenant-theme-light [class*="text-emerald-300"],
        body.tenant-theme-light [class*="text-emerald-200"] {
            color: #047857 !important; /* emerald-700 */
        }
        body.tenant-theme-light [class*="bg-emerald-500/"],
        body.tenant-theme-light [class*="bg-emerald-400/"] {
            background-color: #d1fae5 !important; /* emerald-100 */
            border-color: #a7f3d0 !important; /* emerald-200 */
        }

        body.tenant-theme-light [class*="text-red-300"],
        body.tenant-theme-light [class*="text-red-200"],
        body.tenant-theme-light [class*="text-red-100"] {
            color: #b91c1c !important; /* red-700 */
        }
        body.tenant-theme-light [class*="bg-red-500/"],
        body.tenant-theme-light [class*="bg-red-400/"] {
            background-color: #fee2e2 !important; /* red-100 */
            border-color: #fecaca !important; /* red-200 */
        }

        body.tenant-theme-light [class*="text-blue-300"],
        body.tenant-theme-light [class*="text-blue-200"],
        body.tenant-theme-light [class*="text-blue-100"] {
            color: #1d4ed8 !important; /* blue-700 */
        }
        body.tenant-theme-light [class*="bg-blue-500/"],
        body.tenant-theme-light [class*="bg-blue-400/"] {
            background-color: #dbeafe !important; /* blue-100 */
            border-color: #bfdbfe !important; /* blue-200 */
        }

        body.tenant-theme-light [class*="text-amber-300"],
        body.tenant-theme-light [class*="text-amber-200"],
        body.tenant-theme-light [class*="text-amber-100"] {
            color: #b45309 !important; /* amber-700 */
        }
        body.tenant-theme-light [class*="bg-amber-500/"],
        body.tenant-theme-light [class*="bg-amber-400/"] {
            background-color: #fef3c7 !important; /* amber-100 */
            border-color: #fde68a !important; /* amber-200 */
        }

        body.tenant-theme-light [class*="text-purple-300"],
        body.tenant-theme-light [class*="text-purple-200"],
        body.tenant-theme-light [class*="text-purple-100"] {
            color: #7e22ce !important; /* purple-700 */
        }
        body.tenant-theme-light [class*="bg-purple-500/"],
        body.tenant-theme-light [class*="bg-purple-400/"] {
            background-color: #f3e8ff !important; /* purple-100 */
            border-color: #e9d5ff !important; /* purple-200 */
        }

        body.tenant-theme-light [class*="text-sky-300"],
        body.tenant-theme-light [class*="text-sky-200"],
        body.tenant-theme-light [class*="text-sky-100"] {
            color: #0369a1 !important; /* sky-700 */
        }
        body.tenant-theme-light [class*="bg-sky-500/"],
        body.tenant-theme-light [class*="bg-sky-400/"] {
            background-color: #e0f2fe !important; /* sky-100 */
            border-color: #bae6fd !important; /* sky-200 */
        }

        body.tenant-theme-light .legacy-content [class~="ring-white/20"] {
            --tw-ring-color: rgba(var(--pm-accent-rgb), 0.18) !important;
        }
    </style>

    @vite(['resources/css/paymonitor.css', 'resources/js/paymonitor-dashboard.js'])
</head>
<body class="tenant-theme-{{ $themeMode }} min-h-screen antialiased" x-data="{ sidebarOpen: false }">

    @php
    try {
        $currentTenant = \App\Models\Tenant::find(tenant()?->id);
        $updateRequired = (bool) ($currentTenant?->update_required ?? false);
        $requiredVersion = $currentTenant?->update_required_version;
        $tenantCurrentVersion = \App\Models\TenantSetting::get('current_version', 'v1.0.0');
        $isAlreadyOnRequired = !$updateRequired || (
            $requiredVersion && version_compare(
                ltrim((string)$tenantCurrentVersion, 'v'),
                ltrim((string)$requiredVersion, 'v'),
                '>='
            )
        );
    } catch (\Exception $e) {
        $updateRequired = false;
        $isAlreadyOnRequired = true;
    }
    @endphp

    @if($updateRequired && !$isAlreadyOnRequired)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="bg-[#161b22] border border-yellow-500/50 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-yellow-500/10 border border-yellow-500/30 mx-auto mb-6">
                <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h2 class="text-white text-xl font-bold text-center mb-2">Update Required</h2>
            <p class="text-[#8b949e] text-sm text-center mb-1">Your administrator requires you to update to version</p>
            <p class="text-yellow-400 text-2xl font-black text-center font-mono mb-4">{{ $requiredVersion }}</p>
            <p class="text-[#8b949e] text-xs text-center mb-6">You must update your portal before continuing. This update includes important improvements and security fixes.</p>
            <div class="bg-[#0d1117] rounded-xl p-4 mb-6 flex items-center justify-between">
                <div class="text-center">
                    <p class="text-[#8b949e] text-xs mb-1">Your Version</p>
                    <p class="text-white font-mono font-bold">{{ $tenantCurrentVersion }}</p>
                </div>
                <div class="text-[#8b949e] text-lg">→</div>
                <div class="text-center">
                    <p class="text-[#8b949e] text-xs mb-1">Required Version</p>
                    <p class="text-yellow-400 font-mono font-bold">{{ $requiredVersion }}</p>
                </div>
            </div>
            <a href="{{ url('/updates') }}"
               class="block w-full text-center bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-3 rounded-xl transition-colors text-sm">
                Update Now →
            </a>
            <p class="text-[#52525b] text-xs text-center mt-3">You cannot dismiss this dialog until your portal is updated.</p>
        </div>
    </div>
    @endif

    <div class="relative min-h-screen">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/70 md:hidden" x-on:click="sidebarOpen = false"></div>

        <aside class="tenant-sidebar-surface fixed inset-y-0 left-0 z-50 w-64 overflow-hidden border-r px-4 py-6 transition-transform duration-200 md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div class="flex h-full min-h-0 flex-col">
                <div class="tenant-sidebar-scroll min-h-0 flex-1 pr-2">
                    <div class="tenant-panel overflow-hidden rounded-2xl border p-3">
                        <div class="flex items-center gap-2.5">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $tenantName }} logo" class="h-12 w-12 rounded-xl object-cover ring-1 ring-white/10 flex-shrink-0">
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl shadow-lg flex-shrink-0" style="background: linear-gradient(135deg, var(--pm-accent), var(--pm-accent-hover)); box-shadow: 0 16px 36px rgba(var(--pm-accent-rgb), 0.22);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="tenant-heading font-heading text-base font-bold tracking-tight leading-tight" title="{{ $tenantName }}">{{ $tenantName }}</p>
                                <div class="mt-1">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.18em]" style="border: 1px solid rgba(var(--pm-accent-rgb), 0.28); background-color: rgba(var(--pm-accent-rgb), 0.12); color: var(--pm-accent);">
                                        Tenant
                                    </span>
                                </div>
                                @if(filled($tagline))
                                    <p class="tenant-muted mt-1 text-[11px] leading-5">{{ \Illuminate\Support\Str::limit($tagline, 42) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <p class="tenant-muted px-4 text-[11px] font-semibold uppercase tracking-[0.24em]">Overview</p>
                        <nav class="mt-3 space-y-1.5">
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::DASHBOARD_VIEW))
                                @php($dashboardActive = request()->routeIs('dashboard'))
                                <a href="{{ route('dashboard', $tenantParameter, false) }}" class="{{ $navItemClass($dashboardActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($dashboardActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12a8.25 8.25 0 1 1 16.5 0v6.75a1.5 1.5 0 0 1-1.5 1.5h-3.75v-6h-6v6H5.25a1.5 1.5 0 0 1-1.5-1.5V12Z"/></svg>
                                    <span>Dashboard</span>
                                </a>
                            @endif
                            @php($billingActive = request()->routeIs('billing.*'))
                            <a href="{{ route('billing.index', $tenantParameter, false) }}" class="{{ $navItemClass($billingActive) }}">
                                <svg class="h-5 w-5 {{ $navIconClass($billingActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5m-16.5 6h2.25m2.25 0h2.25m-7.5 6h15a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 2.25 6V18a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                <span>Billing</span>
                                @if($billingUnpaidCount > 0)
                                    <span class="ml-auto inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-red-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-red-300">{{ $billingUnpaidCount }}</span>
                                @endif
                            </a>
                        </nav>
                    </div>

                    <div class="mt-6">
                                        <p class="tenant-muted px-4 text-[11px] font-semibold uppercase tracking-[0.24em]">Lending</p>
                        <nav class="mt-3 space-y-1.5">
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::MEMBERS_VIEW))
                                @php($membersActive = request()->routeIs('members.*'))
                                <a href="{{ route('members.index', $tenantParameter, false) }}" class="{{ $navItemClass($membersActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($membersActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM5.25 18a5.25 5.25 0 0 1 10.5 0"/></svg>
                                    <span>Members</span>
                                </a>
                            @endif
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::LOANS_VIEW))
                                @php($loansActive = request()->routeIs('loans.*'))
                                <a href="{{ route('loans.index', $tenantParameter, false) }}" class="{{ $navItemClass($loansActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($loansActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                    <span>Loans</span>
                                </a>
                            @endif
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::LOAN_TYPES_VIEW))
                                @php($loanTypesActive = request()->routeIs('loan-types.*'))
                                <a href="{{ route('loan-types.index', $tenantParameter, false) }}" class="{{ $navItemClass($loanTypesActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($loanTypesActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9A1.5 1.5 0 0 1 18 6v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18V6A1.5 1.5 0 0 1 7.5 4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9h6M9 12h6M9 15h3.75"/></svg>
                                    <span>Loan Types</span>
                                </a>
                            @endif
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::LOAN_PAYMENTS_VIEW))
                                @php($paymentsActive = request()->routeIs('loan-payments.*'))
                                <a href="{{ route('loan-payments.index', $tenantParameter, false) }}" class="{{ $navItemClass($paymentsActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($paymentsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6A2.25 2.25 0 0 1 3.75 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-12 4.5h3"/></svg>
                                    <span>Payments</span>
                                </a>
                            @endif
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::COLLECTIONS_VIEW))
                                @php($collectionsActive = request()->routeIs('tenant.collections'))
                                <a href="{{ route('tenant.collections', $tenantParameter, false) }}" class="{{ $navItemClass($collectionsActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($collectionsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6 3.75v6m12-6v6M5.25 12.75h13.5a1.5 1.5 0 0 1 1.5 1.5v3a3 3 0 0 1-3 3H6.75a3 3 0 0 1-3-3v-3a1.5 1.5 0 0 1 1.5-1.5Zm3 0v1.5a.75.75 0 0 0 .75.75h6a.75.75 0 0 0 .75-.75v-1.5"/></svg>
                                    <span>Collections</span>
                                </a>
                            @endif
                        </nav>
                    </div>

                    @if($user?->hasAnyTenantPermission([\App\Support\TenantPermissions::BRANCHES_VIEW, \App\Support\TenantPermissions::USERS_VIEW]) || ($tenantSupportsAuditLogs && $user?->hasTenantPermission(\App\Support\TenantPermissions::AUDIT_LOGS_VIEW)))
                        <div class="mt-6">
                            <p class="tenant-muted px-4 text-[11px] font-semibold uppercase tracking-[0.24em]">Management</p>
                            <nav class="mt-3 space-y-1.5">
                                @if($user?->hasTenantPermission(\App\Support\TenantPermissions::BRANCHES_VIEW))
                                    @php($branchesActive = request()->routeIs('branches.*'))
                                    <a href="{{ route('branches.index', $tenantParameter, false) }}" class="{{ $navItemClass($branchesActive) }}">
                                        <svg class="h-5 w-5 {{ $navIconClass($branchesActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h4.5v4.5H4.5v-4.5Zm0 6h4.5v4.5H4.5v-4.5Zm10.5-6h4.5v4.5H15v-4.5Zm0 6h4.5v4.5H15v-4.5Z"/></svg>
                                        <span>Branches</span>
                                    </a>
                                @endif
                                @if($user?->hasTenantPermission(\App\Support\TenantPermissions::USERS_VIEW))
                                    @php($usersActive = request()->routeIs('users.*'))
                                    <a href="{{ route('users.index', $tenantParameter, false) }}" class="{{ $navItemClass($usersActive) }}">
                                        <svg class="h-5 w-5 {{ $navIconClass($usersActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM5.25 18a5.25 5.25 0 0 1 10.5 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 8.25h3m-1.5-1.5v3"/></svg>
                                        <span>Users</span>
                                    </a>
                                @endif
                                @if($tenantSupportsAuditLogs && $user?->hasTenantPermission(\App\Support\TenantPermissions::AUDIT_LOGS_VIEW))
                                    @php($auditLogsActive = request()->routeIs('tenant.audit-logs'))
                                    <a href="{{ route('tenant.audit-logs', $tenantParameter, false) }}" class="{{ $navItemClass($auditLogsActive) }}">
                                        <svg class="h-5 w-5 {{ $navIconClass($auditLogsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4.5 2.25"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        <span>Audit Logs</span>
                                    </a>
                                @endif
                            </nav>
                        </div>
                    @endif

                    <div class="mt-6">
                        <p class="tenant-muted px-4 text-[11px] font-semibold uppercase tracking-[0.24em]">Insights</p>
                        <nav class="mt-3 space-y-1.5">
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::REPORTS_VIEW))
                                @php($reportsActive = request()->routeIs('reports.*'))
                                <a href="{{ route('reports.index', $tenantParameter, false) }}" class="{{ $navItemClass($reportsActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($reportsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5h15"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 16.5v-4.5M12 16.5V9M16.5 16.5V6"/></svg>
                                    <span>Reports</span>
                                </a>
                            @endif
                        </nav>
                    </div>

                    <div class="mt-6 border-t border-white/[0.06] pt-6">
                        <p class="tenant-muted px-4 text-[11px] font-semibold uppercase tracking-[0.24em]">Workspace</p>
                        <nav class="mt-3 space-y-1.5">
                            @php($settingsActive = request()->routeIs('settings.*'))
                            @if($user?->hasTenantPermission(\App\Support\TenantPermissions::SETTINGS_VIEW))
                                <a href="{{ route('settings.index', $tenantParameter, false) }}" class="{{ $navItemClass($settingsActive) }}">
                                    <svg class="h-5 w-5 {{ $navIconClass($settingsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3m-7.72 1.22 2.12-2.12m8.64 0 2.12 2.12M18 10.5v3m-1.22 7.72-2.12-2.12m-8.64 0-2.12 2.12M6 13.5v-3m6 1.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z" />
                                    </svg>
                                    <span class="inline-flex items-center gap-2">
                                        <span>Settings</span>
                                        @if($updateInfo['update_available'] ?? false)
                                            <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-300">New</span>
                                        @endif
                                    </span>
                                </a>
                            @endif
                        </nav>
                    </div>
                </div>

                <div class="mt-4 shrink-0 border-t pt-4" style="border-color: rgba(148, 163, 184, 0.16);">
                    <form method="POST" action="{{ route('tenant.logout', $tenantParameter, false) }}"
                        data-confirm="You will be signed out of this tenant portal."
                        data-confirm-title="Log out of portal?"
                        data-confirm-confirm-text="Log out"
                        data-confirm-tone="danger">
                        @csrf
                        <button type="submit" class="tenant-nav-item group flex w-full appearance-none items-center gap-3 rounded-md border-0 border-l-[3px] border-transparent bg-transparent px-3 py-2 text-left text-sm font-medium transition">
                            <svg class="tenant-nav-icon h-5 w-5 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5V5.25A2.25 2.25 0 0 0 12.75 3h-6A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21h6A2.25 2.25 0 0 0 15 18.75V16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 15 3-3m0 0-3-3m3 3H9"/></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="md:pl-64">
            <header class="tenant-topbar-surface fixed left-0 right-0 top-0 z-30 border-b backdrop-blur md:left-64">
                <div class="flex h-16 items-center justify-between px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border transition md:hidden" style="border-color: rgba(148, 163, 184, 0.2); color: var(--pm-text-secondary);" x-on:click="sidebarOpen = true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden rounded-full px-3 py-1 text-xs font-medium sm:inline-flex" style="border: 1px solid rgba(var(--pm-accent-rgb), 0.28); background-color: rgba(var(--pm-accent-rgb), 0.12); color: var(--pm-accent);" title="{{ $tenantName }}">{{ \Illuminate\Support\Str::limit($tenantName, 22) }}</span>
                        <div class="hidden text-right sm:block">
                            <p class="tenant-heading text-sm font-medium">{{ $user?->name ?? 'Tenant User' }}</p>
                            <p class="tenant-muted text-xs">{{ $roleDisplay }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="tenant-main-surface min-h-screen p-6 pt-24">
                <div class="mx-auto max-w-7xl">
                    @if($updateInfo['update_available'] ?? false)
                        <div
                            class="mb-4 flex flex-col gap-3 rounded-xl border border-sky-500/20 bg-sky-500/10 px-4 py-3 text-sky-100 lg:flex-row lg:items-center lg:justify-between"
                            x-data="{ show: localStorage.getItem('dismissed_update') !== '{{ $updateInfo['latest_version'] ?? '' }}' }"
                            x-show="show"
                            x-transition
                        >
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-sky-400/30 text-sky-300">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-sky-200">New update available</p>
                                    <p class="mt-1 text-sm text-sky-100/85">{{ $updateInfo['latest_version'] ?? 'Unknown' }} - {{ $updateInfo['release_name'] ?? 'Unable to check' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('settings.updates', $tenantParameter, false) }}" class="inline-flex items-center rounded-lg border border-sky-400/30 bg-transparent px-3 py-1.5 text-xs font-semibold text-sky-200 transition hover:border-sky-300 hover:bg-sky-500/10 hover:text-white">
                                    View updates
                                </a>
                                <button
                                    type="button"
                                    @click="show = false; localStorage.setItem('dismissed_update', '{{ $updateInfo['latest_version'] ?? '' }}')"
                                    class="text-xs font-medium text-sky-200/70 transition hover:text-sky-100"
                                >
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($subscriptionAlert !== null)
                        <?php
                            $subscriptionAlertStyles = match ($subscriptionAlert['tone']) {
                                'danger' => [
                                    'container' => 'border-red-500/40 bg-red-500/10 text-red-100',
                                    'icon' => 'text-red-300',
                                ],
                                'warning' => [
                                    'container' => 'border-amber-400/40 bg-amber-500/10 text-amber-100',
                                    'icon' => 'text-amber-300',
                                ],
                                default => [
                                    'container' => 'border-sky-400/40 bg-sky-500/10 text-sky-100',
                                    'icon' => 'text-sky-300',
                                ],
                            };
                        ?>
                        <div class="mb-6 rounded-xl border px-4 py-4 {{ $subscriptionAlertStyles['container'] }}">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-current/30 {{ $subscriptionAlertStyles['icon'] }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold">{{ $subscriptionAlert['title'] }}</p>
                                    <p class="mt-1 text-sm text-white/80">{{ $subscriptionAlert['message'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($flashMessages->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($flashMessages as $flash)
                                <?php
                                    [$flashBorder, $flashBg, $flashIcon] = match ($flash['key']) {
                                        'success' => ['#22c55e', 'rgba(34,197,94,0.12)', '#16a34a'],
                                        'error'   => ['#ef4444', 'rgba(239,68,68,0.12)',  '#dc2626'],
                                        default   => ['#f59e0b', 'rgba(245,158,11,0.12)', '#d97706'],
                                    };
                                ?>
                                <div
                                    x-data="{ visible: true }"
                                    x-init="setTimeout(() => visible = false, 5000)"
                                    x-show="visible"
                                    x-transition.opacity.duration.300ms
                                    class="rounded-xl border-l-4 px-4 py-3 text-sm font-medium"
                                    style="border-left-color: {{ $flashBorder }}; background-color: {{ $flashBg }}; color: var(--pm-text-primary); border: 1px solid {{ $flashBorder }}33; border-left: 4px solid {{ $flashBorder }};"
                                >
                                    {{ $flash['message'] }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="legacy-content mt-6 max-w-7xl">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>


    @include('partials.dialogs')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
