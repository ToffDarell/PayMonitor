<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply | PayMonitor</title>
    <meta name="description" content="Apply for PayMonitor — the cooperative lending management SaaS platform.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        navy: {
                            base: '#0B1120',
                            surface: 'rgba(255,255,255,0.03)',
                            border: 'rgba(255,255,255,0.08)',
                            muted: '#94a3b8',
                        }
                    }
                }
            }
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.09), transparent 24%),
                radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.08), transparent 22%),
                #0B1120;
        }
        .bg-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 38%, transparent 72%);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen text-white antialiased">

    <nav class="fixed inset-x-0 top-0 z-50 border-b border-navy-border bg-[#0B1120]/80 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <a href="/" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <span class="font-heading text-xl font-bold tracking-tight text-white">PayMonitor</span>
            </a>
            <a href="/login" class="text-sm font-semibold text-navy-muted transition-colors hover:text-white">Sign In</a>
        </div>
    </nav>

    <main class="relative px-6 pb-12 pt-24">
        <div class="absolute inset-0 z-0 bg-grid"></div>
        <div class="relative z-10 mx-auto max-w-7xl rounded-[2rem] border border-navy-border bg-navy-surface p-5 backdrop-blur-sm sm:p-8">
            <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
                    <div class="rounded-[1.75rem] border border-white/10 bg-[#0A1628]/75 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Onboarding</p>
                        <h1 class="mt-3 font-heading text-4xl font-bold leading-tight text-white sm:text-5xl">
                            Apply for <span class="block text-emerald-300">PayMonitor</span>
                        </h1>
                        <p class="mt-4 text-base leading-8 text-navy-muted">
                            Submit your cooperative details to get started. Your account will be reviewed and set up within 24 hours.
                        </p>

                        <div class="mt-6 space-y-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">1. Fill in your cooperative details below.</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">2. Submit your application.</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">3. Admin reviews and creates your tenant account.</div>
                        </div>
                    </div>
                </aside>

                <section class="rounded-[1.75rem] border border-white/10 bg-[#111827]/80 p-6 sm:p-8">
                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-500/20 bg-red-500/10 p-4">
                            <p class="text-sm font-semibold text-red-300">Please review the form.</p>
                            <ul class="mt-2 space-y-1 text-sm text-red-200/90">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="mb-6 rounded-2xl border border-yellow-500/20 bg-yellow-500/10 p-4">
                            <p class="text-sm font-semibold text-yellow-300">{{ session('warning') }}</p>
                        </div>
                    @endif

                    <form id="apply-form" action="{{ route('apply.store', absolute: false) }}" method="POST" class="space-y-8" x-data="{ cycle: '{{ $cycle }}' }">
                        @csrf

                        <input type="hidden" name="plan" :value="cycle === 'monthly' ? {{ $monthlyPlan->id }} : {{ $yearlyPlan->id }}">

                        {{-- Billing cycle toggle --}}
                        <div>
                            <label class="mb-3 block text-sm font-medium text-slate-300">Billing Cycle</label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-medium" :class="cycle === 'monthly' ? 'text-white' : 'text-slate-500'">Monthly</span>
                                <button type="button" @click="cycle = cycle === 'monthly' ? 'yearly' : 'monthly'"
                                    class="relative h-7 w-12 rounded-full border border-white/10 bg-white/5 transition-colors"
                                    :class="cycle === 'yearly' ? 'bg-emerald-500/20 border-emerald-500/40' : ''">
                                    <span class="absolute top-0.5 left-0.5 h-6 w-6 rounded-full bg-white transition-transform duration-200 shadow"
                                          :class="cycle === 'yearly' ? 'translate-x-5' : ''"></span>
                                </button>
                                <span class="text-sm font-medium" :class="cycle === 'yearly' ? 'text-white' : 'text-slate-500'">Yearly</span>
                            </div>
                        </div>

                        {{-- Cooperative details --}}
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-300">Cooperative Name</label>
                                <input type="text" name="cooperative_name" id="cooperative_name" value="{{ old('cooperative_name') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="e.g. Metro Manila Lending Coop">
                                @error('cooperative_name')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">CDA Registration Number</label>
                                <input type="text" name="cda_registration_number" id="cda_registration_number" value="{{ old('cda_registration_number') }}" class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="e.g. CDA-2024-001234">
                                <p class="mt-2 text-xs text-slate-500">Found on your CDA certificate of registration.</p>
                                @error('cda_registration_number')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Work Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="you@cooperative.com">
                                @error('email')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="+63 917 000 0000">
                                @error('phone')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">First Name</label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                @error('first_name')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Last Name</label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                @error('last_name')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="space-y-3">
                            <button
                                id="submit-btn"
                                type="submit"
                                class="w-full rounded-xl py-3.5 text-sm font-semibold text-white transition-all bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:brightness-110"
                            >
                                Submit Application
                            </button>

                            <div class="text-center text-xs text-slate-500">
                                After submission, central admin will review and set up your account.
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('apply-form')?.addEventListener('submit', function() {
                const btn = document.getElementById('submit-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('opacity-70', 'cursor-not-allowed');
                    btn.textContent = 'Processing…';
                }
            });
        });
    </script>
</body>
</html>
