<x-guest-layout title="Daftar">
    <h1 class="text-2xl font-bold text-slate-900">Buat akun bukudigi</h1>
    <p class="mt-1 text-sm text-slate-500">Daftar gratis, akses katalog ebook lokal.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   required autofocus autocomplete="name"
                   class="input @error('name') !border-red-500 @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autocomplete="username"
                   class="input @error('email') !border-red-500 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Nomor WhatsApp <span class="text-slate-400">(opsional)</span></label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                   placeholder="08xxxxxxxxxx" autocomplete="tel"
                   class="input @error('phone') !border-red-500 @enderror">
            <p class="mt-1 text-xs text-slate-500">Untuk notifikasi pembelian &amp; OTP. Bisa ditambah nanti.</p>
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" name="password"
                   required autocomplete="new-password" minlength="8"
                   class="input @error('password') !border-red-500 @enderror">
            <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password"
                   class="input">
        </div>

        <p class="text-xs text-slate-500">
            Dengan daftar, kamu setuju dengan <a href="{{ route('info.syarat') }}" class="text-brand-600 hover:underline">Syarat Layanan</a> dan <a href="{{ route('info.privasi') }}" class="text-brand-600 hover:underline">Kebijakan Privasi</a>.
        </p>

        <button type="submit" class="btn-primary w-full !py-3">Daftar Sekarang</button>
    </form>

    <div class="my-6 flex items-center gap-3">
        <div class="h-px flex-1 bg-slate-200"></div>
        <span class="text-xs text-slate-400">atau</span>
        <div class="h-px flex-1 bg-slate-200"></div>
    </div>

    <a href="{{ route('auth.google') }}"
       class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-brand-500 hover:bg-slate-50">
        <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A10.997 10.997 0 0012 23z"/><path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1 0-.73.13-1.44.35-2.1V7.07H2.18A10.997 10.997 0 001 12c0 1.77.42 3.45 1.18 4.93l3.66-2.83z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"/></svg>
        Lanjut dengan Google
    </a>

    <p class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Masuk</a>
    </p>
</x-guest-layout>
