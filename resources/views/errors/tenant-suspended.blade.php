@php
    $tenantName = $tenantName ?? tenant()?->name ?? 'Cooperative';
    $tenantHost = $tenantHost ?? request()->getHost();
    $portalStatus = $portalStatus ?? (tenant()?->status ?? 'unavailable');
    $statusMessage = $statusMessage ?? 'This cooperative portal is currently unavailable.';
    $tenantLogoPath = \App\Models\TenantSetting::get('logo_path');
    $tenantFaviconUrl = filled($tenantLogoPath)
        ? route('stancl.tenancy.asset', ['path' => ltrim((string) $tenantLogoPath, '/')], false).'?v='.rawurlencode((string) $tenantLogoPath)
        : asset('favicon.ico');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenantName }} Portal Unavailable</title>
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
                        {{ $tenantName }}<br><span class="pm-gradient-text">Portal</span>
                    </p>
                    <p class="mt-4 max-w-sm text-base leading-7 text-slate-400">
                        This tenant domain is temporarily unavailable while access is restricted.
                    </p>
                </div>

                <div class="text-sm text-slate-600">
                    &copy; 2026 {{ $tenantName }} Portal
                </div>
            </div>
        </aside>

        <main class="flex min-h-screen items-center justify-center bg-[#0B1120] px-5 py-10 sm:px-8">
            <div class="w-full max-w-md rounded-2xl border border-white/[0.08] bg-white/[0.03] p-7 shadow-[0_24px_80px_rgba(0,0,0,0.5)] backdrop-blur sm:p-8">
                <div>
                    <p class="font-heading max-w-full overflow-hidden text-ellipsis whitespace-nowrap text-xs font-semibold uppercase tracking-[0.12em] text-emerald-400 sm:text-sm sm:tracking-[0.16em]">{{ strtoupper($tenantHost) }}</p>
                    <h1 class="font-heading mt-4 text-2xl font-bold tracking-tight text-white">Portal Unavailable</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-400">{{ $statusMessage }}</p>
                </div>

                <div class="mt-8 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-4">
                    <p class="text-sm font-medium uppercase tracking-[0.12em] text-amber-300">Current status</p>
                    <p class="mt-2 text-lg font-semibold capitalize text-white">{{ str_replace('_', ' ', $portalStatus) }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Please contact your administrator for assistance with restoring access to this tenant portal.
                    </p>
                </div>

                <p class="mt-6 text-center text-sm text-slate-500">
                    If you believe this is an error, contact your cooperative administrator.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
