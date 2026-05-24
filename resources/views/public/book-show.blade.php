@extends('layouts.app')

@php
    $bookCoverUrl = $book->cover_path && \Illuminate\Support\Str::startsWith($book->cover_path, ['http://', 'https://'])
        ? $book->cover_path
        : ($book->cover_path ? asset('storage/'.$book->cover_path) : asset('og-default.png'));

    // OG card khusus (1200x630 yang di-generate dengan brand) → fallback ke cover biasa
    $ogImageUrl = $book->og_card_path
        ? asset('storage/'.$book->og_card_path)
        : $bookCoverUrl;

    $pageTitle = $book->title . ' — ' . $book->displayAuthor() . ' | bukudigi.com';
    $pageDescription = \Illuminate\Support\Str::limit(strip_tags($book->description), 155);
    $pageImage = $ogImageUrl;
    $pageImageAlt = 'Cover ebook ' . $book->title;
    $ogType = 'book';
    $hasFormattedOgCard = (bool) $book->og_card_path; // 1200x630 → set dimensions explicit

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Book',
        'name' => $book->title,
        'description' => \Illuminate\Support\Str::limit(strip_tags($book->description), 500),
        'image' => $bookCoverUrl,
        'url' => route('books.show', $book->slug),
        'bookFormat' => $book->epub_master_path ? 'https://schema.org/EBook' : 'https://schema.org/EBook',
        'inLanguage' => 'id',
        'numberOfPages' => $book->page_count,
        'author' => [
            '@type' => 'Person',
            'name' => $book->displayAuthor(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'bukudigi.com',
            'url' => route('home'),
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => (string) $book->price,
            'priceCurrency' => 'IDR',
            'availability' => 'https://schema.org/InStock',
            'url' => route('books.show', $book->slug),
            'seller' => [
                '@type' => 'Organization',
                'name' => 'bukudigi.com',
            ],
        ],
    ];
    if ($book->category) {
        $jsonLd['genre'] = $book->category->name;
    }

    // Breadcrumb schema (separate JSON-LD)
    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
            $book->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $book->category->name, 'item' => route('kategori.show', $book->category)] : null,
            ['@type' => 'ListItem', 'position' => $book->category ? 3 : 2, 'name' => $book->title, 'item' => route('books.show', $book->slug)],
        ])),
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    {{-- og:image:alt + book-specific OG properties --}}
    <meta property="og:image:alt" content="{{ $pageImageAlt }}">
    @if($book->category)
        <meta property="book:tag" content="{{ $book->category->name }}">
    @endif
    <meta property="book:author" content="{{ $book->displayAuthor() }}">
    @if($book->approved_at)
        <meta property="book:release_date" content="{{ $book->approved_at->toDateString() }}">
    @endif
@endpush

