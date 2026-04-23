<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PayMonitor — Multi-Tenant Lending Cooperative SaaS</title>

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
                            surface: 'rgba(255, 255, 255, 0.03)',
                            border: 'rgba(255, 255, 255, 0.08)',
                            muted: '#94a3b8',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background-color: #0B1120; }
        
        .bg-grid {
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp 0.6s ease forwards; }
        .fade-up-delay { animation: fadeUp 0.6s ease 0.2s forwards; opacity: 0; }
        .fade-up-delay-2 { animation: fadeUp 0.6s ease 0.4s forwards; opacity: 0; }
    </style>
</head>
<body class="text-white antialiased">
    
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 border-b border-navy-border bg-[#0B1120]/80 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <a href="#" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <span class="font-heading text-xl font-bold text-white tracking-tight">PayMonitor</span>
            </a>
            
            <div class="hidden items-center gap-8 md:flex">
                <a href="#features" class="text-sm font-medium text-navy-muted transition-colors duration-150 hover:text-white">Features</a>
                <a href="#how-it-works" class="text-sm font-medium text-navy-muted transition-colors duration-150 hover:text-white">How It Works</a>
                <a href="#pricing" class="text-sm font-medium text-navy-muted transition-colors duration-150 hover:text-white">Pricing</a>
            </div>

            <div>
                <a href="/login" class="rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all hover:shadow-emerald-500/30 hover:brightness-110">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative flex min-h-screen items-center justify-center overflow-hidden bg-navy-base pt-16">
        <div class="absolute inset-0 z-0 bg-grid"></div>
        <div class="absolute top-1/2 left-1/2 z-0 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/5 blur-[100px]"></div>
        
        <div class="relative z-10 mx-auto max-w-4xl px-6 text-center">
            
            <div class="fade-up mb-8 inline-flex items-center gap-2 rounded-full border border-navy-border bg-navy-surface px-4 py-1.5 text-sm text-navy-muted">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                Trusted by Philippine Cooperatives
            </div>

            <h1 class="font-heading fade-up-delay mb-6 text-6xl font-extrabold leading-tight tracking-tight text-white lg:text-7xl">
                Lending made<br>
                <span class="bg-gradient-to-r from-emerald-400 via-emerald-300 to-teal-200 bg-clip-text text-transparent">simple & powerful</span>
            </h1>

            <p class="fade-up-delay-2 mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-navy-muted">
                The all-in-one platform that helps cooperatives manage loans, track payments, and grow — with isolated data per branch and real-time insights.
            </p>

            <div class="fade-up-delay-2 flex flex-wrap justify-center gap-4">
                <a href="/apply" class="rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all hover:shadow-emerald-500/30 hover:brightness-110">
                    Apply Now
                </a>
                <a href="#features" class="rounded-xl border border-navy-border bg-navy-surface px-8 py-3.5 font-semibold text-white transition-colors duration-200 hover:bg-white/5">
                    See Features
                </a>
            </div>

            <div class="mt-16 animate-bounce text-navy-muted flex justify-center">
               <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
               </svg>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <section class="border-y border-navy-border bg-navy-base py-8 relative z-20">
        <div class="mx-auto grid max-w-5xl grid-cols-1 divide-y divide-navy-border text-center md:grid-cols-3 md:divide-x md:divide-y-0">
            <div class="py-4 md:py-0">
                <div class="font-heading text-4xl font-bold text-white">500+</div>
                <div class="mt-1 text-sm text-navy-muted">Cooperatives Served</div>
            </div>
            <div class="py-4 md:py-0">
                <div class="font-heading text-4xl font-bold text-white">₱2M+</div>
                <div class="mt-1 text-sm text-navy-muted">Loans Tracked</div>
            </div>
            <div class="py-4 md:py-0">
                <div class="font-heading text-4xl font-bold text-white">99.9%</div>
                <div class="mt-1 text-sm text-navy-muted">Uptime Guaranteed</div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="bg-navy-base py-24 relative overflow-hidden">
        <div class="absolute -right-64 top-0 h-[500px] w-[500px] rounded-full bg-emerald-500/5 blur-[100px]"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-6">
            <div class="mb-3 text-sm font-semibold uppercase tracking-widest text-emerald-400">FEATURES</div>
            <h2 class="font-heading mb-4 text-4xl font-bold text-white">Everything your cooperative needs</h2>
            <p class="mb-16 text-navy-muted">Built specifically for Philippine lending cooperatives.</p>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                
                <div class="rounded-2xl border border-navy-border bg-navy-surface p-6 backdrop-blur-sm transition-colors duration-200 hover:border-emerald-500/50">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M16 6v6h6M9 13h6M9 17h6"></path></svg>
                    </div>
                    <h3 class="mb-2 text-base font-semibold text-white">Loan Management</h3>
                    <p class="text-sm leading-relaxed text-navy-muted">Create and track loans with automatic interest computation and amortization schedules.</p>
                </div>

                <div class="rounded-2xl border border-navy-border bg-navy-surface p-6 backdrop-blur-sm transition-colors duration-200 hover:border-emerald-500/50">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="mb-2 text-base font-semibold text-white">Payment Tracking</h3>
                    <p class="text-sm leading-relaxed text-navy-muted">Record payments and instantly see outstanding balances update in real time.</p>
                </div>

                <div class="rounded-2xl border border-navy-border bg-navy-surface p-6 backdrop-blur-sm transition-colors duration-200 hover:border-emerald-500/50">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="mb-2 text-base font-semibold text-white">Member Management</h3>
                    <p class="text-sm leading-relaxed text-navy-muted">Keep complete borrower profiles and full loan history in one organized place.</p>
                </div>

                <div class="rounded-2xl border border-navy-border bg-navy-surface p-6 backdrop-blur-sm transition-colors duration-200 hover:border-emerald-500/50">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="mb-2 text-base font-semibold text-white">Multi-Branch Support</h3>
                    <p class="text-sm leading-relaxed text-navy-muted">Manage multiple branches under a single cooperative account effortlessly.</p>
                </div>

                <div class="rounded-2xl border border-navy-border bg-navy-surface p-6 backdrop-blur-sm transition-colors duration-200 hover:border-emerald-500/50">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="mb-2 text-base font-semibold text-white">Reports & Analytics</h3>
                    <p class="text-sm leading-relaxed text-navy-muted">Track collections, overdue accounts, and interest income with clear reports.</p>
                </div>

                <div class="rounded-2xl border border-navy-border bg-navy-surface p-6 backdrop-blur-sm transition-colors duration-200 hover:border-emerald-500/50">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h3 class="mb-2 text-base font-semibold text-white">Role-Based Access</h3>
                    <p class="text-sm leading-relaxed text-navy-muted">Define exactly what each staff member can see and do in the system.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="bg-navy-base py-24 border-t border-navy-border">
        <div class="mx-auto max-w-7xl px-6 text-center">
            <div class="mb-3 text-sm font-semibold uppercase tracking-widest text-emerald-400">HOW IT WORKS</div>
            <h2 class="font-heading mb-2 text-4xl font-bold text-white">Up and running in 24 hours</h2>
            <p class="mb-16 text-navy-muted">Simple process, no technical setup required.</p>

            <div class="relative mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-12 md:grid-cols-3 md:gap-6">
                <!-- Connecting Line -->
                <div class="absolute left-1/6 right-1/6 top-6 hidden border-t border-dashed border-navy-muted/30 md:block"></div>

                <div class="relative z-10 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-lg font-bold text-emerald-400 backdrop-blur-sm">1</div>
                    <h3 class="mb-2 font-semibold text-white">Submit Application</h3>
                    <p class="mx-auto max-w-xs text-sm text-navy-muted">Fill out the application form and select your preferred plan.</p>
                </div>

                <div class="relative z-10 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-lg font-bold text-emerald-400 backdrop-blur-sm">2</div>
                    <h3 class="mb-2 font-semibold text-white">Get Approved</h3>
                    <p class="mx-auto max-w-xs text-sm text-navy-muted">Our team reviews and approves your application within 24 hours.</p>
                </div>

                <div class="relative z-10 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-lg font-bold text-emerald-400 backdrop-blur-sm">3</div>
                    <h3 class="mb-2 font-semibold text-white">Go Live</h3>
                    <p class="mx-auto max-w-xs text-sm text-navy-muted">Receive your login credentials and start managing loans immediately.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="bg-[#0f1523] py-24">
        @php
            $featuredPlan = $plans->first(fn ($plan) => strtolower($plan->name) === 'standard')
                ?? $plans->values()->get(1)
                ?? $plans->first();
        @endphp
        <div class="mx-auto max-w-7xl px-6">
            <div class="text-center mb-16">
                <div class="mb-3 text-sm font-semibold uppercase tracking-widest text-emerald-400">PRICING</div>
                <h2 class="font-heading mb-2 text-4xl font-bold text-white">Simple, transparent pricing</h2>
                <p class="text-navy-muted">Choose the plan that fits your cooperative.</p>
            </div>

            @if ($plans->isNotEmpty())
                <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($plans as $plan)
                        @php
                            $isFeatured = $featuredPlan?->is($plan) ?? false;
                            $priceLabel = rtrim(rtrim(number_format((float) $plan->price, 2), '0'), '.');
                            $descriptionItems = collect(preg_split('/\r\n|\r|\n/', (string) $plan->description))
                                ->map(fn ($item) => trim($item))
                                ->filter();
                            $limitItems = collect([
                                $plan->max_branches === 0
                                    ? 'Unlimited Branches'
                                    : number_format($plan->max_branches) . ' ' . \Illuminate\Support\Str::plural('Branch', $plan->max_branches),
                                $plan->max_users === 0
                                    ? 'Unlimited Staff Users'
                                    : number_format($plan->max_users) . ' Staff ' . \Illuminate\Support\Str::plural('User', $plan->max_users),
                            ]);
                            $allAvailable = \App\Models\Plan::getAvailableFeatures();
                            $checkedFeatures = collect($plan->features ?? [])
                                ->map(fn ($key) => $allAvailable[$key]['name'] ?? null)
                                ->filter()
                                ->values();

                            $featureItems = $limitItems->concat($checkedFeatures);
                        @endphp

                        <div @class([
                            'rounded-3xl border p-8 backdrop-blur-sm',
                            'border-navy-border bg-navy-surface' => ! $isFeatured,
                            'relative border-emerald-500/50 bg-[#0A1628] shadow-[0_0_40px_rgba(16,185,129,0.1)]' => $isFeatured,
                        ])>
                            @if ($isFeatured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-emerald-500 px-4 py-1 text-xs font-semibold text-white shadow-lg shadow-emerald-500/20">Most Popular</div>
                            @endif

                            <div @class([
                                'mb-2 text-sm font-medium uppercase tracking-wide',
                                'text-navy-muted' => ! $isFeatured,
                                'text-emerald-400' => $isFeatured,
                            ])>{{ $plan->name }}</div>

                            <div class="font-heading text-4xl font-black text-white">&#8369;{{ $priceLabel }}<span class="text-lg font-normal text-navy-muted">/mo</span></div>
                            <hr class="my-6 border-navy-border" />

                            <ul class="space-y-4">
                                @foreach ($featureItems as $feature)
                                    <li @class([
                                        'flex items-center gap-3 text-sm',
                                        'text-slate-300' => ! $isFeatured,
                                        'text-slate-200' => $isFeatured,
                                    ])>
                                        <span @class([
                                            'mt-0.5 flex h-5 w-5 items-center justify-center rounded-full text-emerald-400',
                                            'bg-emerald-500/15' => ! $isFeatured,
                                            'bg-emerald-500/20' => $isFeatured,
                                        ])>
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42-.003L4.79 10.75a1 1 0 1 1 1.42-1.41l2.54 2.56 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/></svg>
                                        </span>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ route('apply.create', ['plan' => $plan->id], false) }}" @class([
                                'mt-8 block w-full rounded-xl py-3 text-center transition-colors',
                                'border border-navy-border bg-white/[0.02] font-medium text-white hover:bg-white/5' => ! $isFeatured,
                                'bg-gradient-to-r from-emerald-500 to-emerald-600 font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all hover:shadow-emerald-500/30 hover:brightness-110' => $isFeatured,
                            ])>Apply for {{ $plan->name }}</a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mx-auto max-w-3xl rounded-3xl border border-navy-border bg-navy-surface p-10 text-center text-navy-muted">
                    Pricing plans will appear here once they are added in the central admin.
                </div>
            @endif

        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-navy-border bg-[#0B1120] py-8 text-sm">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between px-6 md:flex-row">
            <div class="flex items-center gap-2 mb-4 md:mb-0">
                <div class="flex h-5 w-5 items-center justify-center rounded bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-sm">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <span class="font-heading font-bold text-white tracking-tight">PayMonitor</span>
                <span class="ml-2 text-navy-muted hidden sm:inline">Built for Philippine cooperatives.</span>
            </div>
            <div class="text-navy-muted text-center">
                &copy; 2026 PayMonitor. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
