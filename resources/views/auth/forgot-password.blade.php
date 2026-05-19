<x-guest-layout title="Lupa Password">
    <h1 class="text-2xl font-bold text-slate-900">Reset password</h1>
    <p class="mt-1 text-sm text-slate-500">
        Masukkan email kamu, kami kirim link untuk reset password.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus class="input @error('email') !border-red-500 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full !py-3">Kirim Link Reset</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Ingat password? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Kembali ke login</a>
    </p>
</x-guest-layout>
