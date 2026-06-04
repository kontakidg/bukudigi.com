@extends('layouts.app')

@php
    $pageTitle = ($category ? $category . ' — ' : '') . 'Blog bukudigi.com';
    $pageDescription = 'Artikel, tips menulis, dan kabar terbaru dari bukudigi.com — marketplace ebook penulis Indonesia.';
    $canonical = route('blog.index');
@endphp

@section('content')
<section class="mx-auto max-w-6xl px-4 py-10">

    <header class="text-center">
        <h1 class="text-3xl font-bold md:text-4xl">📝 Blog bukudigi</h1>
        <p class="mx-auto mt-2 max-w-2xl text-slate-500">
            Tips menulis, panduan ebook, dan kabar terbaru dari komunitas penulis Indonesia.
        </p>
    </header>

    {{-- Filter kategori --}}
    @if($categories->isNotEmpty())
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="{{ route('blog.index') }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium {{ $category === '' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                Semua
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.index', ['kategori' => $cat]) }}"
                   class="rounded-full px-4 py-1.5 text-sm font-medium {{ $category === $cat ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>
    @endif

    @if($articles->isEmpty())
        <div class="mt-10 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-4xl">📭</p>
            <p class="mt-2 font-semibold">Belum ada artikel{{ $category ? ' di kategori ini' : '' }}.</p>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($articles as $article)
                <a href="{{ route('blog.show', $article->slug) }}"
                   class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:shadow-md">
                    <div class="aspect-[16/9] overflow-hidden bg-slate-100">
                        <img src="{{ $article->coverUrl() }}" alt="{{ $article->title }}"
                             class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        @if($article->category)
                            <span class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $article->category }}</span>
                        @endif
                        <h2 class="mt-1 font-bold leading-snug text-slate-900 group-hover:text-brand-700">
                            {{ $article->title }}
                        </h2>
                        @if($article->excerpt)
                            <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ $article->excerpt }}</p>
                        @endif
                        <div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
                            <span>{{ $article->author_name }}</span>
                            <span>·</span>
                            <span>{{ optional($article->published_at)->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">{{ $articles->links() }}</div>
    @endif
</section>
@endsection
