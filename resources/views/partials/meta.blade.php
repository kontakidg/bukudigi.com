@php
    $metaTitle = $title ?? 'bukudigi.com — Marketplace Ebook PDF Indonesia';
    $metaDesc  = $description ?? 'Beli ebook PDF lokal dari penulis Indonesia. Bayar QRIS, instan download, watermark personal. Komisi adil untuk penulis.';
    $metaImg   = $image ?? asset('images/og-default.png');
@endphp

<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDesc }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:image" content="{{ $metaImg }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="bukudigi.com">
<meta property="og:locale" content="id_ID">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $metaImg }}">
