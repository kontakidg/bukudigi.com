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
        <div class="mt-3 flex items-center gap-2 text-sm text-slate-400">
            <span>{{ $article->author_name }}</span>
            <span>·</span>
            <span>{{ optional($article->published_at)->translatedFormat('d F Y') }}</span>
            <span>·</span>
            <span>{{ number_format($article->views_count) }}× dibaca</span>
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
@endsection
