@php
    $tenantName = $tenantName ?? tenant()?->name ?? 'Cooperative';
    $tenantHost = $tenantHost ?? request()->getHost();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenantName }} Portal | PayMonitor</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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

    @vite(['resources/css/paymonitor.css'])

    <style>
        .pm-auth-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="grid min-h-screen md:grid-cols-[1.08fr_0.92fr]">
        <!-- Left panel -->
        <aside class="relative hidden overflow-hidden border-r border-white/5 bg-[#0A1628] md:flex">
            <div class="pm-auth-grid absolute inset-0 opacity-60"></div>
            <div class="pm-orb pm-orb--emerald" style="width:500px;height:500px;top:-10%;left:-5%"></div>
            <div class="pm-orb pm-orb--amber" style="width:400px;height:400px;bottom:10%;right:-10%;animation-delay:-7s"></div>

            <div class="relative flex min-h-screen w-full flex-col justify-between px-10 py-10 lg:px-14">
                <div></div>

                <div class="mx-auto max-w-md">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <span class="font-heading text-2xl font-extrabold text-white tracking-tight">PayMonitor</span>
                    </div>
                    <p class="font-heading text-3xl font-bold tracking-tight text-white lg:text-4xl leading-tight">
                        {{ $tenantName }}<br><span class="pm-gradient-text">Portal</span>
                    </p>
                    <p class="mt-4 max-w-sm text-base leading-7 text-slate-400">
                        Welcome back to your cooperative portal.
                    </p>

                    <div class="mt-10 space-y-4">
                        <div class="flex items-start gap-3 text-sm text-slate-300">
                            <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42-.003L4.79 10.75a1 1 0 1 1 1.42-1.41l2.54 2.56 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/></svg>
                            </span>
                            <span>Your data is completely isolated</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-slate-300">
                            <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42-.003L4.79 10.75a1 1 0 1 1 1.42-1.41l2.54 2.56 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/></svg>
                            </span>
                            <span>Secure domain-based login</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-slate-300">
                            <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42-.003L4.79 10.75a1 1 0 1 1 1.42-1.41l2.54 2.56 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/></svg>
                            </span>
                            <span>Role-based staff access</span>
                        </div>
                    </div>
                </div>

                <div class="text-sm text-slate-600">
                    &copy; 2026 {{ $tenantName }} Portal
                </div>
            </div>
        </aside>

        <!-- Login form -->
        <main class="flex min-h-screen items-center justify-center bg-[#0B1120] px-5 py-10 sm:px-8">
            <div class="w-full max-w-md rounded-2xl border border-white/[0.08] bg-white/[0.03] p-7 shadow-[0_24px_80px_rgba(0,0,0,0.5)] backdrop-blur sm:p-8" x-data="{ showPassword: false }">
                <div>
                    <p class="font-heading max-w-full overflow-hidden text-ellipsis whitespace-nowrap text-xs font-semibold uppercase tracking-[0.12em] text-emerald-400 sm:text-sm sm:tracking-[0.16em]">{{ strtoupper($tenantHost) }}</p>
                    <h1 class="font-heading mt-4 text-2xl font-bold tracking-tight text-white">{{ $tenantName }} Portal</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Sign in to manage loans and members.</p>
                </div>

                <form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="{{ 'name@' . $tenantHost }}" class="pm-input block w-full rounded-xl px-4 py-3 text-sm">
                        @error('email')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                        <div class="relative">
                            <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" autocomplete="current-password" required placeholder="Enter your password" class="pm-input block w-full rounded-xl px-4 py-3 pr-14 text-sm">
                            <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-sm font-medium text-slate-400 transition hover:text-slate-200">
                                <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <label for="remember" class="flex items-center gap-3 text-sm text-slate-400">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember')) class="pm-check h-4 w-4 rounded border-white/10">
                        <span>Remember me</span>
                    </label>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:shadow-emerald-500/30 hover:brightness-110">
                        Log In
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Having trouble? Contact your administrator.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
