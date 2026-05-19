<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold">📱 Verifikasi WhatsApp</h1>
        <p class="mt-1 text-sm text-slate-500">Verifikasi nomor untuk menerima notifikasi pesanan & OTP login.</p>
    </x-slot>

    <div class="mx-auto max-w-md px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        <div class="card p-6">
            <p class="text-sm text-slate-600">
                Nomor terdaftar: <span class="font-semibold text-slate-900">{{ $phone }}</span>
            </p>

            @if (! $sent)
                <form method="POST" action="{{ route('wa.verify.send') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn-primary w-full !py-3">
                        Kirim Kode OTP via WhatsApp
                    </button>
                    @error('phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </form>
            @else
                <form method="POST" action="{{ route('wa.verify.confirm') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="otp" class="mb-1 block text-sm font-medium text-slate-700">Masukkan 6 digit OTP</label>
                        <input id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" pattern="\d{6}"
                               required autofocus
                               class="input text-center text-2xl tracking-widest @error('otp') !border-red-500 @enderror"
                               placeholder="000000">
                        @error('otp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary w-full !py-3">Verifikasi</button>
                </form>

                <form method="POST" action="{{ route('wa.verify.send') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn-ghost w-full text-xs">Kirim ulang OTP</button>
                </form>
            @endif
        </div>

        <p class="mt-4 text-center text-xs text-slate-500">
            <a href="{{ route('profile.edit') }}" class="hover:text-brand-600">← Kembali ke profil</a>
        </p>
    </div>
</x-app-layout>
