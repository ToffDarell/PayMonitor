<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply | PayMonitor</title>

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
        body { font-family: 'Inter', sans-serif; background-color: #0B1120; }
        .bg-grid {
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
        }
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
            <div>
                <a href="/login" class="text-sm font-semibold text-navy-muted hover:text-white transition-colors">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="relative flex-1 flex items-center justify-center pt-24 pb-12 px-6">
        <div class="absolute inset-0 z-0 bg-grid"></div>
        <div class="absolute top-1/2 left-1/2 z-0 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/5 blur-[100px]"></div>

        <div class="relative z-10 w-full max-w-lg rounded-3xl border border-navy-border bg-navy-surface p-8 backdrop-blur-sm">
            <h1 class="font-heading text-3xl font-bold text-white mb-2">Apply for PayMonitor</h1>
            <p class="text-navy-muted mb-8">Ready to modernize your cooperative? Fill out the form below to get started.</p>

            <form action="{{ route('apply.store', absolute: false) }}" method="POST" class="space-y-5">
                @csrf

                @php($activeSelectedPlan = (string) old('plan', $selectedPlan))

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Selected Plan</label>
                    <div class="relative">
                        <select name="plan" class="w-full appearance-none rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @forelse ($plans as $plan)
                                @php($priceLabel = rtrim(rtrim(number_format((float) $plan->price, 2), '0'), '.'))
                                <option value="{{ $plan->id }}" {{ $activeSelectedPlan === (string) $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} Plan - &#8369;{{ $priceLabel }}/mo
                                </option>
                            @empty
                                <option value="">No plans available yet</option>
                            @endforelse
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                    @error('plan')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Cooperative Name</label>
                    <input type="text" name="cooperative_name" required class="w-full rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" placeholder="e.g. Metro Manila Lending Coop">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">CDA Registration Number</label>
                    <input type="text" name="cda_registration_number" class="w-full rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" placeholder="e.g. CDA-2024-001234">
                    <p class="mt-1 text-xs text-navy-muted">Found on your CDA certificate of registration</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">First Name</label>
                        <input type="text" name="first_name" required class="w-full rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Last Name</label>
                        <input type="text" name="last_name" required class="w-full rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Work Email</label>
                    <input type="email" name="email" required class="w-full rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" placeholder="you@cooperative.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Phone Number</label>
                    <input type="text" name="phone" required class="w-full rounded-xl border border-navy-border bg-[#0A1628] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" placeholder="+63 917 000 0000">
                </div>

                <button type="submit" class="mt-8 block w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 py-3 text-center font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all hover:shadow-emerald-500/30 hover:brightness-110">
                    Submit Application
                </button>
            </form>
        </div>
    </main>
</body>
</html>
