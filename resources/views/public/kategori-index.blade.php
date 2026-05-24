@extends('layouts.app')

@php
    $pageTitle = 'Kategori Ebook — bukudigi.com';
    $pageDescription = 'Jelajahi kategori ebook lokal Indonesia: bisnis, edukasi, fiksi, resep, religi, self-help, dan lainnya.';
@endphp

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="text-2xl font-bold">Kategori Ebook</h1>
    <p class="mt-1 text-sm text-slate-500">Jelajahi {{ $categories->count() }} kategori buku digital di bukudigi.</p>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($categories as $cat)
            <a href="{{ route('kategori.show', $cat) }}"
               class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-500 hover:shadow-sm">
                <span class="text-3xl">
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
                        @case('hiburan') 🎬 @break
                        @case('sejarah') 🏛️ @break
                        @case('teknologi-it') 💻 @break
                        @case('seni-desain') 🎨 @break
                        @case('olahraga-fitness') 🏋️ @break
                        @case('travel-wisata') ✈️ @break
                        @case('parenting') 👨‍👩‍👧 @break
                        @case('keuangan-pribadi') 💰 @break
                        @case('marketing-media-sosial') 📣 @break
                        @case('penulisan-jurnalistik') ✍️ @break
                        @case('non-fiksi-sains') 🔬 @break
                        @default 📚
                    @endswitch
                </span>
                <h3 class="font-semibold text-slate-900">{{ $cat->name }}</h3>
                <p class="text-xs text-slate-500">{{ $cat->books_count }} buku</p>
            </a>
        @endforeach
    </div>
</section>
@endsection
