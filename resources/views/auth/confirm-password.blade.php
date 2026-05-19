<x-guest-layout title="Konfirmasi Password">
    <h1 class="text-2xl font-bold text-slate-900">Konfirmasi password</h1>
    <p class="mt-1 text-sm text-slate-500">Area ini sensitif. Konfirmasi password kamu untuk lanjut.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="input @error('password') !border-red-500 @enderror">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Konfirmasi</button>
    </form>
</x-guest-layout>
