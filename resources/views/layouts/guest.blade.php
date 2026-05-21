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

        <a href="{{ route('home') }}" class="relative inline-flex w-fit rounded-lg bg-white/95 px-4 py-2 shadow-sm backdrop-blur">
            <img src="{{ asset('logo.png') }}" alt="BukuDigi.com"
                 width="1200" height="279"
                 class="h-auto w-40">
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
            <a href="{{ route('home') }}" class="mb-8 inline-flex md:hidden">
                <img src="{{ asset('logo.png') }}" alt="BukuDigi.com"
                     width="1200" height="279"
                     class="h-auto w-40">
            </a>

            {{ $slot }}
        </div>
    </main>
</div>

</body>
</html>
