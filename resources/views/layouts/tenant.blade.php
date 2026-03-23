<?php
    $pageTitle = trim($__env->yieldContent('title', 'Tenant App')) ?: 'Tenant App';
    $user = auth()->user();
    $tenantModel = tenant();
    $tenantName = $tenantModel?->name ?? 'PayMonitor';
    $tenantHost = request()->getHost();
    $tenantParameter = ['tenant' => $tenantModel?->id ?? request()->route('tenant')];
    $roleName = $user?->getRoleNames()->first() ?? 'viewer';
    $roleBadgeClass = match ($roleName) {
        'tenant_admin' => 'border border-emerald-400/30 bg-emerald-500/15 text-emerald-200',
        'branch_manager' => 'border border-blue-400/30 bg-blue-500/15 text-blue-200',
        'loan_officer' => 'border border-amber-400/30 bg-amber-500/15 text-amber-200',
        'cashier' => 'border border-cyan-400/30 bg-cyan-500/15 text-cyan-200',
        default => 'border border-slate-400/20 bg-slate-500/15 text-slate-200',
    };
    $navItemClass = static function (bool $active): string {
        return $active
            ? 'group flex items-center gap-3 rounded-md border-l-[3px] border-emerald-500 bg-emerald-500/[0.08] px-4 py-3 text-sm font-medium text-white'
            : 'group flex items-center gap-3 rounded-md border-l-[3px] border-transparent px-4 py-3 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-white';
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
    <title>{{ $pageTitle }} | {{ $tenantName }}</title>

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

    @vite(['resources/css/paymonitor.css', 'resources/js/paymonitor-dashboard.js'])
</head>
<body class="min-h-screen bg-[#060B18] text-[#F1F5F9] antialiased" x-data="{ sidebarOpen: false }">
    <div class="relative min-h-screen">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/70 md:hidden" x-on:click="sidebarOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-50 w-64 border-r border-white/[0.06] bg-[#0A1628] px-4 py-6 transition-transform duration-200 md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div class="flex h-full flex-col">
                <div>
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20 flex-shrink-0">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-heading text-base font-bold tracking-tight text-white leading-tight" title="{{ $tenantName }}">{{ $tenantName }}</p>
                                <p class="truncate text-[10px] uppercase tracking-[0.16em] text-emerald-400/60" title="{{ $tenantHost }}">{{ $tenantHost }}</p>
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
                </div>

                <div class="mt-auto rounded-2xl border border-white/5 bg-white/[0.02] p-4">
                    <p class="text-sm font-medium text-white">{{ $user?->name ?? 'Tenant User' }}</p>
                    <p class="mt-1 truncate text-sm text-zinc-500">{{ $user?->email ?? 'user@cooperative.com' }}</p>
                    <a href="{{ route('tenant.logout', $tenantParameter, false) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md border border-white/10 px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5V5.25A2.25 2.25 0 0 0 12.75 3h-6A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21h6A2.25 2.25 0 0 0 15 18.75V16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 15 3-3m0 0-3-3m3 3H9"/></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <div class="md:pl-64">
            <header class="fixed left-0 right-0 top-0 z-30 border-b border-white/[0.06] bg-[#060B18]/80 backdrop-blur md:left-64">
                <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 text-zinc-300 transition hover:border-white/20 hover:bg-white/[0.04] md:hidden" x-on:click="sidebarOpen = true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cooperative Portal</p>
                            <h1 class="font-heading mt-1 text-xl font-bold tracking-tight text-white">{{ $pageTitle }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-white">{{ $user?->name ?? 'Tenant User' }}</p>
                            <p class="text-xs text-zinc-500">{{ $tenantName }}</p>
                        </div>
                        <span class="hidden rounded-full px-3 py-1 text-xs font-medium sm:inline-flex {{ $roleBadgeClass }}">{{ str_replace('_', ' ', $roleName) }}</span>
                        <a href="{{ route('tenant.logout', $tenantParameter, false) }}" class="inline-flex items-center gap-2 rounded-md border border-white/10 px-3 py-2 text-sm font-medium text-zinc-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5V5.25A2.25 2.25 0 0 0 12.75 3h-6A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21h6A2.25 2.25 0 0 0 15 18.75V16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 15 3-3m0 0-3-3m3 3H9"/></svg>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    </div>
                </div>
            </header>

            <main class="px-4 pb-8 pt-24 sm:px-6 lg:px-8">
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
