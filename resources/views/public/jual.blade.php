@extends('layouts.app')

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800 py-16 text-white md:py-24">
    <div class="absolute inset-0 opacity-20" aria-hidden="true">
        <svg class="absolute -right-20 -top-20 h-96 w-96" fill="none" viewBox="0 0 200 200"><defs><pattern id="p2" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="white"/></pattern></defs><rect width="200" height="200" fill="url(#p2)"/></svg>
    </div>
    <div class="relative mx-auto max-w-4xl px-4 text-center">
        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur">✍️ Untuk Penulis Indonesia</span>
        <h1 class="mt-4 text-3xl font-bold leading-tight drop-shadow-md md:text-5xl">Jadi penulis di bukudigi.<br>Royalti masuk rekening tanggal 5.</h1>
        <p class="mt-4 text-lg text-brand-100">Royalti penulis <strong class="text-white">70%</strong> (affiliate 10%, platform 20%). Dibayar dalam <strong class="text-white">Rupiah</strong> ke rekening bank kamu. Tanpa kontrak penerbit, tanpa minimum sales.</p>
        <div class="mt-8">
            @auth
                <a href="{{ route('author.register.show') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-brand-700 shadow hover:bg-brand-50">Mulai Daftar Penulis →</a>
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-brand-700 shadow hover:bg-brand-50">Daftar Akun Dulu →</a>
                <p class="mt-3 text-sm text-brand-100">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-white underline">Masuk</a></p>
            @endauth
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12">
    <h2 class="text-center text-2xl font-bold md:text-3xl">Kenapa jualan di bukudigi?</h2>
    <div class="mt-8 grid gap-6 md:grid-cols-3">
        <div class="card p-6">
            <span class="text-3xl">💰</span>
            <h3 class="mt-3 font-bold">Royalti 70%</h3>
            <p class="mt-2 text-sm text-slate-600">Dari setiap pembelian, kamu terima 70% gross. Affiliate 10%, platform 20%. Tanpa potongan tersembunyi.</p>
        </div>
        <div class="card p-6">
            <span class="text-3xl">📅</span>
            <h3 class="mt-3 font-bold">Payout Bulanan dalam Rupiah</h3>
            <p class="mt-2 text-sm text-slate-600">Saldo &gt; Rp 100.000 otomatis ditransfer tanggal 5 setiap bulan ke rekening bank Indonesia kamu dalam <strong>Rupiah</strong>.</p>
        </div>
        <div class="card p-6">
            <span class="text-3xl">🔒</span>
            <h3 class="mt-3 font-bold">Anti Bajakan</h3>
            <p class="mt-2 text-sm text-slate-600">Setiap PDF & EPUB di-watermark identitas pembeli. Bocoran terlacak ke akun aslinya.</p>
        </div>
        <div class="card p-6">
            <span class="text-3xl">📊</span>
            <h3 class="mt-3 font-bold">Dashboard Real-time</h3>
            <p class="mt-2 text-sm text-slate-600">Pantau penjualan harian, mingguan, bulanan langsung dari akun kamu.</p>
        </div>
        <div class="card p-6">
            <span class="text-3xl">✨</span>
            <h3 class="mt-3 font-bold">Promosi Gratis</h3>
            <p class="mt-2 text-sm text-slate-600">Buku kamu otomatis muncul di kategori, search, dan rekomendasi homepage.</p>
        </div>
        <div class="card p-6">
            <span class="text-3xl">🤝</span>
            <h3 class="mt-3 font-bold">Support Manusia</h3>
            <p class="mt-2 text-sm text-slate-600">Admin balas WA dalam 24 jam untuk masalah upload, moderasi, atau payout.</p>
        </div>
    </div>
</section>

<section class="bg-white py-12">
    <div class="mx-auto max-w-4xl px-4">
        <h2 class="text-center text-2xl font-bold md:text-3xl">Cara mulai dalam 4 langkah</h2>
        <div class="mt-8 space-y-4">
            @foreach([
                ['1', 'Daftar akun', 'Daftar sebagai user biasa dulu di bukudigi.com.'],
                ['2', 'Upgrade ke penulis', 'Isi data NIK, rekening bank, upload KTP & selfie untuk verifikasi.'],
                ['3', 'Upload buku', 'PDF + cover + deskripsi (EPUB opsional, biar pembaca bisa baca online di reader bukudigi). AI + admin moderasi 1-2 hari kerja.'],
                ['4', 'Buku live, royalti jalan', 'Setelah approve, buku langsung muncul di katalog. Royalti masuk tanggal 5 bulan berikutnya.'],
            ] as $step)
                <div class="flex gap-4 rounded-xl border border-slate-200 bg-white p-5">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-700">{{ $step[0] }}</span>
                    <div>
                        <h3 class="font-bold">{{ $step[1] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $step[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-8 text-center text-white">
            <h3 class="text-xl font-bold">Siap mulai?</h3>
            <p class="mt-2 text-brand-100">Pendaftaran gratis. Tidak ada biaya bulanan, tidak ada minimum.</p>
            @auth
                <a href="{{ route('author.register.show') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-brand-700 shadow hover:bg-brand-50">Daftar Penulis Sekarang →</a>
            @else
                <a href="{{ route('register') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-brand-700 shadow hover:bg-brand-50">Buat Akun →</a>
            @endauth
        </div>
    </div>
</section>
@endsection
