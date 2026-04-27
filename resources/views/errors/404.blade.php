@php
    $tenantName = function_exists('tenant') && tenant() ? tenant('name') : 'PayMonitor';

    try {
        $tenantLogoPath = class_exists(\App\Models\TenantSetting::class) ? \App\Models\TenantSetting::get('logo_path') : null;
        $tenantFaviconUrl = filled($tenantLogoPath)
            ? route('stancl.tenancy.asset', ['path' => ltrim((string) $tenantLogoPath, '/')], false).'?v='.rawurlencode((string) $tenantLogoPath)
            : asset('favicon.ico');
    } catch (\Throwable $e) {
        $tenantFaviconUrl = asset('favicon.ico');
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found | {{ $tenantName }}</title>
    <link rel="icon" href="{{ $tenantFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $tenantFaviconUrl }}">

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
        <aside class="relative hidden overflow-hidden border-r border-white/5 bg-[#0A1628] md:flex">
            <div class="pm-auth-grid absolute inset-0 opacity-60"></div>
            <div class="pm-orb pm-orb--emerald" style="width:500px;height:500px;top:-10%;left:-5%"></div>
            <div class="pm-orb pm-orb--amber" style="width:400px;height:400px;bottom:10%;right:-10%;animation-delay:-7s"></div>

            <div class="relative flex min-h-screen w-full flex-col justify-between px-10 py-10 lg:px-14">
                <div></div>

                <div class="mx-auto max-w-md">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <span class="font-heading text-2xl font-extrabold tracking-tight text-white">PayMonitor</span>
                    </div>
                    <p class="font-heading text-3xl font-bold leading-tight tracking-tight text-white lg:text-4xl">
                        {{ $tenantName }}<br><span class="pm-gradient-text">Page Not Found</span>
                    </p>
                    <p class="mt-4 max-w-sm text-base leading-7 text-slate-400">
                        The page you are looking for may have been moved, deleted, or may never have existed.
                    </p>
                </div>

                <div class="text-sm text-slate-600">
                    &copy; {{ date('Y') }} {{ $tenantName }}
                </div>
            </div>
        </aside>

        <main class="flex min-h-screen items-center justify-center bg-[#0B1120] px-5 py-10 sm:px-8">
            <div class="w-full max-w-md rounded-2xl border border-white/[0.08] bg-white/[0.03] p-7 shadow-[0_24px_80px_rgba(0,0,0,0.5)] backdrop-blur sm:p-8">
                <div>
                    <p class="font-heading max-w-full overflow-hidden text-ellipsis whitespace-nowrap text-xs font-semibold uppercase tracking-[0.12em] text-emerald-400 sm:text-sm sm:tracking-[0.16em]">404 NOT FOUND</p>
                    <h1 class="font-heading mt-4 text-2xl font-bold tracking-tight text-white">Page Not Found</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-400">
                        The page you requested could not be found. It may have been moved or deleted.
                    </p>
                </div>

                <div class="mt-8 rounded-xl border border-blue-500/20 bg-blue-500/10 px-4 py-4">
                    <p class="text-sm font-medium uppercase tracking-[0.12em] text-blue-300">What can you do?</p>
                    <ul class="mt-3 space-y-1.5 text-sm leading-6 text-slate-300">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-400"></span>
                            Check that the URL is spelled correctly.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-400"></span>
                            Go back to the previous page and try again.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-400"></span>
                            Return to the dashboard to continue working.
                        </li>
                    </ul>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}"
                       class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-white/[0.05] bg-white/[0.05] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:border-white/10 hover:bg-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        Go Back
                    </a>
                    <a href="{{ url('/dashboard') }}"
                       class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
