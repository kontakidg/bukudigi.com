@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Tentang Kami',
    'lead' => 'bukudigi.com — marketplace ebook PDF dari penulis Indonesia, untuk pembaca Indonesia.',
])

<section class="mx-auto max-w-3xl px-4 py-12">
    <div class="prose prose-slate max-w-none">
        <h2>Apa itu bukudigi.com?</h2>
        <p>
            bukudigi.com adalah platform digital tempat penulis individu menjual ebook PDF langsung ke pembaca,
            tanpa perantara penerbit. Kami menyediakan infrastruktur pembayaran, hosting file, watermarking,
            dan moderasi konten — sehingga penulis bisa fokus berkarya, dan pembaca dapat akses karya lokal
            dengan harga adil.
        </p>

        <h2>Posisi kami: marketplace, bukan penerbit</h2>
        <p>
            bukudigi.com adalah <strong>perantara teknologi (marketplace)</strong> yang menghubungkan penulis dan pembaca.
            Kami <strong>tidak menerbitkan, mengedit, atau memodifikasi</strong> konten karya. Semua karya yang tampil
            di bukudigi.com adalah milik &amp; tanggung jawab penulis masing-masing.
        </p>
        <p>
            Penulis memberi lisensi <em>non-eksklusif terbatas</em> kepada kami hanya untuk: menyimpan file,
            menampilkan preview, memproses pembayaran, melakukan watermarking, dan mendistribusikan kepada pembeli sah.
            Hak cipta tetap 100% milik penulis.
        </p>

        <h2>Untuk siapa?</h2>
        <ul>
            <li><strong>Pembaca</strong> — siapa saja yang ingin akses ebook lokal Indonesia tanpa langganan, bayar sekali pakai QRIS atau Virtual Account, langsung download dengan watermark personal.</li>
            <li><strong>Penulis</strong> — penulis indie, dosen, content creator, UMKM yang punya karya digital tapi tidak ingin/tidak bisa lewat penerbit konvensional.</li>
        </ul>

        <h2>Apa yang membedakan kami</h2>
        <ul>
            <li><strong>Pembayaran lokal</strong> — QRIS, Virtual Account BCA/BNI/Mandiri, GoPay, OVO, DANA. Tidak perlu kartu kredit.</li>
            <li><strong>Watermark personal</strong> — setiap PDF yang dibeli ada watermark nama &amp; email pembeli di setiap halaman. Anti-bocoran sosial, tapi pembeli tetap bisa baca offline, print, simpan permanen.</li>
            <li><strong>Komisi adil</strong> — penulis menerima 70% dari harga gross, affiliate 10%, platform 20%. Royalti masuk rekening setiap tanggal 5.</li>
            <li><strong>Moderasi manusia</strong> — setiap buku ditinjau admin sebelum live, untuk pastikan tidak ada bajakan, konten ilegal, atau plagiat.</li>
            <li><strong>Transparan</strong> — biaya, komisi, jadwal payout, semua tertulis di <a href="{{ route('info.komisi') }}" class="text-brand-600">halaman komisi</a>.</li>
        </ul>

        <h2>Nilai yang kami pegang</h2>
        <ol>
            <li><strong>Hormat hak cipta.</strong> Konten bajakan akan dihapus dan akun di-suspend. Pemilik hak yang merasa karyanya dibajak bisa lapor lewat <a href="{{ route('info.bantuan') }}" class="text-brand-600">pusat bantuan</a>.</li>
            <li><strong>Transparansi keuangan.</strong> Penulis melihat semua pembelian buku mereka real-time. Tidak ada potongan tersembunyi.</li>
            <li><strong>Privasi pembeli.</strong> Data pembelian &amp; identitas pembeli tidak dibagikan ke penulis. Watermark personal hanya tampil di file pembeli, bukan publik.</li>
            <li><strong>Bahasa &amp; nilai Indonesia.</strong> Konten dimoderasi sesuai norma sosial Indonesia, tidak menerima konten porno/SARA/diskriminasi.</li>
        </ol>

        <h2>Operator platform</h2>
        <p>
            bukudigi.com dioperasikan dari Indonesia. Untuk korespondensi resmi (legal, hak cipta, pers, kerja sama),
            kirim email ke <a href="mailto:hello@bukudigi.com" class="text-brand-600">hello@bukudigi.com</a>.
            Detail badan hukum dan alamat resmi akan dipublikasikan di sini setelah pendaftaran PSE (Penyelenggara
            Sistem Elektronik) di Kominfo selesai.
        </p>

        <h2>Roadmap ke depan</h2>
        <ul>
            <li>Dukungan format EPUB selain PDF</li>
            <li>Sistem rating &amp; review pembeli</li>
            <li>Bundle / paket buku</li>
            <li>Affiliate program untuk reviewer/blogger</li>
            <li>Aplikasi mobile (iOS &amp; Android)</li>
        </ul>

        <p class="mt-8 border-t border-slate-200 pt-6 text-sm text-slate-500">
            Punya pertanyaan, masukan, atau ingin kerja sama?
            <a href="{{ route('info.bantuan') }}" class="text-brand-600 hover:underline">Buka Pusat Bantuan →</a>
        </p>
    </div>
</section>
@endsection
