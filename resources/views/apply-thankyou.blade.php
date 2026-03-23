<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Received | PayMonitor</title>

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
    <main class="relative flex-1 flex items-center justify-center pt-24 pb-12 px-6 text-center">
        <div class="absolute inset-0 z-0 bg-grid"></div>
        <div class="absolute top-1/2 left-1/2 z-0 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/5 blur-[100px]"></div>

        <div class="relative z-10 w-full max-w-lg">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="font-heading text-4xl font-bold text-white mb-4">Application Received</h1>
            <p class="text-lg text-navy-muted mb-8 leading-relaxed">
                Thank you for applying to PayMonitor! Our team will review your application and get back to you within 24 hours. Keep an eye on your email for the next steps.
            </p>

            <a href="/" class="inline-flex rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all hover:shadow-emerald-500/30 hover:brightness-110">
                Return to Homepage
            </a>
        </div>
    </main>
</body>
</html>