<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Pending | PayMonitor</title>
    <meta name="description" content="Your PayMonitor application payment is being processed.">
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
        body { font-family: 'Inter', sans-serif; background-color: #0B1120; }
        .bg-grid {
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
        }
        @keyframes pulse-ring {
            0%   { transform: scale(0.9); opacity: 0.8; }
            50%  { transform: scale(1.05); opacity: 0.4; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }
        .pulse-ring { animation: pulse-ring 2s ease-in-out infinite; }
    </style>
</head>
<body class="text-white antialiased min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 border-b border-navy-border bg-[#0B1120]/80 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <a href="/" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-sm">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <span class="font-heading text-xl font-bold text-white tracking-tight">PayMonitor</span>
            </a>
            <a href="/login" class="text-sm font-semibold text-navy-muted hover:text-white transition-colors">Sign In</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="relative flex-1 flex items-center justify-center pt-24 pb-12 px-6">
        <div class="absolute inset-0 z-0 bg-grid"></div>
        <div class="absolute top-1/2 left-1/2 z-0 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-yellow-500/5 blur-[100px]"></div>

        <div class="relative z-10 w-full max-w-lg">

            @if(session('error'))
                <div class="mb-6 rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-center">
                    <p class="text-sm font-semibold text-red-300">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Icon -->
            <div class="mx-auto mb-6 relative w-24 h-24 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full bg-yellow-500/10 pulse-ring"></div>
                <div class="relative flex h-20 w-20 items-center justify-center rounded-2xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-400">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Heading -->
            <h1 class="font-heading text-4xl font-bold text-white text-center mb-3">Payment Pending</h1>
            <p class="text-navy-muted text-center mb-8 leading-relaxed">
                Your payment is being processed. Once confirmed, your application will move forward for review.
            </p>

            <!-- Application Details -->
            <div class="rounded-2xl border border-white/10 bg-[#111827]/80 p-6 mb-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Application Details</p>
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-slate-400">Cooperative</dt>
                        <dd class="text-sm font-semibold text-white">{{ $application->cooperative_name }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-slate-400">Plan</dt>
                        <dd class="text-sm font-semibold text-white">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                {{ $application->plan?->name ?? 'N/A' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-slate-400">Amount</dt>
                        <dd class="text-sm font-bold text-white">
                            ₱{{ number_format((float) ($application->amount_paid ?? $application->plan?->price ?? 0), 2) }}/month
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-slate-400">Payment Status</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-yellow-400/10 text-yellow-500 border border-yellow-400/20">
                                <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                Pending Verification
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Actions -->
            <div class="space-y-3 mb-6">
                <form id="check-form" action="{{ route('apply.verify-payment', $application->id, false) }}" method="POST">
                    @csrf
                    <button
                        id="check-btn"
                        type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-yellow-500 to-amber-500 py-3.5 text-sm font-semibold text-black shadow-lg shadow-yellow-500/20 hover:brightness-110 transition-all"
                    >
                        Check Payment Status
                    </button>
                </form>

                @if($application->payment_url)
                    <a
                        href="{{ $application->payment_url }}"
                        target="_blank"
                        rel="noopener"
                        class="w-full inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] py-3.5 text-sm font-semibold text-slate-300 hover:bg-white/[0.06] hover:text-white transition-all"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Return to PayMongo Checkout
                    </a>
                @endif
            </div>

            <!-- Help note -->
            <div class="rounded-2xl border border-blue-500/15 bg-blue-500/5 p-4 mb-4">
                <p class="text-xs text-slate-400 leading-relaxed">
                    <strong class="text-slate-200">Already paid on PayMongo?</strong>
                    Click <em>Check Payment Status</em> above — it queries PayMongo's API in real time and will confirm your payment immediately.
                </p>
            </div>

            <p class="text-center text-xs text-[#8b949e]">
                Need help? Contact us at
                <a href="mailto:{{ config('app.support_email', 'support@paymonitor.com') }}" class="text-emerald-400 hover:underline">
                    {{ config('app.support_email', 'support@paymonitor.com') }}
                </a>
            </p>
        </div>
    </main>

    <script>
        // Prevent double-submit
        document.getElementById('check-form')?.addEventListener('submit', function() {
            const btn = document.getElementById('check-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Checking…';
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            }
        });
    </script>
</body>
</html>
