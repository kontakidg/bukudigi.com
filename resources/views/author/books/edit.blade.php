<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold">Edit Buku</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $book->title }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8">
        @if($book->status === 'active')
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                ⚠️ Buku ini sudah <strong>live</strong>. Perubahan akan langsung berlaku tanpa moderasi ulang.
                Untuk ganti file PDF, sebaiknya archive dulu lalu upload ulang.
            </div>
        @elseif($book->status === 'rejected')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <strong>Buku ini ditolak admin.</strong> Alasan: {{ $book->rejection_reason }}
                <br>Edit & submit ulang.
            </div>
        @endif

        @include('author.books._form')
    </div>
</x-app-layout>
