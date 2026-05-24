@php
    $metaTitle = $title ?? 'bukudigi.com — Marketplace Ebook PDF & EPUB Indonesia';
    $metaDesc  = $description ?? 'Beli ebook PDF & EPUB lokal dari penulis Indonesia. Baca online atau download. Bayar QRIS, watermark personal. Komisi adil untuk penulis.';
    $metaImg   = $image ?? asset('og-default.png');
    $metaType  = $ogType ?? 'website';
    $canonical = $canonical ?? url()->current();
    $robotsContent = ($noindex ?? false) ? 'noindex, nofollow' : 'index, follow';
    // Image dimensions — set 1200x630 untuk OG cards (default), false untuk image cover langsung yg portrait
    $imgWidth = $imageWidth ?? 1200;
    $imgHeight = $imageHeight ?? 630;
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
<meta property="og:image:secure_url" content="{{ $metaImg }}">
<meta property="og:image:type" content="image/png">
@if($imgWidth && $imgHeight)
    <meta property="og:image:width" content="{{ $imgWidth }}">
    <meta property="og:image:height" content="{{ $imgHeight }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="bukudigi.com">
<meta property="og:locale" content="id_ID">
@if(config('services.facebook.app_id'))
    <meta property="fb:app_id" content="{{ config('services.facebook.app_id') }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $metaImg }}">

{{-- JSON-LD structured data (kalau di-pass dari view) --}}
@isset($jsonLd)
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endisset
