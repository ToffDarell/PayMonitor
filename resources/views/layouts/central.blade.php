<?php
    $pageTitle = trim($__env->yieldContent('title', 'Central App')) ?: 'Central App';
    $user = auth()->user();
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
    <title>{{ $pageTitle }} | PayMonitor Central</title>

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

        <aside class="fixed inset-y-0 left-0 z-50 w-56 border-r border-white/[0.06] bg-[#0A1628] px-4 py-6 transition-transform duration-200 md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div class="flex h-full flex-col">
                <div>
                    <div class="overflow-hidden rounded-2xl border border-white/5 bg-white/[0.02] p-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-heading text-[15px] font-bold tracking-tight text-white leading-tight">PayMonitor</p>
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
                        </nav>
                    </div>
                </div>

                <div class="mt-auto rounded-2xl border border-white/5 bg-white/[0.02] p-4">
                    <p class="text-sm font-medium text-white">{{ $user?->name ?? 'Super Admin' }}</p>
                    <p class="mt-1 truncate text-sm text-zinc-500">{{ $user?->email ?? 'admin@paymonitor.com' }}</p>
                    <a href="{{ route('central.logout', absolute: false) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md border border-white/10 px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5V5.25A2.25 2.25 0 0 0 12.75 3h-6A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21h6A2.25 2.25 0 0 0 15 18.75V16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 15 3-3m0 0-3-3m3 3H9"/></svg>
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
                        <a href="{{ route('central.logout', absolute: false) }}" class="inline-flex items-center gap-2 rounded-md border border-white/10 px-3 py-2 text-sm font-medium text-zinc-300 transition hover:border-white/20 hover:bg-white/[0.04] hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5V5.25A2.25 2.25 0 0 0 12.75 3h-6A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21h6A2.25 2.25 0 0 0 15 18.75V16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 15 3-3m0 0-3-3m3 3H9"/></svg>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    </div>
                </div>
            </header>

            <main class="p-6 pt-24">
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
