<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Masuk' }} — bukudigi.com</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">

<div class="grid min-h-screen md:grid-cols-2">
    {{-- Left: brand panel --}}
    <aside class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800 p-10 text-white md:flex">
        <div class="absolute inset-0 opacity-20" aria-hidden="true">
            <svg class="absolute -right-20 -top-20 h-96 w-96" fill="none" viewBox="0 0 200 200"><defs><pattern id="p" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="white"/></pattern></defs><rect width="200" height="200" fill="url(#p)"/></svg>
        </div>

        <a href="{{ route('home') }}" class="relative flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 backdrop-blur">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
            </span>
            <span class="text-xl font-bold tracking-tight">bukudigi<span class="text-brand-200">.com</span></span>
        </a>

        <div class="relative">
            <h2 class="text-3xl font-bold leading-tight">Ebook PDF dari penulis Indonesia,<br>untuk pembaca Indonesia.</h2>
            <p class="mt-4 max-w-md text-brand-100">
                Bayar pakai QRIS, instan download, watermark personal di setiap halaman. Dukung penulis lokal — komisi adil 80% untuk mereka.
            </p>
            <div class="mt-8 flex items-center gap-6 text-sm text-brand-100">
                <div>📚 Harga mulai <span class="font-bold text-white">Rp 15.000</span></div>
                <div>🔒 Watermark <span class="font-bold text-white">personal</span></div>
            </div>
        </div>

        <p class="relative text-xs text-brand-200">© {{ date('Y') }} bukudigi.com</p>
    </aside>

    {{-- Right: form panel --}}
    <main class="flex flex-col justify-center px-6 py-10 sm:px-10">
        <div class="mx-auto w-full max-w-sm">
            {{-- Mobile logo --}}
            <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2 md:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                </span>
                <span class="text-lg font-bold">bukudigi<span class="text-brand-600">.com</span></span>
            </a>

            {{ $slot }}
        </div>
    </main>
</div>

</body>
</html>
