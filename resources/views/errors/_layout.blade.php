{{-- Shared error layout. Pass: emoji, code, title, message, primaryAction (label, url), showBackButton --}}
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} · {{ $title }} — bukudigi.com</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-gradient-to-br from-slate-50 via-white to-brand-50 font-sans text-slate-900 antialiased">

<main class="flex flex-1 items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg text-center">
        <a href="{{ url('/') }}" class="mb-8 inline-flex" aria-label="BukuDigi.com">
            <img src="{{ asset('logo.png') }}" alt="BukuDigi.com"
                 width="1200" height="279"
                 class="h-auto w-[200px]">
        </a>

        <div class="text-7xl md:text-8xl">{{ $emoji }}</div>
        <p class="mt-6 text-sm font-semibold uppercase tracking-widest text-brand-600">Error {{ $code }}</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900 md:text-3xl">{{ $title }}</h1>
        <p class="mx-auto mt-3 max-w-md text-sm text-slate-600 md:text-base">{!! $message !!}</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ $primaryUrl ?? url('/') }}" class="btn-primary">{{ $primaryLabel ?? '← Kembali ke Beranda' }}</a>
            @if(($showBack ?? false))
                <a href="javascript:history.back()" class="btn-outline">Halaman Sebelumnya</a>
            @endif
        </div>

        <p class="mt-10 text-xs text-slate-400">
            Butuh bantuan? <a href="{{ url('/info/bantuan') }}" class="text-brand-600 hover:underline">Pusat Bantuan</a>
            atau <a href="mailto:support@bukudigi.com" class="text-brand-600 hover:underline">support@bukudigi.com</a>
        </p>
    </div>
</main>

</body>
</html>
