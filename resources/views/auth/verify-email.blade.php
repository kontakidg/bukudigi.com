<x-guest-layout title="Verifikasi Email">
    <h1 class="text-2xl font-bold text-slate-900">Cek inbox kamu</h1>
    <p class="mt-1 text-sm text-slate-500">
        Kami sudah kirim link verifikasi ke email yang kamu daftarkan. Klik link itu untuk mengaktifkan akun.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
            Link verifikasi baru sudah dikirim. Cek email kamu.
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full !py-3">Kirim Ulang Link</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost w-full !py-3">Keluar</button>
        </form>
    </div>
</x-guest-layout>
