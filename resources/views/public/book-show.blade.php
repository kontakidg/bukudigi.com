@extends('layouts.app')

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
            @if($book->author)
                <p class="mt-1 text-sm text-slate-600">oleh <span class="font-semibold text-slate-800">{{ $book->author->display_name }}</span></p>
            @endif

            <div class="mt-4 flex items-center gap-3">
                <span class="text-3xl font-bold text-brand-600">{{ $book->formattedPrice() }}</span>
                @if($book->category)
                    <a href="{{ route('kategori.show', $book->category) }}"
                       class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 hover:bg-brand-100">
                        {{ $book->category->name }}
                    </a>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('checkout.start', $book) }}" class="btn-primary !py-3">🛒 Beli Sekarang</a>
                @else
                    <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.start', $book)) }}" class="btn-primary !py-3">
                        🔒 Masuk untuk Beli
                    </a>
                @endauth

                @if(!empty($book->preview_image_paths))
                    <a href="#preview" class="btn-outline !py-3">👁️ Lihat Preview ({{ count($book->preview_image_paths) }} hal)</a>
                @elseif($book->preview_pdf_path)
                    <a href="{{ asset('storage/'.$book->preview_pdf_path) }}" target="_blank" rel="noopener" class="btn-outline !py-3">👁️ Lihat Preview PDF</a>
                @else
                    <button class="btn-outline !py-3" disabled title="Preview belum tersedia">👁️ Preview Belum Tersedia</button>
                @endif
            </div>

            <div class="mt-6 grid grid-cols-3 gap-3 text-center text-xs">
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-slate-500">Halaman</p>
                    <p class="mt-1 font-semibold">{{ $book->page_count ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-slate-500">Ukuran File</p>
                    <p class="mt-1 font-semibold">{{ $book->file_size_bytes ? round($book->file_size_bytes / 1024 / 1024, 1).' MB' : '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-slate-500">Terjual</p>
                    <p class="mt-1 font-semibold">{{ $book->sales_count }}×</p>
                </div>
            </div>

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
