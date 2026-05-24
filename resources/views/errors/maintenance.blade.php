<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="60">
    <meta name="theme-color" content="#4f46e5">
    <title>Sedang Maintenance — bukudigi.com</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-12px) rotate(2deg); }
        }
        @keyframes float-slow {
            0%, 100% { transform: translateY(0) rotate(2deg); }
            50% { transform: translateY(-8px) rotate(-3deg); }
        }
        @keyframes blink {
            0%, 90%, 100% { transform: scaleY(1); }
            95% { transform: scaleY(0.1); }
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
        .book-1 { animation: float 4s ease-in-out infinite; }
        .book-2 { animation: float-slow 5s ease-in-out infinite 0.5s; }
        .book-3 { animation: float 4.5s ease-in-out infinite 1s; }
        .eye-blink { animation: blink 4s ease-in-out infinite; transform-origin: center; }
        .dot { animation: pulse-dot 1.4s ease-in-out infinite; }
        .dot-2 { animation-delay: 0.2s; }
        .dot-3 { animation-delay: 0.4s; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-gradient-to-br from-brand-50 via-white to-amber-50 font-sans text-slate-800">

<main class="flex flex-1 items-center justify-center px-6 py-12">
    <div class="w-full max-w-2xl text-center">

        {{-- Floating books illustration --}}
        <div class="relative mx-auto mb-8 h-40 w-64">
            {{-- Book 1 (kiri) --}}
            <div class="book-1 absolute left-0 top-2 flex h-32 w-20 items-center justify-center rounded-r-md bg-gradient-to-br from-brand-400 to-brand-600 shadow-xl">
                <span class="text-4xl">📕</span>
            </div>
            {{-- Book 2 (tengah, depan, dengan muka) --}}
            <div class="book-2 absolute left-1/2 top-0 z-10 flex h-36 w-24 -translate-x-1/2 flex-col items-center justify-center rounded-md bg-gradient-to-br from-amber-300 to-amber-500 shadow-2xl">
                <div class="flex gap-2.5">
                    <div class="eye-blink h-2 w-2 rounded-full bg-slate-800"></div>
                    <div class="eye-blink h-2 w-2 rounded-full bg-slate-800"></div>
                </div>
                <div class="mt-2 h-1.5 w-4 rounded-full bg-slate-800"></div>
            </div>
            {{-- Book 3 (kanan) --}}
            <div class="book-3 absolute right-0 top-3 flex h-32 w-20 items-center justify-center rounded-l-md bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-xl">
                <span class="text-4xl">📗</span>
            </div>
        </div>

        {{-- Brand logo --}}
        <a href="{{ url('/') }}" class="inline-block">
            <img src="{{ asset('logo.png') }}" alt="bukudigi.com"
                 width="1200" height="279"
                 class="h-auto w-32 mx-auto opacity-90">
        </a>

        {{-- Status pill --}}
        <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-1.5 text-xs font-semibold text-amber-800">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-600"></span>
            </span>
            MAINTENANCE MODE
        </div>

        {{-- Title --}}
        <h1 class="mt-5 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
            {{ $title }}
        </h1>

        {{-- Loading dots --}}
        <div class="mt-3 flex items-center justify-center gap-1.5 text-brand-600">
            <span class="dot h-2 w-2 rounded-full bg-brand-500"></span>
            <span class="dot dot-2 h-2 w-2 rounded-full bg-brand-500"></span>
            <span class="dot dot-3 h-2 w-2 rounded-full bg-brand-500"></span>
        </div>

        {{-- Message --}}
        <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-slate-600 md:text-lg">
            {{ $message }}
        </p>

        {{-- Countdown / Estimated end --}}
        @if($estimatedEnd)
            @php
                try {
                    $end = \Illuminate\Support\Carbon::parse($estimatedEnd);
                    $now = now();
                    $isPast = $end->lte($now);
                } catch (\Throwable $e) {
                    $end = null;
                    $isPast = true;
                }
            @endphp
            @if($end && ! $isPast)
                <div class="mx-auto mt-6 inline-block rounded-xl border border-brand-200 bg-white px-5 py-3 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perkiraan selesai</p>
                    <p class="mt-1 text-lg font-bold text-brand-700">
                        {{ $end->format('d M Y · H:i') }} WIB
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $end->diffForHumans($now, ['parts' => 2]) }}</p>
                </div>
            @endif
        @endif

        {{-- Actions --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <button onclick="location.reload()"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Coba Lagi
            </button>

            @if($showContact)
                <a href="mailto:support@bukudigi.com"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-brand-400 hover:text-brand-600">
                    ✉️ Hubungi Support
                </a>
            @endif
        </div>

        {{-- Auto-refresh notice --}}
        <p class="mt-8 text-xs text-slate-400">
            Halaman ini akan otomatis refresh tiap 60 detik. Kalau sudah selesai, langsung balik normal.
        </p>

        {{-- Quote --}}
        <p class="mt-12 text-xs italic text-slate-400">
            "Membaca tanpa merefleksikan, sama seperti makan tanpa mencerna."<br>
            <span class="text-[10px] text-slate-300">— Edmund Burke</span>
        </p>
    </div>
</main>

<footer class="border-t border-slate-200/60 bg-white/50 px-6 py-4 text-center text-xs text-slate-500 backdrop-blur">
    © {{ date('Y') }} bukudigi.com · Marketplace ebook PDF & EPUB Indonesia
</footer>

</body>
</html>
