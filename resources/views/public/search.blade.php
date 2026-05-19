@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="text-2xl font-bold">
        @if($q !== '')
            Hasil pencarian: <span class="text-brand-600">"{{ $q }}"</span>
        @else
            Pencarian Buku
        @endif
    </h1>
    <p class="mt-1 text-sm text-slate-500">{{ $books->total() }} buku ditemukan</p>

    @if($books->isEmpty())
        <div class="mt-8 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-2xl">🔍</p>
            <p class="mt-2 font-semibold">Tidak ada buku yang cocok.</p>
            <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau <a href="{{ route('kategori.index') }}" class="text-brand-600 hover:underline">jelajahi per kategori</a>.</p>
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
