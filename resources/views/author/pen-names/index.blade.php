<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">🖋️ Pen Name</h1>
                <p class="mt-1 text-sm text-slate-500">Identitas penulis yang tampil di publik. Bisa punya beberapa (misal satu untuk fiksi, satu untuk non-fiksi).</p>
            </div>
            <a href="{{ route('author.pen-names.create') }}" class="btn-primary !py-2 !text-sm">+ Tambah Pen Name</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        @if($penNames->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="text-4xl">🖋️</p>
                <p class="mt-2 font-semibold">Belum ada pen name.</p>
                <p class="mt-1 text-sm text-slate-500">Buat pen name pertama kamu — bisa nama asli atau nama pena.</p>
                <a href="{{ route('author.pen-names.create') }}" class="btn-primary mt-6">+ Tambah Pen Name</a>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($penNames as $pn)
                    <div class="card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-slate-900">{{ $pn->name }}</h3>
                                    @if($pn->is_default)
                                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold text-brand-700">DEFAULT</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">/{{ $pn->slug }} · {{ $pn->books_count }} buku</p>
                            </div>
                            <div class="flex gap-2 text-xs">
                                <a href="{{ route('author.pen-names.edit', $pn) }}" class="text-brand-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('author.pen-names.destroy', $pn) }}" class="inline" onsubmit="return confirm('Hapus pen name &quot;{{ $pn->name }}&quot;?');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </div>
                        @if($pn->bio)
                            <p class="mt-3 line-clamp-3 text-sm text-slate-600">{{ $pn->bio }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                <p class="font-semibold text-slate-700">ℹ️ Cara pakai</p>
                <ul class="ml-5 mt-1 list-disc">
                    <li><strong>Default</strong> = pen name yang otomatis dipakai kalau kamu tidak pilih saat upload buku.</li>
                    <li>Saat upload buku baru, kamu bisa pilih pen name mana untuk publish buku itu.</li>
                    <li>Buku yang sudah live tetap pakai pen name yang dipilih dulu — bisa diubah dari edit buku.</li>
                    <li>Pen name dengan buku aktif <strong>tidak bisa dihapus</strong> — pindahkan bukunya dulu.</li>
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
