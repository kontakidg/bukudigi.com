<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold">Edit Pen Name</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $penName->name }} · /{{ $penName->slug }}</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8">
        @include('author.pen-names._form')
    </div>
</x-app-layout>
