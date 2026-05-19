@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <nav class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
        <span class="mx-1">/</span>
        <a href="{{ route('kategori.index') }}" class="hover:text-brand-600">Kategori</a>
        <span class="mx-1">/</span>
        <span class="text-slate-700">{{ $category->name }}</span>
    </nav>

    <h1 class="text-2xl font-bold">{{ $category->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $category->description }}</p>
    <p class="mt-1 text-xs text-slate-500">{{ $books->total() }} buku</p>

    @if($books->isEmpty())
        <div class="mt-8 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-2xl">📚</p>
            <p class="mt-2 font-semibold">Belum ada buku di kategori ini.</p>
        </div>
    @else
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($books as $book)
                <x-book-card :book="$book" />
            @endforeach
        </div>
        <div class="mt-8">{{ $books->links() }}</div>
    @endif
</section>
@endsection
