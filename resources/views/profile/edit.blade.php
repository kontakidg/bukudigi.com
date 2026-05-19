<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold">⚙ Pengaturan Profil</h1>
        <p class="mt-1 text-sm text-slate-500">Atur data akun, password, dan zona keamanan.</p>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8">
        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
