@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Panduan Upload Buku',
    'lead' => 'Langkah demi langkah upload buku pertama kamu di bukudigi.com — dari persiapan file sampai live.',
])

<section class="mx-auto max-w-3xl px-4 py-12">
    <div class="prose prose-slate max-w-none">

        <h2>Persiapkan dulu — checklist sebelum upload</h2>
        <ul>
            <li>✅ <strong>File PDF master</strong> — bersih, font ter-embed, tidak ter-encrypt/password, maks 50 MB</li>
            <li>✅ <strong>Cover image</strong> — JPG/PNG/WebP, max 5 MB, rasio 3:4 (mis. 600×800 atau 900×1200 px)</li>
            <li>✅ <strong>Judul buku</strong> — final, jelas, mewakili isi</li>
            <li>✅ <strong>Deskripsi</strong> — 200–800 karakter ideal, menjelaskan untuk siapa &amp; isi buku</li>
            <li>✅ <strong>Daftar isi</strong> — opsional, sangat membantu pembeli (sangat dianjurkan!)</li>
            <li>✅ <strong>Kategori &amp; tag</strong> — minimum 1 kategori, 3-5 tag relevan</li>
            <li>✅ <strong>Harga</strong> — antara Rp 15.000–Rp 500.000</li>
            <li>✅ <strong>Akun penulis sudah terverifikasi</strong> oleh admin (kalau belum, tunggu 1-2 hari setelah daftar)</li>
        </ul>

        <h2>Spesifikasi file PDF</h2>
        <ul>
            <li><strong>Format:</strong> PDF (Portable Document Format) versi 1.4 atau lebih baru</li>
            <li><strong>Ukuran:</strong> maks 50 MB</li>
            <li><strong>Jumlah halaman:</strong> tidak ada minimum/maksimum, tapi panduan: ebook 100–300 halaman optimal untuk pembaca</li>
            <li><strong>Font:</strong> embed di PDF (jangan rely on font lokal user) — di Word: Save As → Tools → Save Options → "Embed fonts"</li>
            <li><strong>Tidak boleh:</strong> ter-encrypt, password-protected, atau pakai DRM lain (sistem watermark kami yang akan handle proteksi)</li>
            <li><strong>Resolution gambar di PDF:</strong> 150-300 DPI cukup. Lebih tinggi cuma boros size, sama saja di layar.</li>
            <li><strong>Test sebelum upload:</strong> buka di Adobe Reader atau Chrome — harus tampil dengan benar dari halaman pertama sampai terakhir</li>
        </ul>

        <h2>Spesifikasi cover image</h2>
        <ul>
            <li><strong>Rasio:</strong> 3:4 (potret) — contoh: 600×800, 900×1200, 1200×1600 px</li>
            <li><strong>Format:</strong> JPG (rekomendasi), PNG, atau WebP. Maks 5 MB.</li>
            <li><strong>Konten cover:</strong> judul buku jelas terbaca, nama penulis (opsional tapi membantu), visual yang menarik &amp; representatif</li>
            <li><strong>Hindari:</strong>
                <ul>
                    <li>Stok foto generik tanpa lisensi → bisa kena copyright</li>
                    <li>Gambar dari internet tanpa atribusi/izin</li>
                    <li>Cover yang terlalu busy / text tidak terbaca di thumbnail kecil</li>
                </ul>
            </li>
            <li><strong>Rekomendasi tool gratis untuk bikin cover:</strong> Canva (template "Book Cover"), Figma, GIMP, Photopea</li>
        </ul>

        <h2>Cara nulis deskripsi yang menjual</h2>
        <p>Struktur yang work untuk pembaca:</p>
        <ol>
            <li><strong>Hook 1 kalimat</strong> — apa masalah/tujuan pembaca yang dibuku selesaikan</li>
            <li><strong>Untuk siapa</strong> — target reader spesifik (pemula? mahasiswa? profesional?)</li>
            <li><strong>Apa yang akan didapat</strong> — 3–5 poin konkret yang dipelajari</li>
            <li><strong>Bukti / kredibilitas</strong> — pengalaman/portofolio penulis (opsional)</li>
            <li><strong>Call to action ringan</strong> — "Klik preview untuk lihat halaman pertama"</li>
        </ol>
        <p class="text-sm text-slate-600">
            <strong>Hindari:</strong> klaim berlebihan ("dijamin jadi kaya"), copy-paste dari blurb buku lain, deskripsi terlalu pendek (&lt; 50 kata) atau terlalu panjang (&gt; 1000 kata).
        </p>

        <h2>Strategi harga</h2>
        <ul>
            <li><strong>Cek kompetitor:</strong> buka kategori yang sama, lihat range harga buku serupa</li>
            <li><strong>Rule of thumb:</strong>
                <ul>
                    <li>Tutorial pendek / short ebook (50-100 hal): Rp 25–55 ribu</li>
                    <li>Buku menengah (100-200 hal): Rp 49–99 ribu</li>
                    <li>Buku komprehensif / referensi (200+ hal): Rp 99–199 ribu</li>
                    <li>Buku niche premium / studi kasus eksklusif: Rp 199–500 ribu</li>
                </ul>
            </li>
            <li><strong>Tips testing:</strong> launch dengan harga "intro" 25-50% lebih rendah selama 2-4 minggu untuk dapat momentum review/sales count. Kemudian naikkan ke harga normal.</li>
            <li><strong>Jangan terlalu murah</strong> — harga Rp 15-20 ribu sering dipersepsikan rendah kualitas. Kecuali memang ebook pendek &lt; 50 halaman.</li>
        </ul>

        <h2>Step-by-step upload</h2>
        <ol>
            <li>Login ke akun penulis kamu (dashboard accessible di <code class="rounded bg-slate-100 px-1.5 py-0.5">/author/dashboard</code>)</li>
            <li>Klik <strong>+ Upload Buku</strong> di header dashboard atau halaman Buku Saya</li>
            <li>Isi form: judul → kategori → harga → deskripsi → daftar isi (opsional) → tag</li>
            <li>Upload <strong>cover</strong> (image) dan <strong>file PDF master</strong></li>
            <li>Centang <strong>Disclosure AI</strong> kalau kamu menggunakan AI dalam pembuatan</li>
            <li>Centang <strong>Persetujuan</strong> (konten asli / kamu berhak menjual)</li>
            <li>Klik <strong>Submit untuk Moderasi</strong></li>
            <li>Status berubah jadi <strong>"Review"</strong> — admin akan cek dalam 1-2 hari kerja</li>
            <li>Kamu dapat WhatsApp notifikasi saat approved (buku langsung live) atau rejected (dengan alasan)</li>
        </ol>

        <h2>Alasan penolakan umum (dan cara hindari)</h2>
        <ul>
            <li><strong>"File PDF tidak valid / ter-encrypt"</strong> → unprotect dulu PDF (Acrobat → File → Properties → Security)</li>
            <li><strong>"Cover mengandung gambar berlisensi tidak jelas"</strong> → pakai gambar self-made, atau Unsplash/Pexels (free) dengan atribusi yang benar</li>
            <li><strong>"Deskripsi terlalu pendek/copy-paste"</strong> → tulis original minimal 200 karakter</li>
            <li><strong>"Konten dugaan plagiat/bajakan"</strong> → pastikan kamu pemilik karya, tidak menyalin dari sumber lain tanpa izin</li>
            <li><strong>"Harga di luar batas"</strong> → set di range Rp 15.000–500.000</li>
            <li><strong>"Cover tidak menampilkan judul buku"</strong> → judul harus terbaca di thumbnail kecil (test di 200×267 px)</li>
        </ul>

        <h2>Setelah live — tips meningkatkan penjualan</h2>
        <ul>
            <li><strong>Bagi link aktif:</strong> share URL buku kamu (mis. <code class="rounded bg-slate-100 px-1.5 py-0.5">bukudigi.com/buku/judul-buku</code>) ke audiens kamu (Instagram, Twitter, blog, milis)</li>
            <li><strong>Aktif di kategori:</strong> upload buku ke-2, ke-3 di kategori yang sama untuk membangun otoritas</li>
            <li><strong>Update deskripsi:</strong> kalau ada feedback dari pembeli awal, refine deskripsi/judul biar lebih on-target</li>
            <li><strong>Preview yang menggoda:</strong> pastikan 5 halaman pertama buku adalah yang paling kuat — itu yang dipakai untuk preview gratis</li>
            <li><strong>Konten gratis sebagai funnel:</strong> tulis artikel/post sosmed yang related, tambahkan link "Versi lengkap di bukudigi.com"</li>
            <li><strong>Bundling natural:</strong> kalau punya 3+ buku di topik sama, kasih hint di deskripsi ("Lihat juga buku saya ttg ...")</li>
        </ul>

        <h2>Edit / archive buku setelah live</h2>
        <ul>
            <li><strong>Edit:</strong> di dashboard → Buku Saya → Edit. Untuk perubahan substansial (file PDF baru, judul, harga), akan kembali masuk review.</li>
            <li><strong>Archive:</strong> klik Archive di tabel buku saya. Buku tidak muncul lagi di katalog publik, tapi pembeli existing tetap bisa download.</li>
            <li><strong>Tidak bisa hapus permanen kalau sudah ada pembeli</strong> — hanya bisa archive. Pembeli punya hak akses selama masa link aktif.</li>
        </ul>

        <h2>Hal yang dilarang</h2>
        <ul>
            <li>Upload buku yang bukan karya kamu / tanpa izin pemilik hak (lihat <a href="{{ route('info.syarat') }}#7-konten-dilarang">§Konten Dilarang</a>)</li>
            <li>Upload buku yang sama berkali-kali dengan judul berbeda untuk manipulasi SEO/kategori</li>
            <li>Memanipulasi sales count atau review (kalau ada di masa depan)</li>
            <li>Mengakali sistem watermark (mis. upload PDF kosong + share PDF asli di luar platform)</li>
        </ul>
        <p>
            Pelanggaran = buku ditarik, royalti pending dibatalkan, dan kalau berulang/serius = akun suspended permanen.
        </p>

        <p class="mt-10 border-t border-slate-200 pt-6 text-sm text-slate-500">
            Ada pertanyaan spesifik? <a href="{{ route('info.bantuan') }}" class="text-brand-600">Lihat Pusat Bantuan</a> atau email <a href="mailto:support@bukudigi.com" class="text-brand-600">support@bukudigi.com</a>.
        </p>
    </div>
</section>
@endsection
