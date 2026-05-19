@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">Semua Buku</h1>
        <form method="get" class="flex items-center gap-2">
            <label class="text-sm text-slate-600">Urutkan:</label>
            <select name="sort" onchange="this.form.submit()" class="input !py-1.5 !text-sm">
                <option value="newest" @selected($sort === 'newest')>Terbaru</option>
                <option value="popular" @selected($sort === 'popular')>Paling Laris</option>
                <option value="price_low" @selected($sort === 'price_low')>Harga Terendah</option>
                <option value="price_high" @selected($sort === 'price_high')>Harga Tertinggi</option>
            </select>
        </form>
    </div>

    @if($books->isEmpty())
        <div class="mt-8 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-2xl">📚</p>
            <p class="mt-2 font-semibold">Belum ada buku live.</p>
            <p class="mt-1 text-sm text-slate-500">Buku akan muncul di sini setelah penulis upload &amp; admin approve.</p>
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
