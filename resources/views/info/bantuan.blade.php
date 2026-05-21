@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Pusat Bantuan',
    'lead' => 'Jawaban cepat untuk pembeli dan penulis. Kalau yang kamu cari belum ada, hubungi kami.',
])

<section class="mx-auto max-w-4xl px-4 py-12">
    {{-- Quick links --}}
    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('info.faq') }}" class="card p-5">
            <span class="text-3xl">❓</span>
            <h3 class="mt-2 font-bold">FAQ</h3>
            <p class="mt-1 text-sm text-slate-600">Pertanyaan paling umum dari pembeli &amp; penulis.</p>
        </a>
        <a href="{{ route('info.komisi') }}" class="card p-5">
            <span class="text-3xl">💰</span>
            <h3 class="mt-2 font-bold">Komisi &amp; Royalti</h3>
            <p class="mt-1 text-sm text-slate-600">Skema bagi hasil 80:20, payout bulanan, perhitungan pajak.</p>
        </a>
        <a href="{{ route('info.panduan-penulis') }}" class="card p-5">
            <span class="text-3xl">📘</span>
            <h3 class="mt-2 font-bold">Panduan Upload</h3>
            <p class="mt-1 text-sm text-slate-600">Cara upload buku step-by-step, spec teknis, tips.</p>
        </a>
    </div>

    {{-- Untuk Pembeli --}}
    <div class="mt-12">
        <h2 class="text-2xl font-bold">Untuk Pembeli</h2>
        <div class="mt-4 space-y-4">
            <div class="card p-5">
                <h3 class="font-bold">Cara membeli ebook</h3>
                <ol class="mt-2 ml-5 list-decimal text-sm text-slate-700">
                    <li>Daftar / login ke akun</li>
                    <li>Pilih buku yang ingin dibeli, klik <strong>🛒 Beli Sekarang</strong></li>
                    <li>Bayar via QRIS, Virtual Account, GoPay, OVO, DANA, atau ShopeePay</li>
                    <li>Setelah bayar sukses, buku otomatis muncul di Perpustakaan Saya</li>
                    <li>Klik <strong>⬇ Download PDF</strong> untuk unduh (maks 5x dalam 30 hari)</li>
                </ol>
            </div>

            <div class="card p-5">
                <h3 class="font-bold">Apa itu watermark personal?</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Setiap PDF yang kamu beli akan punya watermark berisi nama, email, dan kode order kamu
                    di footer setiap halaman dan teks "PREVIEW" diagonal samar. Ini supaya kalau ada yang
                    membocorkan file, kami bisa lacak ke akun aslinya. Watermark <strong>tidak menghalangi pembacaan</strong>
                    — kamu tetap bisa baca, print untuk konsumsi pribadi, simpan permanen di perangkat kamu.
                </p>
            </div>

            <div class="card p-5">
                <h3 class="font-bold">Refund: kapan bisa, kapan tidak</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Refund <strong>bisa diajukan dalam 7 hari</strong> setelah pembelian, dan <strong>hanya untuk alasan teknis</strong>:
                </p>
                <ul class="mt-2 ml-5 list-disc text-sm text-slate-700">
                    <li>File PDF rusak / tidak bisa dibuka di reader standar (Adobe Reader, Chrome, dll)</li>
                    <li>Halaman kosong atau corrupt setelah download</li>
                    <li>Pembelian tidak sengaja (double-click, dll) — laporkan dalam 24 jam</li>
                </ul>
                <p class="mt-2 text-sm text-slate-700">
                    Refund <strong>tidak diberikan</strong> karena: tidak suka isi buku, sudah download tapi
                    mengaku belum baca, atau alasan subjektif lain. Pakai fitur preview gratis sebelum beli.
                </p>
            </div>

            <div class="card p-5">
                <h3 class="font-bold">Link download tidak jalan / sudah expired</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Link download berlaku 30 hari sejak pembelian, maks 5 kali download. Setelah itu link
                    expired — hubungi kami via WA/email dengan kode order kamu untuk reset.
                </p>
            </div>
        </div>
    </div>

    {{-- Untuk Penulis --}}
    <div class="mt-12">
        <h2 class="text-2xl font-bold">Untuk Penulis</h2>
        <div class="mt-4 space-y-4">
            <div class="card p-5">
                <h3 class="font-bold">Cara daftar jadi penulis</h3>
                <ol class="mt-2 ml-5 list-decimal text-sm text-slate-700">
                    <li>Daftar akun pembeli dulu di <a href="{{ route('register') }}" class="text-brand-600">/register</a></li>
                    <li>Buka <a href="{{ route('jual') }}" class="text-brand-600">/jual</a> → klik "Mulai Daftar Penulis"</li>
                    <li>Isi data: nama tampil, bio, NIK 16 digit, NPWP (opsional), rekening bank</li>
                    <li>Upload foto KTP dan selfie + KTP</li>
                    <li>Submit. Admin verifikasi dalam 1-2 hari kerja</li>
                </ol>
            </div>

            <div class="card p-5">
                <h3 class="font-bold">Kenapa harus pakai KTP &amp; rekening sendiri?</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Untuk verifikasi identitas (mencegah fraud, pembajakan, akun palsu) dan kepatuhan pajak
                    (PPh 23). Data KTP disimpan terenkripsi, hanya admin verifikator yang akses, dan tidak
                    pernah dibagikan ke pihak ketiga selain kewajiban hukum.
                </p>
            </div>

            <div class="card p-5">
                <h3 class="font-bold">Kalau buku saya ditolak</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Kamu akan dapat WhatsApp notifikasi berisi alasan penolakan. Kamu bisa edit isi/file
                    dan submit ulang dari Dashboard → Buku Saya → Edit → "Submit ulang".
                </p>
            </div>

            <div class="card p-5">
                <h3 class="font-bold">Kapan royalti dibayarkan?</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Setiap <strong>tanggal 5 bulan berikutnya</strong>, untuk semua transaksi yang sudah lewat
                    masa cooling 7 hari. Threshold minimum Rp 100.000 — kalau di bawah itu, saldo carry-over.
                    Detail lengkap di <a href="{{ route('info.komisi') }}" class="text-brand-600">halaman komisi</a>.
                </p>
            </div>
        </div>
    </div>

    {{-- Laporan Pelanggaran --}}
    <div class="mt-12">
        <h2 class="text-2xl font-bold">Lapor Pelanggaran Hak Cipta</h2>
        <p class="mt-2 text-sm text-slate-700">
            Kalau kamu menemukan karya kamu (atau yang kamu wakili) dijual di bukudigi.com tanpa izin,
            silakan kirim laporan ke <a href="mailto:abuse@bukudigi.com" class="text-brand-600 font-semibold">abuse@bukudigi.com</a>
            dengan informasi:
        </p>
        <ul class="mt-2 ml-5 list-disc text-sm text-slate-700">
            <li>URL buku di bukudigi.com yang dilaporkan</li>
            <li>Bukti kepemilikan karya asli (sertifikat HKI, naskah asli, kontrak, dll)</li>
            <li>Identitas kamu &amp; cara dihubungi balik</li>
            <li>Pernyataan dengan itikad baik bahwa kamu pemilik hak yang sah</li>
        </ul>
        <p class="mt-2 text-sm text-slate-700">
            Kami akan review dalam <strong>maksimal 3 hari kerja</strong> dan menonaktifkan buku terlapor selama proses verifikasi.
            Detail prosedur ada di <a href="{{ route('info.syarat') }}#dmca" class="text-brand-600">Syarat &amp; Ketentuan §Pelanggaran Hak Cipta</a>.
        </p>
    </div>

    {{-- Kontak --}}
    <div class="mt-12 rounded-xl bg-gradient-to-br from-brand-50 to-white p-6">
        <h2 class="text-xl font-bold">Tidak menemukan jawaban?</h2>
        <p class="mt-2 text-sm text-slate-700">Hubungi kami via:</p>
        <ul class="mt-3 space-y-2 text-sm">
            <li>📧 Email umum: <a href="mailto:hello@bukudigi.com" class="text-brand-600 font-semibold">hello@bukudigi.com</a></li>
            <li>📧 Support pembeli/penulis: <a href="mailto:support@bukudigi.com" class="text-brand-600 font-semibold">support@bukudigi.com</a></li>
            <li>📧 Hak cipta &amp; legal: <a href="mailto:abuse@bukudigi.com" class="text-brand-600 font-semibold">abuse@bukudigi.com</a></li>
            <li>📧 Privasi data: <a href="mailto:privacy@bukudigi.com" class="text-brand-600 font-semibold">privacy@bukudigi.com</a></li>
        </ul>
        <p class="mt-3 text-xs text-slate-500">Response time: 1-2 hari kerja, Senin–Jumat 09.00–17.00 WIB.</p>
    </div>
</section>
@endsection