@section('content')
<section class="mx-auto max-w-6xl px-4 py-8">
    <nav class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
        <span class="mx-1">/</span>
        @if($book->category)
            <a href="{{ route('kategori.show', $book->category) }}" class="hover:text-brand-600">{{ $book->category->name }}</a>
            <span class="mx-1">/</span>
        @endif
        <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($book->title, 50) }}</span>
    </nav>

    <div class="grid gap-8 md:grid-cols-3">
        <div class="md:col-span-1">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if($book->cover_path)
                    <img src="{{ \Illuminate\Support\Str::startsWith($book->cover_path, ['http://', 'https://']) ? $book->cover_path : asset('storage/'.$book->cover_path) }}"
                         alt="{{ $book->title }}"
                         class="aspect-[3/4] w-full object-cover">
                @else
                    <div class="flex aspect-[3/4] w-full items-center justify-center bg-gradient-to-br from-brand-100 to-brand-300 text-brand-700">
                        <svg class="h-24 w-24" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                @endif
            </div>
        </div>

        <div class="md:col-span-2">
            <h1 class="text-3xl font-bold text-slate-900">{{ $book->title }}</h1>
            <p class="mt-1 text-sm text-slate-600">oleh <span class="font-semibold text-slate-800">{{ $book->displayAuthor() }}</span></p>

            <div class="mt-4 flex items-center gap-3">
                <span class="text-3xl font-bold text-brand-600">{{ $book->formattedPrice() }}</span>
                @if($book->category)
                    <a href="{{ route('kategori.show', $book->category) }}"
                       class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 hover:bg-brand-100">
                        {{ $book->category->name }}
                    </a>
                @endif
            </div>

            @auth
            <div x-data="{
                open: false,
                code: '',
                loading: false,
                result: null,
                async apply() {
                    if (!this.code.trim()) return;
                    this.loading = true;
                    this.result = null;
                    try {
                        const resp = await fetch('{{ route('checkout.voucher.preview', $book) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ code: this.code.trim() }),
                        });
                        this.result = await resp.json();
                    } catch (e) {
                        this.result = { valid: false, reason: 'Gagal cek voucher. Cek koneksi.' };
                    } finally {
                        this.loading = false;
                    }
                },
                reset() { this.code = ''; this.result = null; },
                get buyUrl() {
                    const base = '{{ route('checkout.start', $book) }}';
                    if (this.result && this.result.valid) {
                        return base + '?voucher_code=' + encodeURIComponent(this.code.trim());
                    }
                    return base;
                }
            }">
                {{-- Voucher input (collapsible) --}}
                <div class="mt-5 rounded-lg border border-slate-200 bg-white p-3"
                     :class="result && result.valid ? '!border-green-300 !bg-green-50/50' : ''">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between text-left text-sm font-semibold text-slate-700 hover:text-brand-600">
                        <span>
                            <span x-show="!(result && result.valid)">🎟️ Punya kode voucher?</span>
                            <span x-show="result && result.valid" class="text-green-700">✓ Voucher aktif: <span x-text="result.code"></span></span>
                        </span>
                        <span x-text="open ? '−' : '+'" class="text-lg leading-none"></span>
                    </button>
                    <div x-show="open" x-cloak x-transition class="mt-3 space-y-2">
                        {{-- Form input — disable kalau voucher sudah valid --}}
                        <div class="flex gap-2" x-show="!(result && result.valid)">
                            <input type="text" x-model="code" @keyup.enter="apply()" :disabled="loading"
                                   placeholder="Masukkan kode voucher"
                                   class="input !py-2 flex-1 uppercase">
                            <button type="button" @click="apply()" :disabled="loading || !code.trim()"
                                    class="btn-primary !py-2 !text-sm whitespace-nowrap"
                                    x-text="loading ? '...' : 'Pakai Voucher'"></button>
                        </div>
                        {{-- Success state --}}
                        <template x-if="result && result.valid">
                            <div class="rounded-lg border border-green-300 bg-green-50 p-3 text-xs text-green-800">
                                <p class="font-semibold text-sm">🎉 Voucher diterapkan!</p>
                                <p class="mt-1 text-green-700" x-text="result.name"></p>
                                <div class="mt-3 space-y-0.5 border-t border-green-200 pt-2">
                                    <p>Potongan: <strong x-text="result.discount_display"></strong></p>
                                    <p>Total bayar: <strong class="text-base" x-text="result.net_display"></strong></p>
                                </div>
                                <button type="button" @click="reset()"
                                        class="mt-3 text-[11px] font-semibold text-red-600 hover:underline">
                                    Hapus voucher
                                </button>
                            </div>
                        </template>
                        {{-- Error state --}}
                        <template x-if="result && !result.valid">
                            <div class="rounded-lg border border-red-200 bg-red-50 p-2 text-xs text-red-700" x-text="result.reason"></div>
                        </template>
                    </div>
                </div>

                {{-- Tombol Beli + Preview — dynamic label kalau voucher applied --}}
                <div class="mt-4 flex flex-wrap gap-3">
                    <a :href="buyUrl" href="{{ route('checkout.start', $book) }}" class="btn-primary !py-3">
                        <span x-show="!(result && result.valid)">🛒 Beli Sekarang</span>
                        <span x-show="result && result.valid">🛒 Beli dengan Voucher</span>
                    </a>
                    @if(!empty($book->preview_image_paths))
                        <a href="#preview" class="btn-outline !py-3">👁️ Lihat Preview ({{ count($book->preview_image_paths) }} hal)</a>
                    @elseif($book->preview_pdf_path)
                        <a href="{{ asset('storage/'.$book->preview_pdf_path) }}" target="_blank" rel="noopener" class="btn-outline !py-3">👁️ Lihat Preview PDF</a>
                    @else
                        <button class="btn-outline !py-3" disabled title="Preview belum tersedia">👁️ Preview Belum Tersedia</button>
                    @endif
                </div>
            </div>
            @else
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.start', $book)) }}" class="btn-primary !py-3">
                    🔒 Masuk untuk Beli
                </a>
                @if(!empty($book->preview_image_paths))
                    <a href="#preview" class="btn-outline !py-3">👁️ Lihat Preview ({{ count($book->preview_image_paths) }} hal)</a>
                @elseif($book->preview_pdf_path)
                    <a href="{{ asset('storage/'.$book->preview_pdf_path) }}" target="_blank" rel="noopener" class="btn-outline !py-3">👁️ Lihat Preview PDF</a>
                @else
                    <button class="btn-outline !py-3" disabled title="Preview belum tersedia">👁️ Preview Belum Tersedia</button>
                @endif
            </div>
            @endauth

            <div class="mt-6 grid grid-cols-3 gap-3 text-center text-xs">
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-slate-500">Halaman</p>
                    <p class="mt-1 font-semibold">{{ $book->page_count ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-slate-500">Format</p>
                    <p class="mt-1 font-semibold">
                        <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] text-rose-700">PDF</span>
                        @if($book->epub_master_path)
                            <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] text-indigo-700">EPUB</span>
                        @endif
                    </p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-slate-500">Terjual</p>
                    <p class="mt-1 font-semibold">{{ $book->sales_count }}×</p>
                </div>
            </div>

            @if($book->epub_master_path)
                <div class="mt-3 flex items-start gap-2 rounded-lg border border-indigo-200 bg-indigo-50/60 p-3 text-xs text-indigo-900">
                    <span class="text-base">📖</span>
                    <p>
                        <strong>Tersedia juga dalam EPUB</strong> — selain PDF, kamu bisa baca buku ini online langsung di reader bukudigi.com atau download .epub untuk dibaca di aplikasi favorit (Apple Books, Calibre, Kindle, dll). Cocok buat HP/tablet — teks otomatis reflow sesuai layar.
                    </p>
                </div>
            @endif

            <div class="mt-8">
                <h2 class="text-lg font-bold">Tentang Buku Ini</h2>
                <div class="prose prose-sm mt-2 max-w-none text-slate-700">
                    {!! nl2br(e($book->description)) !!}
                </div>
            </div>

            @if($book->table_of_contents)
                <div class="mt-6">
                    <h2 class="text-lg font-bold">Daftar Isi</h2>
                    <div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $book->table_of_contents }}</div>
                </div>
            @endif

            @if($book->tags->isNotEmpty())
                <div class="mt-6">
                    <h2 class="text-sm font-semibold text-slate-500">Tag</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($book->tags as $tag)
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-700">#{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Share buttons --}}
            @php
                $shareUrl = route('books.show', $book->slug);
                $shareTitle = $book->title.' — '.$book->displayAuthor();
                $shareText = 'Cek ebook "'.$book->title.'" oleh '.$book->displayAuthor().' di bukudigi.com';
                $waText = urlencode($shareText.' '.$shareUrl);
                $tgText = urlencode($shareText);
                $twText = urlencode($shareText);
                $emailSubject = urlencode('Lihat ebook: '.$book->title);
                $emailBody = urlencode($shareText."\n\n".$shareUrl);
                $encodedUrl = urlencode($shareUrl);
            @endphp
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4"
                 x-data="{ copied: false, copyLink() {
                    navigator.clipboard.writeText('{{ $shareUrl }}').then(() => {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    });
                 }}">
                <h2 class="text-sm font-semibold text-slate-700">📤 Bagikan buku ini</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    {{-- WhatsApp --}}
                    <a href="https://wa.me/?text={{ $waText }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-green-500 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-green-600"
                       title="Bagikan ke WhatsApp">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>

                    {{-- Facebook --}}
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-[#1877F2] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
                       title="Bagikan ke Facebook">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </a>

                    {{-- Twitter / X --}}
                    <a href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}&text={{ $twText }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-black px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-80"
                       title="Bagikan ke X (Twitter)">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        X
                    </a>

                    {{-- Telegram --}}
                    <a href="https://t.me/share/url?url={{ $encodedUrl }}&text={{ $tgText }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-[#26A5E4] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
                       title="Bagikan ke Telegram">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                        Telegram
                    </a>

                    {{-- LinkedIn --}}
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encodedUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-[#0A66C2] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
                       title="Bagikan ke LinkedIn">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        LinkedIn
                    </a>

                    {{-- Email --}}
                    <a href="mailto:?subject={{ $emailSubject }}&body={{ $emailBody }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-brand-400 hover:text-brand-600"
                       title="Kirim via email">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Email
                    </a>

                    {{-- Copy Link --}}
                    <button type="button" @click="copyLink()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-brand-400 hover:text-brand-600"
                            title="Salin link">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" x-show="!copied">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" x-show="copied" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="copied ? 'Tersalin!' : 'Salin Link'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($book->preview_image_paths))
        <section id="preview" class="mt-12 scroll-mt-20" x-data="{ lightbox: null }">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <h2 class="text-xl font-bold">👁️ Preview Buku</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ count($book->preview_image_paths) }} halaman pertama · gratis. Klik gambar untuk zoom.</p>
                </div>
                @if($book->preview_pdf_path)
                    <a href="{{ asset('storage/'.$book->preview_pdf_path) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-600 hover:underline">
                        Buka sebagai PDF →
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                @foreach($book->preview_image_paths as $i => $imgRel)
                    <button type="button" @click="lightbox = {{ $i }}"
                            class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-brand-500 hover:shadow-md">
                        <img src="{{ asset('storage/'.$imgRel) }}"
                             alt="Halaman {{ $i + 1 }}"
                             loading="lazy"
                             class="aspect-[3/4] w-full object-cover">
                        <span class="absolute left-1 top-1 rounded bg-white/90 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 shadow">
                            {{ $i + 1 }}
                        </span>
                    </button>
                @endforeach
            </div>

            {{-- Lightbox --}}
            <div x-show="lightbox !== null" x-cloak
                 @keydown.escape.window="lightbox = null"
                 @keydown.arrow-left.window="if (lightbox > 0) lightbox--"
                 @keydown.arrow-right.window="if (lightbox < {{ count($book->preview_image_paths) - 1 }}) lightbox++"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/90 p-4"
                 x-transition.opacity>
                <button @click="lightbox = null"
                        class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Tutup">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <button @click="if (lightbox > 0) lightbox--"
                        :class="lightbox === 0 && 'opacity-30 cursor-not-allowed'"
                        class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Sebelumnya">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="if (lightbox < {{ count($book->preview_image_paths) - 1 }}) lightbox++"
                        :class="lightbox === {{ count($book->preview_image_paths) - 1 }} && 'opacity-30 cursor-not-allowed'"
                        class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Selanjutnya">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                @foreach($book->preview_image_paths as $i => $imgRel)
                    <img x-show="lightbox === {{ $i }}" x-transition
                         src="{{ asset('storage/'.$imgRel) }}"
                         alt="Halaman {{ $i + 1 }}"
                         class="max-h-[90vh] max-w-full rounded shadow-2xl">
                @endforeach

                <p class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-3 py-1 text-xs text-white">
                    Halaman <span x-text="lightbox + 1"></span> dari {{ count($book->preview_image_paths) }}
                </p>
            </div>

            <div class="mt-6 rounded-xl bg-gradient-to-br from-brand-50 to-white p-6 text-center">
                <p class="text-sm text-slate-700">Suka previewnya? Beli untuk akses {{ $book->page_count ?? 'semua' }} halaman tanpa watermark DEMO.</p>
                @auth
                    <a href="{{ route('checkout.start', $book) }}" class="btn-primary mt-3">🛒 Beli {{ $book->formattedPrice() }}</a>
                @else
                    <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.start', $book)) }}" class="btn-primary mt-3">🔒 Masuk untuk Beli</a>
                @endauth
            </div>
        </section>
    @endif

    @if($related->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-4 text-xl font-bold">Buku Serupa</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($related as $r)
                    <x-book-card :book="$r" />
                @endforeach
            </div>
        </section>
    @endif
</section>
@endsection
