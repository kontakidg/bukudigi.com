@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">📚 Perpustakaan Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Buku yang sudah kamu beli. Re-download kapan saja (maks 5x dalam 30 hari per buku).</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('books.index') }}" class="btn-outline !py-2 !text-sm">🛍️ Beli Buku Lagi</a>
            <a href="{{ route('profile.edit') }}" class="btn-ghost !py-2 !text-sm">⚙ Profil</a>
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="mt-8 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-4xl">📖</p>
            <p class="mt-3 font-semibold text-lg">Belum ada buku di perpustakaan kamu.</p>
            <p class="mt-1 text-sm text-slate-500">Mulai jelajahi katalog ebook lokal.</p>
            <a href="{{ route('books.index') }}" class="btn-primary mt-6">Jelajahi Buku →</a>
        </div>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($orders as $order)
                <div class="card overflow-hidden">
                    <div class="flex gap-3 p-3">
                        <div class="h-28 w-20 flex-shrink-0 overflow-hidden rounded bg-slate-100">
                            @if($order->book->cover_path)
                                <img src="{{ \Illuminate\Support\Str::startsWith($order->book->cover_path, ['http://', 'https://']) ? $order->book->cover_path : asset('storage/'.$order->book->cover_path) }}"
                                     alt="{{ $order->book->title }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-100 to-brand-300 text-brand-700">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col">
                            <h3 class="line-clamp-2 text-sm font-semibold text-slate-900">{{ $order->book->title }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $order->book->displayAuthor() }}</p>
                            <div class="mt-auto pt-2">
                                @if($order->status === 'ready' && $order->canDownload())
                                    <a href="{{ route('download.show', $order->order_code) }}" class="btn-primary w-full !py-2 !text-xs">⬇ Download PDF</a>
                                @elseif($order->status === 'watermarking')
                                    <span class="block rounded-lg bg-amber-50 px-3 py-2 text-center text-xs font-medium text-amber-700">⏳ Watermarking…</span>
                                @elseif($order->status === 'paid')
                                    <span class="block rounded-lg bg-slate-50 px-3 py-2 text-center text-xs font-medium text-slate-600">📦 Memproses</span>
                                @else
                                    <span class="block rounded-lg bg-slate-50 px-3 py-2 text-center text-xs font-medium text-slate-500">Link expired</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50 px-3 py-2 text-[10px] text-slate-500">
                        Order #{{ $order->order_code }} · {{ $order->paid_at?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-8">{{ $orders->links() }}</div>
    @endif
</section>
@endsection
