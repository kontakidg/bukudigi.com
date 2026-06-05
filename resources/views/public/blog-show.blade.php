@extends('layouts.app')

@php
    $pageTitle = ($article->meta_title ?: $article->title) . ' | Blog bukudigi.com';
    $pageDescription = \Illuminate\Support\Str::limit(strip_tags($article->meta_description ?: $article->excerpt ?: $article->content), 155);
    $pageImage = $article->coverUrl();
    $ogType = 'article';
    $canonical = route('blog.show', $article->slug);

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $article->title,
        'image' => $article->coverUrl(),
        'datePublished' => optional($article->published_at)->toIso8601String(),
        'dateModified' => $article->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => $article->author_name],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'bukudigi.com',
            'logo' => ['@type' => 'ImageObject', 'url' => asset('favicon.png')],
        ],
        'mainEntityOfPage' => $canonical,
        'description' => $pageDescription,
    ];
@endphp

@section('content')
<article class="mx-auto max-w-3xl px-4 py-10">

    <nav class="mb-6 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
        <span class="mx-1">/</span>
        <a href="{{ route('blog.index') }}" class="hover:text-brand-600">Blog</a>
        @if($article->category)
            <span class="mx-1">/</span>
            <a href="{{ route('blog.index', ['kategori' => $article->category]) }}" class="hover:text-brand-600">{{ $article->category }}</a>
        @endif
    </nav>

    <header>
        @if($article->category)
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $article->category }}</span>
        @endif
        <h1 class="mt-2 text-3xl font-bold leading-tight md:text-4xl">{{ $article->title }}</h1>
        <div class="mt-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-400">
                <span>{{ $article->author_name }}</span>
                <span>·</span>
                <span>{{ optional($article->published_at)->translatedFormat('d F Y') }}</span>
                <span>·</span>
                <span>{{ number_format($article->views_count) }}× dibaca</span>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="shareWhatsApp()" title="Bagikan ke WhatsApp" class="text-slate-400 hover:text-brand-600 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-4.255.949c-1.238.503-2.37 1.237-3.322 2.192A9.9 9.9 0 002.511 12a9.882 9.882 0 001.515 5.684c.505 1.238 1.236 2.371 2.19 3.322a9.88 9.88 0 005.684 1.516c1.564 0 3.062-.29 4.52-.844 1.465-.554 2.783-1.404 3.844-2.465a9.9 9.9 0 002.465-3.844c.554-1.458.844-2.956.844-4.52 0-1.564-.29-3.062-.844-4.52-.554-1.465-1.404-2.783-2.465-3.844a9.88 9.88 0 00-3.844-2.465C15.062 2.511 13.564 2.221 12 2.221s-3.062.29-4.52.844c-1.465.554-2.783 1.404-3.844 2.465A9.9 9.9 0 002.221 12c0 1.564.29 3.062.844 4.52"/></svg>
                </button>
                <button onclick="shareFacebook()" title="Bagikan ke Facebook" class="text-slate-400 hover:text-brand-600 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </button>
                <button onclick="shareTwitter()" title="Bagikan ke X (Twitter)" class="text-slate-400 hover:text-brand-600 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.6l-5.165-6.75-5.913 6.75h-3.308l7.73-8.835L2.428 2.25h6.742l4.902 6.48L18.244 2.25zM17.25 20.428h1.828L5.369 3.987H3.456l13.794 16.441z"/></svg>
                </button>
                <button onclick="copyLink()" title="Salin link" class="text-slate-400 hover:text-brand-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </button>
            </div>
        </div>
    </header>

    @if($article->cover_path)
        <div class="mt-6 overflow-hidden rounded-xl bg-slate-100">
            <img src="{{ $article->coverUrl() }}" alt="{{ $article->title }}" class="w-full object-cover">
        </div>
    @endif

    <div class="prose prose-slate mt-8 max-w-none prose-headings:font-bold prose-a:text-brand-600 prose-img:rounded-lg">
        {!! $article->content !!}
    </div>

    {{-- Buku relevan --}}
    @if($relevantBooks->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-lg font-bold">Buku Terkait</h2>
            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                @foreach($relevantBooks as $book)
                    <a href="{{ route('books.show', $book->slug) }}" class="group">
                        <div class="aspect-[3/4] overflow-hidden rounded-lg bg-slate-100">
                            <img src="{{ $book->cover_thumb_path ? asset('storage/' . $book->cover_thumb_path) : asset('storage/' . $book->cover_path) }}" alt="{{ $book->title }}" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                        </div>
                        <h3 class="mt-2 text-xs font-semibold leading-snug text-slate-800 group-hover:text-brand-700 line-clamp-2">{{ $book->title }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $book->displayAuthor() }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm font-bold text-brand-700">{{ $book->formattedPrice() }}</span>
                            @if($book->rating_avg > 0)
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span class="text-xs font-semibold text-slate-700">{{ number_format($book->rating_avg, 1) }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA ke katalog --}}
    <div class="mt-10 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-center text-white">
        <h3 class="text-lg font-bold">Cari ebook berkualitas dari penulis Indonesia?</h3>
        <p class="mt-1 text-sm text-brand-100">Ribuan judul, watermark personal, baca online atau download.</p>
        <a href="{{ route('books.index') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">
            Jelajahi Katalog →
        </a>
    </div>

    {{-- Artikel terkait --}}
    @if($related->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-lg font-bold">Artikel Lainnya</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-3">
                @foreach($related as $r)
                    <a href="{{ route('blog.show', $r->slug) }}" class="group">
                        <div class="aspect-[16/9] overflow-hidden rounded-lg bg-slate-100">
                            <img src="{{ $r->coverUrl() }}" alt="{{ $r->title }}" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                        </div>
                        <h3 class="mt-2 text-sm font-semibold leading-snug text-slate-800 group-hover:text-brand-700">{{ $r->title }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</article>

<script>
    const articleUrl = "{{ route('blog.show', $article->slug) }}";
    const articleTitle = "{{ $article->title }}";

    function shareWhatsApp() {
        const text = `Baca: ${articleTitle}\n\n${articleUrl}`;
        const encoded = encodeURIComponent(text);
        window.open(`https://wa.me/?text=${encoded}`, '_blank');
    }

    function shareFacebook() {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(articleUrl)}`, '_blank');
    }

    function shareTwitter() {
        const text = `Baca: ${articleTitle}`;
        window.open(`https://x.com/intent/tweet?url=${encodeURIComponent(articleUrl)}&text=${encodeURIComponent(text)}`, '_blank');
    }

    function copyLink() {
        navigator.clipboard.writeText(articleUrl).then(() => {
            const button = event.target.closest('button');
            const icon = button.innerHTML;
            button.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
            button.classList.add('text-green-500');
            setTimeout(() => {
                button.innerHTML = icon;
                button.classList.remove('text-green-500');
            }, 2000);
        });
    }
</script>
@endsection
