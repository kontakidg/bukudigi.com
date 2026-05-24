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
