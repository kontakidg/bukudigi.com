@extends('layouts.app')

@php
    $pageTitle = 'bukudigi.com — Marketplace Ebook PDF & EPUB dari Penulis Indonesia';
    $pageDescription = 'Beli ' . number_format($stats['total_books']) . '+ ebook PDF & EPUB lokal Indonesia. Baca online di reader bukudigi atau download. Bayar QRIS, watermark personal. Komisi adil untuk penulis. Mulai dari Rp 15.000.';
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'bukudigi.com',
        'url' => route('home'),
        'description' => 'Marketplace ebook PDF & EPUB dari penulis Indonesia',
        'inLanguage' => 'id',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('cari') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
@endphp

@section('content')
<section class="relative overflow-hidden py-16 text-white md:py-24">
    {{-- Background image --}}
    <img src="{{ asset('bg.jpg') }}" alt=""
         loading="eager" fetchpriority="high"
         class="absolute inset-0 h-full w-full object-cover"
         aria-hidden="true">
    {{-- Dark overlay supaya teks tetap terbaca (gradient indigo dim) --}}
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/75 via-brand-900/65 to-brand-700/55" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 text-center">
        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white backdrop-blur">
            📚 {{ number_format($stats['total_books']) }} ebook · {{ number_format($stats['total_authors']) }} penulis · {{ number_format($stats['total_orders']) }} pembaca
        </span>
        <h1 class="mt-4 mb-3 text-3xl font-bold leading-tight drop-shadow-md md:text-5xl">
            {{ \App\Models\Setting::get('hero_tagline_1', 'Ebook PDF dari Penulis Indonesia') }}<br class="hidden md:inline">
            <span class="text-brand-200">{{ \App\Models\Setting::get('hero_tagline_2', 'Bayar QRIS, Baca Sekarang') }}</span>
        </h1>
        <p class="mx-auto mb-8 max-w-2xl text-base text-brand-100 drop-shadow md:text-lg">
            {{ \App\Models\Setting::get('hero_subtagline', 'Marketplace ebook untuk komunitas pembaca & penulis lokal. Watermark personal di setiap halaman, dukungan langsung ke penulis.') }}
        </p>

        <form action="{{ route('cari') }}" method="get" class="mx-auto max-w-2xl">
            <div class="flex flex-col gap-2 rounded-xl bg-white p-2 shadow-2xl sm:flex-row">
                <input name="q" type="search" placeholder="Cari judul, penulis, atau topik…"
                       class="flex-1 rounded-lg px-3 py-3 text-slate-900 focus:outline-none">
                <button type="submit" class="btn-primary !py-3">Cari Ebook</button>
            </div>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-brand-100/90 drop-shadow md:text-sm">
            <span class="inline-flex items-center gap-1.5">📄 <strong class="text-white">PDF</strong> watermarked</span>
            <span class="text-brand-300">·</span>
            <span class="inline-flex items-center gap-1.5">📖 <strong class="text-white">EPUB</strong> baca online & download</span>
            <span class="text-brand-300">·</span>
            <span class="inline-flex items-center gap-1.5">⚡ Instan setelah bayar</span>
        </div>
    </div>
</section>

{{-- 1. Kategori --}}
<section class="mx-auto max-w-7xl px-4 py-10">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold">Jelajahi per Kategori</h2>
        <a href="{{ route('kategori.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua →</a>
    </div>
    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-9">
        @foreach($categories as $cat)
            <a href="{{ route('kategori.show', $cat) }}"
               class="flex flex-col items-center gap-2 rounded-lg border border-slate-200 bg-white p-4 text-center transition hover:border-brand-500 hover:shadow-sm">
                <span class="text-2xl">
                    @switch($cat->slug)
                        @case('bisnis-investasi') 💼 @break
                        @case('edukasi-akademik') 🎓 @break
                        @case('tutorial-skill') ✨ @break
                        @case('fiksi-sastra') 📖 @break
                        @case('resep-kuliner') 🍳 @break
                        @case('kesehatan') 💚 @break
                        @case('religi') 🕌 @break
                        @case('self-help') 💡 @break
                        @case('anak') 🧸 @break
                        @default 📚
                    @endswitch
                </span>
                <span class="text-xs font-medium leading-tight">{{ $cat->name }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- 2. Buku Terbaru --}}
<section class="mx-auto max-w-7xl px-4 py-6">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold">📖 Buku Terbaru</h2>
        <a href="{{ route('books.index', ['sort' => 'newest']) }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua →</a>
    </div>
    @if($newestBooks->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-2xl">📚</p>
            <p class="mt-2 font-semibold">Belum ada buku live.</p>
            <p class="mt-1 text-sm text-slate-500">Tunggu penulis pertama upload buku, atau <a href="{{ route('jual') }}" class="text-brand-600 hover:underline">jadilah penulis pertama!</a></p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($newestBooks as $book)
                <x-book-card :book="$book" />
            @endforeach
        </div>
    @endif
</section>

{{-- 3. Buku Populer --}}
@if($popularBooks->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 py-6">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold">🔥 Paling Laris</h2>
        <a href="{{ route('books.index', ['sort' => 'popular']) }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua →</a>
    </div>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach($popularBooks as $book)
            <x-book-card :book="$book" />
        @endforeach
    </div>
</section>
@endif

{{-- 4. CTA Penulis --}}
<section class="mx-auto max-w-7xl px-4 py-12">
    <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-8 text-white shadow-xl md:p-12">
        <div class="grid items-center gap-6 md:grid-cols-3">
            <div class="md:col-span-2">
                <h2 class="text-2xl font-bold md:text-3xl">Punya ebook? Jual di bukudigi.</h2>
                <p class="mt-2 text-brand-100">Komisi platform cuma <strong class="text-white">20%</strong>, sisanya untuk kamu. Royalti masuk rekening tanggal 5 tiap bulan. Tanpa kontrak penerbit.</p>
            </div>
            <div class="md:text-right">
                <a href="{{ route('jual') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-brand-700 shadow hover:bg-brand-50">
                    ✍️ Mulai Jualan
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
