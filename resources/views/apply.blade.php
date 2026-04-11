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
    </style>
</head>
<body class="min-h-screen text-white antialiased">
    @php
        $activeSelectedPlan = (string) old('plan', $selectedPlan);
        $selectedPlanModel = $plans->firstWhere('id', (int) $activeSelectedPlan);
        $selectedPlanName = $selectedPlanModel?->name ?? 'Selected Plan';
        $selectedPlanAmount = $selectedPlanModel
            ? '₱'.rtrim(rtrim(number_format((float) $selectedPlanModel->price, 2), '0'), '.').'/mo'
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
                            Submit your cooperative details, pay the selected subscription, and upload the receipt so central admin can verify everything before approval.
                        </p>

                        <div class="mt-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/8 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Selected Plan</p>
                            <div class="mt-3 flex items-start justify-between gap-4">
                                <div>
                                    <h2 id="selected-plan-name" class="font-heading text-2xl font-bold text-white">{{ $selectedPlanName }}</h2>
                                    <p class="mt-1 text-sm text-slate-400">Pay the same amount shown here before uploading your proof.</p>
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
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">1. Choose your subscription plan.</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">2. Send payment using any channel on the right.</div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-slate-300">3. Upload your receipt and wait for verification.</div>
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

                    <form action="{{ route('apply.store', absolute: false) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                        @csrf

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
                                        <option value="{{ $plan->id }}" data-plan-name="{{ $plan->name }}" data-plan-amount="₱{{ $priceLabel }}/mo" data-plan-features="{{ $planFeatures }}" {{ $activeSelectedPlan === (string) $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }} Plan - ₱{{ $priceLabel }}/mo
                                        </option>
                                    @empty
                                        <option value="">No plans available yet</option>
                                    @endforelse
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">Choose the plan you already paid for, then attach the official proof of payment below.</p>
                        </div>

                        <div class="rounded-[1.5rem] border border-emerald-500/20 bg-emerald-500/6 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Where To Pay</p>
                                    <h2 class="mt-2 font-heading text-2xl font-bold text-white">Payment Instructions</h2>
                                    <p class="mt-2 text-sm leading-7 text-slate-400">Send your selected plan amount first, then upload the receipt below.</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-[#081120] px-4 py-3 text-right">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Plan Amount</p>
                                    <p id="selected-plan-amount-summary" class="mt-2 text-2xl font-bold text-white">{{ $selectedPlanAmount }}</p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                                @php($paymentChannels = [
                                    ['label' => 'GCash', 'hint' => 'Mobile transfer', 'badge' => 'G', 'badgeClass' => 'bg-[#0070DC]/20 text-[#0070DC]', 'number' => env('GCASH_NUMBER', '09XX-XXX-XXXX')],
                                    ['label' => 'BDO Transfer', 'hint' => 'Bank transfer', 'badge' => 'BDO', 'badgeClass' => 'bg-[#CC0000]/20 text-[#CC0000]', 'number' => env('BDO_ACCOUNT', 'XXXX-XXXX-XXXX')],
                                    ['label' => 'BPI Transfer', 'hint' => 'Bank transfer', 'badge' => 'BPI', 'badgeClass' => 'bg-[#C41230]/20 text-[#C41230]', 'number' => env('BPI_ACCOUNT', 'XXXX-XXXX-XXXX')],
                                ])
                                @foreach($paymentChannels as $channel)
                                    <div class="rounded-2xl border border-white/10 bg-[#0A1628] p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-[11px] font-bold {{ $channel['badgeClass'] }}">{{ $channel['badge'] }}</span>
                                                <div>
                                                    <p class="text-sm font-semibold text-white">{{ $channel['label'] }}</p>
                                                    <p class="text-xs text-slate-500">{{ $channel['hint'] }}</p>
                                                </div>
                                            </div>
                                            <button type="button" data-copy="{{ $channel['number'] }}" class="rounded-lg border border-white/10 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:border-emerald-500/40 hover:text-white">Copy</button>
                                        </div>
                                        <p class="mt-4 text-[11px] uppercase tracking-[0.16em] text-slate-500">Account Name</p>
                                        <p class="mt-1 text-sm font-medium text-slate-200">PayMonitor Systems</p>
                                        <p class="mt-3 text-[11px] uppercase tracking-[0.16em] text-slate-500">Account Number</p>
                                        <p class="mt-1 text-sm font-semibold text-white">{{ $channel['number'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 rounded-2xl border border-yellow-500/20 bg-yellow-500/7 px-4 py-3 text-sm text-yellow-200">
                                Use your cooperative name as the payment note or reference if the payment channel allows it.
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-300">Cooperative Name</label>
                                <input type="text" name="cooperative_name" value="{{ old('cooperative_name') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="e.g. Metro Manila Lending Coop">
                                @error('cooperative_name')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">CDA Registration Number</label>
                                <input type="text" name="cda_registration_number" value="{{ old('cda_registration_number') }}" class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="e.g. CDA-2024-001234">
                                <p class="mt-2 text-xs text-slate-500">Found on your CDA certificate of registration.</p>
                                @error('cda_registration_number')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Work Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="you@cooperative.com">
                                @error('email')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="+63 917 000 0000">
                                @error('phone')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                @error('first_name')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                @error('last_name')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Payment Reference</label>
                                <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="w-full rounded-2xl border border-white/10 bg-[#0A1628] px-4 py-3 text-sm text-white transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Optional reference / transaction number">
                                <p class="mt-2 text-xs text-slate-500">If your payment channel gave you a reference number, enter it here.</p>
                                @error('payment_reference')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-300">Proof of Payment</label>
                                <label for="payment_proof" class="block cursor-pointer rounded-2xl border border-dashed border-white/15 bg-[#0A1628] p-4 transition hover:border-emerald-500/50 hover:bg-[#0C1528]">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-white">Upload your receipt</p>
                                            <p class="mt-1 text-xs text-slate-500">Accepted: JPG, PNG, WEBP, PDF up to 5MB.</p>
                                        </div>
                                        <span class="rounded-xl bg-emerald-500/15 px-3 py-2 text-xs font-semibold text-emerald-300">Choose File</span>
                                    </div>
                                    <p id="payment-proof-name" class="mt-4 rounded-xl border border-white/10 bg-white/[0.02] px-3 py-2 text-sm text-slate-300">No file chosen yet</p>
                                </label>
                                <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="sr-only">
                                @error('payment_proof')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                            <p class="max-w-xl text-sm leading-6 text-slate-500">After submission, central admin verifies your payment first. Once approved, your cooperative receives its tenant domain and login details by email.</p>
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:shadow-emerald-500/30 hover:brightness-110">
                                Submit Application & Payment Proof
                            </button>
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
            const selectedPlanAmountSummary = document.getElementById('selected-plan-amount-summary');
            const featureCards = document.querySelectorAll('[data-selected-plan-feature]');
            const paymentProofInput = document.getElementById('payment_proof');
            const paymentProofName = document.getElementById('payment-proof-name');

            const updatePlanSummary = () => {
                const option = planSelect?.options[planSelect.selectedIndex];
                const features = (option?.dataset.planFeatures || '').split('||').filter(Boolean);

                if (selectedPlanName) selectedPlanName.textContent = option?.dataset.planName || 'Selected Plan';
                if (selectedPlanAmount) selectedPlanAmount.textContent = option?.dataset.planAmount || 'Choose a plan';
                if (selectedPlanAmountSummary) selectedPlanAmountSummary.textContent = option?.dataset.planAmount || 'Choose a plan';

                featureCards.forEach((card, index) => {
                    card.textContent = features[index] || 'Included in your selected plan';
                });
            };

            document.querySelectorAll('[data-copy]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const value = button.getAttribute('data-copy') || '';
                    const original = button.textContent;

                    try {
                        await navigator.clipboard.writeText(value);
                        button.textContent = 'Copied!';
                        button.classList.add('border-emerald-500/40', 'text-emerald-300');
                        setTimeout(() => {
                            button.textContent = original;
                            button.classList.remove('border-emerald-500/40', 'text-emerald-300');
                        }, 1600);
                    } catch (_error) {
                        button.textContent = 'Copy failed';
                        setTimeout(() => button.textContent = original, 1600);
                    }
                });
            });

            if (paymentProofInput && paymentProofName) {
                paymentProofInput.addEventListener('change', () => {
                    const file = paymentProofInput.files && paymentProofInput.files[0];
                    paymentProofName.textContent = file ? file.name : 'No file chosen yet';
                });
            }

            if (planSelect) {
                planSelect.addEventListener('change', updatePlanSummary);
                updatePlanSummary();
            }
        });
    </script>
</body>
</html>
