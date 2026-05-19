<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">📊 Dashboard Penulis</h1>
                <p class="mt-1 text-sm text-slate-500">Halo, {{ $author->display_name }}!</p>
            </div>
            <div class="flex items-center gap-2">
                @if($author->status === 'pending')
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">⏳ Menunggu Verifikasi</span>
                @elseif($author->status === 'verified')
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">✓ Terverifikasi</span>
                @else
                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">⛔ Suspended</span>
                @endif
                @if($author->status === 'verified')
                    <a href="{{ route('author.books.index') }}" class="btn-outline !py-1.5 !text-sm">📕 Kelola Buku</a>
                    <a href="{{ route('author.books.create') }}" class="btn-primary !py-1.5 !text-sm">+ Upload Buku</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        @if($author->status === 'pending')
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-semibold">⏳ Pendaftaran kamu sedang di-review</p>
                <p class="mt-1">Admin akan verifikasi data KTP dan rekening kamu dalam 1-2 hari kerja. Kamu akan dapat notifikasi WhatsApp begitu disetujui.</p>
            </div>
        @endif

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Saldo Tersedia</p>
                <p class="mt-1 text-xl font-bold text-brand-600">Rp {{ number_format($author->balance_available, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Akan ditransfer tgl 5</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Saldo Menahan</p>
                <p class="mt-1 text-xl font-bold text-slate-700">Rp {{ number_format($author->balance_pending, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Cooling 7 hari</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Total Pendapatan</p>
                <p class="mt-1 text-xl font-bold text-slate-700">Rp {{ number_format($author->total_earned, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Sejak bergabung</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Total Penjualan</p>
                <p class="mt-1 text-xl font-bold text-slate-700">{{ number_format($stats['total_sales']) }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Buku terjual</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <div class="card p-5">
                <p class="text-xs text-slate-500">Buku Live</p>
                <p class="text-2xl font-bold">{{ $stats['active_books'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-slate-500">Menunggu Moderasi</p>
                <p class="text-2xl font-bold">{{ $stats['pending_books'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-slate-500">Total Buku</p>
                <p class="text-2xl font-bold">{{ $stats['total_books'] }}</p>
            </div>
        </div>

        {{-- Books list --}}
        <div class="mt-8">
            <h2 class="text-lg font-bold">Buku Saya</h2>
            @if($books->isEmpty())
                <div class="mt-3 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-4xl">📕</p>
                    <p class="mt-2 font-semibold">Belum ada buku.</p>
                    <p class="mt-1 text-sm text-slate-500">@if($author->status === 'verified')Mulai upload buku pertama kamu.@else Tunggu verifikasi admin dulu sebelum upload.@endif</p>
                </div>
            @else
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <tr><th class="px-4 py-2">Judul</th><th class="px-4 py-2">Status</th><th class="px-4 py-2 text-right">Harga</th><th class="px-4 py-2 text-right">Terjual</th></tr>
                        </thead>
                        <tbody>
                            @foreach($books as $book)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3">{{ $book->title }}</td>
                                    <td class="px-4 py-3">
                                        @switch($book->status)
                                            @case('active')<span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Live</span>@break
                                            @case('pending_review')<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Review</span>@break
                                            @case('rejected')<span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">Ditolak</span>@break
                                            @case('archived')<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Archive</span>@break
                                            @default<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $book->status }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ $book->formattedPrice() }}</td>
                                    <td class="px-4 py-3 text-right">{{ $book->sales_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
