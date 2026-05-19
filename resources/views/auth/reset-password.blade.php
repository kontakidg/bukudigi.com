<x-guest-layout title="Reset Password">
    <h1 class="text-2xl font-bold text-slate-900">Buat password baru</h1>
    <p class="mt-1 text-sm text-slate-500">Pilih password baru untuk akun kamu.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   required autofocus class="input @error('email') !border-red-500 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password Baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8"
                   class="input @error('password') !border-red-500 @enderror">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="input">
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Simpan Password Baru</button>
    </form>
</x-guest-layout>
