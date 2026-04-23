<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply | PayMonitor</title>
    <meta name="description" content="Apply for PayMonitor — the cooperative lending management SaaS platform. Select your plan and pay securely via PayMongo.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
    @php
        $activeSelectedPlan = (string) old('plan', $selectedPlan);
        $selectedPlanModel = $plans->firstWhere('id', (int) $activeSelectedPlan);
        $selectedPlanName = $selectedPlanModel?->name ?? 'Selected Plan';
        $selectedPlanPrice = (float) ($selectedPlanModel?->price ?? 0);
        $selectedPlanAmount = $selectedPlanModel
            ? '₱'.rtrim(rtrim(number_format($selectedPlanPrice, 2), '0'), '.').'/mo'
            : 'Choose a plan';
        $selectedPlanFeatures = collect(
            preg_split('/\r\n|\r|\n/', (string) ($selectedPlanModel?->description ?: \App\Models\Plan::defaultDescription()))
        )->filter()->values()->take(3)->pad(3, 'Included in your selected plan');
    @endphp

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
                            Submit your cooperative details and pay securely via PayMongo to complete your application. Admin will review and provision your tenant account within 24 hours.
                        </p>

                        <div class="mt-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/8 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Selected Plan</p>
                            <div class="mt-3 flex items-start justify-between gap-4">
                                <div>
                                    <h2 id="selected-plan-name" class="font-heading text-2xl font-bold text-white">{{ $selectedPlanName }}</h2>
                                    <p class="mt-1 text-sm text-slate-400">You'll be billed this amount monthly.</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-[#081120] px-4 py-3 text-right">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Amount</p>
                                    <p id="selected-plan-amount" class="mt-2 text-2xl font-bold text-white">{{ $selectedPlanAmount }}</p>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                @foreach($selectedPlanFeatures as $feature)
                                    <div data-selected-plan-feature class="rounded-xl border border-white/10 bg-white/[0.02] px-3 py-2 text-sm text-slate-300">{{ $feature }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">1. Fill in your cooperative details below.</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">2. Select your plan and click "Continue to Payment".</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">3. Complete payment on PayMongo's secure checkout.</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">4. You're redirected back — admin reviews and creates your account.</div>
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

                    <form id="apply-form" action="{{ route('apply.store', absolute: false) }}" method="POST" class="space-y-8">
                        @csrf

                        {{-- Plan selector --}}
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-300">Selected Plan</label>
                            <div class="relative">
                                <select id="plan" name="plan" class="w-full appearance-none rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3.5 text-sm font-medium text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                    @forelse ($plans as $plan)
                                        @php
                                            $priceLabel = rtrim(rtrim(number_format((float) $plan->price, 2), '0'), '.');
                                            $planFeatures = collect(
                                                preg_split('/\r\n|\r|\n/', (string) ($plan->description ?: \App\Models\Plan::defaultDescription()))
                                            )->filter()->values()->take(3)->pad(3, 'Included in your selected plan')->implode('||');
                                        @endphp
                                        <option
                                            value="{{ $plan->id }}"
                                            data-plan-name="{{ $plan->name }}"
                                            data-plan-price="{{ $plan->price }}"
                                            data-plan-amount="{{ $priceLabel == '0' ? 'Free' : '₱'.$priceLabel.'/mo' }}"
                                            data-plan-features="{{ $planFeatures }}"
                                            {{ $activeSelectedPlan === (string) $plan->id ? 'selected' : '' }}
                                        >
                                            {{ $plan->name }} Plan — {{ $priceLabel == '0' ? 'Free' : '₱'.$priceLabel.'/mo' }}
                                        </option>
                                    @empty
                                        <option value="">No plans available yet</option>
                                    @endforelse
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
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

                        {{-- Payment Summary --}}
                        <div id="payment-summary" class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
                            <p class="text-sm font-semibold uppercase tracking-wider text-slate-300 mb-4">Payment Summary</p>

                            {{-- Free plan --}}
                            <div id="free-plan-notice" class="hidden rounded-xl border border-emerald-500/20 bg-emerald-500/8 p-4 text-sm text-emerald-300">
                                <svg class="inline-block mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                This is a free plan. No payment required.
                            </div>

                            {{-- Paid plan --}}
                            <div id="paid-plan-details">
                                <div class="flex items-center justify-between gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-[#8b949e] mb-1">Selected Plan</p>
                                        <div class="flex items-center gap-2">
                                            <span id="summary-plan-name" class="text-white font-semibold text-sm">{{ $selectedPlanName }}</span>
                                            <span id="summary-plan-badge" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $selectedPlanName }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-[#8b949e] mb-1">Amount Due</p>
                                        <p id="summary-plan-amount" class="text-white font-bold text-lg">{{ $selectedPlanAmount }}</p>
                                    </div>
                                </div>

                                <div class="border-t border-[#21262d] pt-4 mb-4">
                                    <p class="text-xs text-[#8b949e] mb-2">Payment Methods Accepted</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#0070DC]/10 border border-[#0070DC]/20 text-[#4DA6FF] text-xs font-semibold">
                                            <span class="h-4 w-4 rounded-full bg-[#0070DC]/20 inline-flex items-center justify-center text-[9px] font-bold text-[#0070DC]">G</span>
                                            GCash
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 text-xs font-semibold">
                                            <span class="h-4 w-4 rounded-full bg-green-500/20 inline-flex items-center justify-center text-[9px] font-bold text-green-400">M</span>
                                            Maya
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            Credit Card
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 text-xs font-semibold">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            Debit Card
                                        </span>
                                    </div>
                                    <p class="mt-2 text-[11px] text-[#8b949e]">Powered by PayMongo · Test mode</p>
                                </div>

                                <p class="text-[#8b949e] text-xs">
                                    You will be redirected to a secure PayMongo checkout page to complete payment.
                                    <strong class="text-yellow-400">Test mode: no real money will be charged.</strong>
                                </p>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="space-y-3">
                            <button
                                id="submit-btn"
                                type="submit"
                                class="w-full rounded-xl py-3.5 text-sm font-semibold text-white transition-all bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:brightness-110"
                            >
                                <span id="submit-btn-text">Continue to Payment →</span>
                            </button>

                            <div id="paymongo-badge" class="flex items-center justify-center gap-1.5 text-[#8b949e] text-xs">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Secured by PayMongo — Philippine payment gateway
                            </div>

                            <div id="free-submit-note" class="hidden text-center text-xs text-slate-500">
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
            const planSelect = document.getElementById('plan');
            const selectedPlanName = document.getElementById('selected-plan-name');
            const selectedPlanAmount = document.getElementById('selected-plan-amount');
            const featureCards = document.querySelectorAll('[data-selected-plan-feature]');

            // Payment summary elements
            const summaryPlanName   = document.getElementById('summary-plan-name');
            const summaryPlanBadge  = document.getElementById('summary-plan-badge');
            const summaryPlanAmount = document.getElementById('summary-plan-amount');
            const freePlanNotice    = document.getElementById('free-plan-notice');
            const paidPlanDetails   = document.getElementById('paid-plan-details');
            const submitBtn         = document.getElementById('submit-btn');
            const submitBtnText     = document.getElementById('submit-btn-text');
            const paymongoBadge     = document.getElementById('paymongo-badge');
            const freeSubmitNote    = document.getElementById('free-submit-note');

            const updatePlanSummary = () => {
                const option    = planSelect?.options[planSelect.selectedIndex];
                const price     = parseFloat(option?.dataset.planPrice || '0');
                const name      = option?.dataset.planName || 'Selected Plan';
                const amount    = option?.dataset.planAmount || '—';
                const features  = (option?.dataset.planFeatures || '').split('||').filter(Boolean);
                const isFree    = price === 0;

                // Sidebar
                if (selectedPlanName)   selectedPlanName.textContent   = name;
                if (selectedPlanAmount) selectedPlanAmount.textContent = isFree ? 'Free' : amount;

                featureCards.forEach((card, index) => {
                    card.textContent = features[index] || 'Included in your selected plan';
                });

                // Payment summary card
                if (summaryPlanName)   summaryPlanName.textContent   = name;
                if (summaryPlanBadge)  summaryPlanBadge.textContent  = name;
                if (summaryPlanAmount) summaryPlanAmount.textContent = isFree ? 'Free' : amount;

                if (isFree) {
                    freePlanNotice?.classList.remove('hidden');
                    paidPlanDetails?.classList.add('hidden');
                    submitBtnText && (submitBtnText.textContent = 'Submit Application');
                    submitBtn?.classList.remove('from-emerald-500', 'to-emerald-600');
                    submitBtn?.classList.add('from-slate-600', 'to-slate-700');
                    paymongoBadge?.classList.add('hidden');
                    freeSubmitNote?.classList.remove('hidden');
                } else {
                    freePlanNotice?.classList.add('hidden');
                    paidPlanDetails?.classList.remove('hidden');
                    submitBtnText && (submitBtnText.textContent = 'Continue to Payment →');
                    submitBtn?.classList.add('from-emerald-500', 'to-emerald-600');
                    submitBtn?.classList.remove('from-slate-600', 'to-slate-700');
                    paymongoBadge?.classList.remove('hidden');
                    freeSubmitNote?.classList.add('hidden');
                }
            };

            if (planSelect) {
                planSelect.addEventListener('change', updatePlanSummary);
                updatePlanSummary();
            }

            // Prevent double-submit
            document.getElementById('apply-form')?.addEventListener('submit', function() {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                    submitBtnText && (submitBtnText.textContent = 'Processing…');
                }
            });
        });
    </script>
</body>
</html>
