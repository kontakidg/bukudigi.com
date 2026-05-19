<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900">Informasi Profil</h2>
        <p class="mt-1 text-sm text-slate-500">Update nama, email, dan nomor WhatsApp.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus
                   class="input @error('name') !border-red-500 @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                   class="input @error('email') !border-red-500 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-xs text-slate-600">
                    Email belum diverifikasi.
                    <button form="send-verification" class="text-brand-600 hover:underline">Kirim ulang link verifikasi</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 text-xs text-green-600">Link verifikasi baru sudah dikirim ke email kamu.</p>
                @endif
            @endif
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">
                Nomor WhatsApp
                @if($user->wa_verified_at)
                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">✓ Terverifikasi</span>
                @elseif($user->phone)
                    <a href="{{ route('wa.verify.show') }}" class="ml-1 text-xs font-semibold text-brand-600 hover:underline">Verifikasi sekarang →</a>
                @endif
            </label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx"
                   class="input @error('phone') !border-red-500 @enderror">
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-slate-500">Untuk notifikasi pembelian, OTP, dan dukungan.</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">Simpan Perubahan</button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-green-600">✓ Tersimpan</p>
            @endif
        </div>
    </form>
</section>
