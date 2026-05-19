<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">📕 Buku Saya</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola katalog ebook yang kamu jual.</p>
            </div>
            <a href="{{ route('author.books.create') }}" class="btn-primary !py-2 !text-sm">+ Upload Buku Baru</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        @if($books->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="text-4xl">📕</p>
                <p class="mt-2 font-semibold">Belum ada buku.</p>
                <p class="mt-1 text-sm text-slate-500">Upload buku pertama kamu sekarang.</p>
                <a href="{{ route('author.books.create') }}" class="btn-primary mt-6">+ Upload Buku Baru</a>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-2">Buku</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2 text-right">Harga</th>
                            <th class="px-4 py-2 text-right">Terjual</th>
                            <th class="px-4 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($books as $book)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-14 w-10 flex-shrink-0 overflow-hidden rounded bg-slate-100">
                                            @if($book->cover_path)
                                                <img src="{{ \Illuminate\Support\Str::startsWith($book->cover_path, ['http://', 'https://']) ? $book->cover_path : asset('storage/'.$book->cover_path) }}" alt="" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $book->title }}</p>
                                            <p class="text-xs text-slate-500">{{ $book->category->name ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @switch($book->status)
                                        @case('active')<span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">✓ Live</span>@break
                                        @case('pending_review')<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">⏳ Review</span>@break
                                        @case('draft')<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Draft</span>@break
                                        @case('rejected')
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700" title="{{ $book->rejection_reason }}">✕ Ditolak</span>
                                            @break
                                        @case('archived')<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Archived</span>@break
                                    @endswitch
                                </td>
                                <td class="px-4 py-3 text-right">{{ $book->formattedPrice() }}</td>
                                <td class="px-4 py-3 text-right">{{ $book->sales_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2 text-xs">
                                        @if(in_array($book->status, ['draft','rejected','active']))
                                            <a href="{{ route('author.books.edit', $book) }}" class="text-brand-600 hover:underline">Edit</a>
                                        @endif
                                        @if(in_array($book->status, ['draft','rejected']))
                                            <form method="POST" action="{{ route('author.books.submit', $book) }}" class="inline">
                                                @csrf
                                                <button class="text-amber-600 hover:underline">Submit ulang</button>
                                            </form>
                                        @endif
                                        @if($book->status !== 'archived')
                                            <form method="POST" action="{{ route('author.books.archive', $book) }}" class="inline" onsubmit="return confirm('Archive buku ini? Pembeli existing tetap bisa download, tapi buku tidak muncul lagi di katalog.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">Archive</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if($book->status === 'rejected' && $book->rejection_reason)
                                <tr class="border-t border-slate-100 bg-red-50">
                                    <td colspan="5" class="px-4 py-2 text-xs text-red-700">
                                        <strong>Alasan ditolak:</strong> {{ $book->rejection_reason }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $books->links() }}</div>
        @endif
    </div>
</x-app-layout>
