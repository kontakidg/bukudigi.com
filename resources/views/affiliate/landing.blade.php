@extends('layouts.app')

@section('title', 'Program Affiliate — bukudigi.com')

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-emerald-700 via-emerald-600 to-teal-700 py-16 text-white md:py-24">
    <div class="absolute inset-0 opacity-20" aria-hidden="true">
        <svg class="absolute -right-20 -top-20 h-96 w-96" fill="none" viewBox="0 0 200 200"><defs><pattern id="pa" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="white"/></pattern></defs><rect width="200" height="200" fill="url(#pa)"/></svg>
    </div>
    <div class="relative mx-auto max-w-4xl px-4 text-center">
        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur">🔗 Program Affiliate bukudigi</span>
        <h1 class="mt-4 text-3xl font-bold leading-tight drop-shadow-md md:text-5xl">Dapat <strong>10% komisi</strong><br>tiap orang beli buku lewat link kamu.</h1>
        <p class="mt-4 text-lg text-emerald-50">Cocok buat content creator, reviewer buku, dosen, komunitas baca. Cookie 30 hari, payout bulanan tgl 5.</p>
        <div class="mt-8">
            @auth
                @if(auth()->user()->affiliate)
                    <a href="{{ route('affiliate.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-emerald-700 shadow hover:bg-emerald-50">Buka Dashboard Affiliate →</a>
                @else
                    <a href="{{ route('affiliate.register.show') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-emerald-700 shadow hover:bg-emerald-50">Daftar Jadi Affiliate →</a>
                @endif
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-emerald-700 shadow hover:bg-emerald-50">Daftar Akun Dulu →</a>
                <p class="mt-3 text-sm text-emerald-100">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-white underline">Masuk</a></p>
            @endauth
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-12">
    <h2 class="text-center text-2xl font-bold md:text-3xl">Cara kerja</h2>
    <div class="mt-8 grid gap-6 md:grid-cols-4">
        <div class="card p-6 text-center">
            <div class="text-3xl">1️⃣</div>
            <h3 class="mt-3 font-bold">Daftar & approval</h3>
            <p class="mt-2 text-sm text-slate-600">Isi form komitmen + channel promosi. Admin review 1–2 hari.</p>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl">2️⃣</div>
            <h3 class="mt-3 font-bold">Dapat link unik</h3>
            <p class="mt-2 text-sm text-slate-600">Bisa share link halaman utama atau per buku. Format: <code>?ref=KODE</code></p>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl">3️⃣</div>
            <h3 class="mt-3 font-bold">Cookie 30 hari</h3>
            <p class="mt-2 text-sm text-slate-600">Pembeli klik link kamu, beli dalam 30 hari = komisi tetap masuk.</p>
        </div>
        <div class="card p-6 text-center">
            <div class="text-3xl">4️⃣</div>
            <h3 class="mt-3 font-bold">Payout tgl 5</h3>
            <p class="mt-2 text-sm text-slate-600">Cooling 7 hari sejak transaksi. Threshold Rp 100.000.</p>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-12">
    <div class="mx-auto max-w-4xl px-4">
        <h2 class="text-center text-2xl font-bold md:text-3xl">Komitmen affiliate (wajib disepakati)</h2>
        <div class="mt-6 card p-6 space-y-3 text-sm text-slate-700">
            <p>✅ Promosikan buku secara <strong>jujur</strong> — tidak menjanjikan klaim palsu (cth: "buku ini bikin kaya dalam 7 hari").</p>
            <p>✅ <strong>Tidak spam</strong> ke grup WhatsApp/Telegram/komentar yang tidak relevan.</p>
            <p>✅ <strong>Tidak self-refer</strong> — klik dari akun sendiri tidak dihitung.</p>
            <p>✅ <strong>Tidak inject ads</strong> di domain orang lain tanpa izin (no cookie stuffing).</p>
            <p>✅ Tidak melakukan <strong>fake order</strong> / fraud — saldo akan di-suspend kalau terdeteksi pola mencurigakan.</p>
            <p>✅ Bersedia <strong>akun di-review ulang</strong> kapan saja oleh admin.</p>
            <p class="text-slate-500 italic">Pelanggaran komitmen di atas dapat menyebabkan akun affiliate di-suspend dan saldo pending di-cancel.</p>
        </div>
    </div>
</section>

<section class="mx-auto max-w-4xl px-4 py-12">
    <h2 class="text-center text-2xl font-bold md:text-3xl">FAQ</h2>
    <div class="mt-6 space-y-3">
        <details class="card p-4"><summary class="cursor-pointer font-semibold">Berapa komisi yang saya dapat?</summary><p class="mt-2 text-sm text-slate-600">10% dari net amount transaksi (setelah voucher kalau ada). Misal pembeli bayar Rp 50.000, kamu dapat Rp 5.000.</p></details>
        <details class="card p-4"><summary class="cursor-pointer font-semibold">Apakah penulis juga bisa jadi affiliate?</summary><p class="mt-2 text-sm text-slate-600">Bisa. Penulis bisa daftar affiliate juga — tapi tidak boleh refer buku sendiri (akan terdeteksi self-refer dan tidak dihitung).</p></details>
        <details class="card p-4"><summary class="cursor-pointer font-semibold">Berapa lama cookie tracking?</summary><p class="mt-2 text-sm text-slate-600">30 hari sejak klik terakhir. Last-click model — kalau pengunjung klik link affiliate lain di tengah jalan, kreditnya pindah.</p></details>
        <details class="card p-4"><summary class="cursor-pointer font-semibold">Kapan saya dibayar?</summary><p class="mt-2 text-sm text-slate-600">Tanggal 5 tiap bulan, untuk saldo yang sudah lewat cooling period 7 hari sejak transaksi. Minimum payout Rp 100.000. Kalau belum tercapai, di-carry over ke bulan berikutnya.</p></details>
        <details class="card p-4"><summary class="cursor-pointer font-semibold">Kenapa harus di-approve admin?</summary><p class="mt-2 text-sm text-slate-600">Untuk menjaga kualitas program — kami review channel promosi & motivasi kamu agar program ini tetap sustainable buat semua pihak.</p></details>
    </div>
</section>

<section class="bg-gradient-to-r from-emerald-600 to-teal-600 py-12 text-white">
    <div class="mx-auto max-w-3xl px-4 text-center">
        <h2 class="text-2xl font-bold md:text-3xl">Siap mulai cuan dari rekomendasi buku?</h2>
        <p class="mt-3 text-emerald-100">Daftar sekarang. Review admin 1–2 hari kerja.</p>
        <div class="mt-6">
            @auth
                @if(auth()->user()->affiliate)
                    <a href="{{ route('affiliate.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-emerald-700 hover:bg-emerald-50">Buka Dashboard →</a>
                @else
                    <a href="{{ route('affiliate.register.show') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-emerald-700 hover:bg-emerald-50">Daftar Affiliate →</a>
                @endif
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-emerald-700 hover:bg-emerald-50">Daftar Akun Dulu →</a>
            @endauth
        </div>
    </div>
</section>
@endsection
