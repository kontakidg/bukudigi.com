<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.meta', [
        'title' => $pageTitle ?? null,
        'description' => $pageDescription ?? null,
        'image' => $pageImage ?? null,
        'ogType' => $ogType ?? null,
        'canonical' => $canonical ?? null,
        'noindex' => $noindex ?? null,
        'jsonLd' => $jsonLd ?? null,
    ])

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased">

@include('partials.header')

<main class="flex-1">
    @if(isset($header))
        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-5">{{ $header }}</div>
        </div>
    @endif

    @isset($slot){{ $slot }}@endisset
    @hasSection('content')@yield('content')@endif
</main>

@include('partials.footer')

@stack('scripts')
</body>
</html>
