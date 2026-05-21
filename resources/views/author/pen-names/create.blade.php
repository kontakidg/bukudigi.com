<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold">+ Tambah Pen Name</h1>
        <p class="mt-1 text-sm text-slate-500">Identitas baru untuk publikasi buku kamu.</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8">
        @include('author.pen-names._form')
    </div>
</x-app-layout>
