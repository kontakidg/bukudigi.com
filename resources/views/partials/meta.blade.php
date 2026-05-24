@php
    $metaTitle = $title ?? 'bukudigi.com — Marketplace Ebook PDF & EPUB Indonesia';
    $metaDesc  = $description ?? 'Beli ebook PDF & EPUB lokal dari penulis Indonesia. Baca online atau download. Bayar QRIS, watermark personal. Komisi adil untuk penulis.';
    $metaImg   = $image ?? asset('og-default.png');
    $metaType  = $ogType ?? 'website';
    $canonical = $canonical ?? url()->current();
    $robotsContent = ($noindex ?? false) ? 'noindex, nofollow' : 'index, follow';
@endphp

<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDesc }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $robotsContent }}">

{{-- Open Graph (Facebook, WhatsApp, Telegram, LinkedIn) --}}
<meta property="og:type" content="{{ $metaType }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:image" content="{{ $metaImg }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="bukudigi.com">
<meta property="og:locale" content="id_ID">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $metaImg }}">

{{-- JSON-LD structured data (kalau di-pass dari view) --}}
@isset($jsonLd)
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endisset
