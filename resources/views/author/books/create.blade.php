<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold">+ Upload Buku Baru</h1>
            <p class="mt-1 text-sm text-slate-500">Setelah submit, admin akan review dalam 1-2 hari kerja.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8">
        @include('author.books._form')
    </div>
</x-app-layout>
