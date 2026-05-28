@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Kebijakan Privasi',
    'lead' => 'Bagaimana kami mengumpulkan, menggunakan, dan melindungi data pribadi kamu — sesuai UU 27/2022 tentang Pelindungan Data Pribadi.',
    'updated' => '20 Mei 2026',
])

<section class="mx-auto max-w-3xl px-4 py-12">
    <div class="prose prose-slate max-w-none prose-h2:text-xl prose-h2:font-bold prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-h2:mt-10 prose-h3:text-base prose-h3:font-semibold prose-h3:mt-6">

        <h2 id="1-pengantar">1. Pengantar</h2>
        <p>
            bukudigi.com ("kami") menghargai privasi kamu. Kebijakan ini menjelaskan jenis data pribadi yang
            kami kumpulkan, cara penggunaan, dasar hukum pemrosesan, dengan siapa kami berbagi, dan hak kamu
            sebagai Subjek Data Pribadi sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi ("UU PDP").
        </p>

        <h2 id="2-data-yang-dikumpulkan">2. Data yang Kami Kumpulkan</h2>

        <h3>2.1 Saat kamu mendaftar akun (semua pengguna)</h3>
        <ul>
            <li><strong>Identitas dasar:</strong> nama, email, password (di-hash), nomor telepon/WhatsApp;</li>
            <li><strong>Data login sosial</strong> (jika pakai Google OAuth): Google user ID, email, nama;</li>
            <li><strong>Avatar/foto profil</strong> (opsional).</li>
        </ul>

        <h3>2.2 Saat kamu membeli ebook (pembeli)</h3>
        <ul>
            <li>Riwayat transaksi: ID order, buku yang dibeli, jumlah, waktu, metode pembayaran;</li>
            <li>Data pembayaran <em>tidak disimpan oleh kami</em> — diproses sepenuhnya oleh Midtrans. Kami hanya menerima konfirmasi sukses/gagal + ID transaksi;</li>
            <li>IP address dan user agent saat download (untuk log keamanan &amp; deteksi penyalahgunaan).</li>
        </ul>

        <h3>2.3 Saat kamu mendaftar sebagai penulis</h3>
        <p>Data tambahan yang lebih sensitif (digolongkan sebagai <strong>"Data Spesifik"</strong> menurut UU PDP):</p>
        <ul>
            <li>NIK (16 digit Nomor Induk Kependudukan);</li>
            <li>NPWP (jika ada);</li>
            <li>Foto KTP &amp; selfie + KTP (untuk verifikasi identitas);</li>
            <li>Data rekening bank: nama bank, nomor rekening, atas nama;</li>
            <li>Display name &amp; bio sebagai penulis;</li>
            <li>Konten Karya yang di-upload (file PDF, cover, metadata).</li>
        </ul>

        <h3>2.4 Otomatis dari interaksi dengan Platform</h3>
        <ul>
            <li>Log akses: IP, browser, OS, halaman yang dikunjungi, timestamp;</li>
            <li>Cookies — lihat §<a href="#6-cookies" class="text-brand-600">6. Cookies</a>;</li>
            <li>Data analitik agregat (kami pakai analytics, lihat §<a href="#7-pihak-ketiga" class="text-brand-600">7. Pihak Ketiga</a>).</li>
        </ul>

        <h2 id="3-tujuan">3. Tujuan Penggunaan Data</h2>
        <p>Kami memproses data pribadi kamu untuk tujuan-tujuan berikut:</p>
        <ul>
            <li>Menjalankan akun kamu (registrasi, login, autentikasi);</li>
            <li>Memproses transaksi pembelian &amp; payout royalti;</li>
            <li>Verifikasi identitas penulis dan kepatuhan pajak (PPh 23);</li>
            <li>Menyusun watermark personal di PDF yang kamu beli;</li>
            <li>Mengirim notifikasi terkait transaksi via email &amp; WhatsApp;</li>
            <li>Mencegah fraud, plagiat, pembajakan, dan penyalahgunaan;</li>
            <li>Memenuhi kewajiban hukum (kerjasama dengan aparat penegak hukum, kewajiban pajak, dll);</li>
            <li>Meningkatkan layanan (analytics agregat, tidak per individu);</li>
            <li>Komunikasi pemasaran (newsletter, promo) — <strong>hanya dengan persetujuan eksplisit kamu</strong>, dan bisa di-unsubscribe kapan saja.</li>
        </ul>

        <h2 id="4-dasar-hukum">4. Dasar Hukum Pemrosesan</h2>
        <p>Sesuai Pasal 20 UU PDP, kami memproses data kamu berdasarkan:</p>
        <ul>
            <li><strong>Persetujuan (consent)</strong> — kamu menyetujui kebijakan ini saat mendaftar;</li>
            <li><strong>Pelaksanaan kontrak</strong> — untuk menjalankan layanan yang kamu beli/gunakan;</li>
            <li><strong>Kewajiban hukum</strong> — pelaporan pajak, kerja sama aparat hukum;</li>
            <li><strong>Kepentingan sah</strong> — keamanan sistem, pencegahan fraud, pengembangan layanan;</li>
            <li><strong>Pelindungan kepentingan vital</strong> — misalnya kalau ada ancaman keamanan akun.</li>
        </ul>

        <h2 id="5-penyimpanan">5. Penyimpanan, Lokasi, &amp; Retensi Data</h2>
        <h3>5.1 Lokasi penyimpanan</h3>
        <ul>
            <li>Database utama: server Indonesia (IDCloudHost, Jakarta);</li>
            <li>File &amp; gambar: Cloudflare R2 (objek penyimpanan global dengan replikasi). Data tersimpan di region terdekat user;</li>
            <li>Backup: terenkripsi, disimpan di lokasi geografis berbeda untuk pemulihan bencana.</li>
        </ul>

        <h3>5.2 Retensi</h3>
        <ul>
            <li>Data akun aktif: disimpan selama akun aktif;</li>
            <li>Akun yang dihapus pengguna: dihapus permanen dalam 30 hari (kecuali catatan transaksi/pajak yang wajib disimpan 10 tahun sesuai UU Perpajakan);</li>
            <li>Data verifikasi penulis (KTP, selfie): dihapus 90 hari setelah akun penulis ditutup, kecuali untuk kepentingan investigasi hukum yang masih berjalan;</li>
            <li>Log keamanan &amp; audit: 1–3 tahun.</li>
        </ul>

        <h2 id="6-cookies">6. Cookies &amp; Tracking</h2>
        <p>Kami pakai cookies untuk:</p>
        <ul>
            <li><strong>Esensial</strong> (wajib): session login, CSRF token, pengaturan bahasa — tidak bisa dimatikan;</li>
            <li><strong>Analytics</strong> (opsional): mengukur traffic halaman secara agregat (tidak per individu);</li>
            <li><strong>Preferensi</strong> (opsional): menyimpan filter pencarian, mode tampilan.</li>
        </ul>
        <p>
            Kami <strong>tidak</strong> pakai cookies pelacakan iklan pihak ketiga seperti Facebook Pixel atau
            Google Ads tanpa persetujuan eksplisit kamu.
        </p>
        <p>
            Kamu bisa mengatur/mematikan cookies via browser, tapi beberapa fitur Platform mungkin tidak berfungsi
            (contoh: login akan terus reset).
        </p>

        <h2 id="7-pihak-ketiga">7. Berbagi Data dengan Pihak Ketiga</h2>
        <p>
            Kami <strong>tidak menjual data kamu</strong>. Kami berbagi data secara terbatas hanya dengan:
        </p>

        <h3>7.1 Penyedia layanan operasional (data processor)</h3>
        <ul>
            <li><strong>Midtrans</strong> (PT Midtrans, anak perusahaan Gojek/GoTo) — memproses pembayaran. Data dibagikan: nama, email, ID order, nominal. Lihat <a href="https://midtrans.com/privacy-policy" target="_blank" rel="noopener" class="text-brand-600">kebijakan privasi Midtrans</a>.</li>
            <li><strong>Cloudflare, Inc.</strong> — CDN, DNS, hosting file (R2). Data file kamu di-host di infrastruktur mereka. <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener" class="text-brand-600">Kebijakan privasi Cloudflare</a>.</li>
            <li><strong>Fonnte</strong> — gateway WhatsApp untuk OTP &amp; notifikasi. Data: nomor WA, isi pesan notifikasi (mis. kode OTP).</li>
            <li><strong>Google LLC</strong> — jika kamu login via Google: data akun Google sebatas yang kamu setujui di OAuth consent. Juga untuk Google Vision API (moderasi cover image).</li>
            <li><strong>OpenAI</strong> — moderasi teks Karya (sample max 5000 karakter per Karya). OpenAI berjanji tidak melatih model dengan data API.</li>
        </ul>

        <h3>7.2 Aparat penegak hukum &amp; kewajiban hukum</h3>
        <ul>
            <li>Kami akan mematuhi panggilan resmi/perintah pengadilan/surat permintaan resmi dari aparat penegak hukum Indonesia;</li>
            <li>Data pajak (royalti penulis, NPWP, PPh 23 yang dipotong) dilaporkan ke Direktorat Jenderal Pajak;</li>
            <li>Dalam kasus penyalahgunaan/kejahatan, data dapat diserahkan ke pihak yang berhak setelah verifikasi keabsahan permintaan.</li>
        </ul>

        <h3>7.3 Transfer data lintas batas</h3>
        <p>
            Beberapa penyedia (Cloudflare, Google, OpenAI) memproses data di server di luar Indonesia. Sesuai
            Pasal 56 UU PDP, transfer dilakukan dengan jaminan tingkat perlindungan yang setara, melalui kontrak
            standar atau ketentuan perlindungan data dengan masing-masing penyedia.
        </p>

        <h2 id="8-hak-kamu">8. Hak Subjek Data (Pasal 5–13 UU PDP)</h2>
        <p>Kamu punya hak berikut atas data pribadi kamu:</p>
        <ul>
            <li><strong>Hak untuk mendapat informasi</strong> tentang kejelasan identitas, dasar kepentingan hukum, tujuan permintaan dan penggunaan, akuntabilitas pihak yang meminta data;</li>
            <li><strong>Hak akses</strong> — meminta salinan data yang kami simpan tentang kamu;</li>
            <li><strong>Hak koreksi</strong> — meminta perbaikan data yang tidak akurat;</li>
            <li><strong>Hak menghapus / "right to be forgotten"</strong> — meminta penghapusan data (kecuali yang wajib disimpan menurut hukum);</li>
            <li><strong>Hak penghentian pemrosesan</strong>;</li>
            <li><strong>Hak menarik kembali persetujuan</strong>;</li>
            <li><strong>Hak mengajukan keberatan</strong> atas pemrosesan otomatis termasuk profiling;</li>
            <li><strong>Hak portabilitas</strong> — menerima data kamu dalam format yang umum digunakan (mis. JSON / CSV);</li>
            <li><strong>Hak menunda/membatasi</strong> pemrosesan;</li>
            <li><strong>Hak menggugat &amp; menerima ganti rugi</strong> atas pelanggaran data pribadi.</li>
        </ul>
        <p>
            Untuk menggunakan hak ini, kirim email ke
            <a href="mailto:privacy@bukudigi.com" class="text-brand-600 font-semibold">privacy@bukudigi.com</a>
            dengan subjek "Permintaan Hak Subjek Data" + identifikasi kamu (nama, email akun, no KTP untuk
            verifikasi). Kami merespons dalam 3 × 24 jam kerja sesuai Pasal 13 UU PDP.
        </p>

        <h2 id="9-keamanan">9. Keamanan Data</h2>
        <ul>
            <li>Password di-hash dengan algoritma bcrypt (industry standard);</li>
            <li>Koneksi HTTPS / TLS untuk semua traffic;</li>
            <li>Database backup harian, terenkripsi;</li>
            <li>Akses internal ke data sensitif (KTP, rekening) terbatas pada admin terverifikasi, dengan audit log;</li>
            <li>Watermark personal di PDF untuk mencegah pembocoran;</li>
            <li>Rate limiting &amp; anti-fraud detection;</li>
            <li>Pemberitahuan kebocoran data: jika terjadi insiden yang berdampak pada hak kamu, kami akan beritahu dalam <strong>3 × 24 jam</strong> sesuai Pasal 46 UU PDP.</li>
        </ul>
        <p>
            Meski kami berusaha sekuat tenaga, tidak ada sistem yang 100% aman. Kamu juga punya tanggung jawab
            menjaga password &amp; perangkat kamu.
        </p>

        <h2 id="10-anak">10. Anak Di Bawah Umur</h2>
        <p>
            Platform tidak ditujukan untuk anak di bawah 17 tahun. Kami <strong>tidak sengaja mengumpulkan data
            anak di bawah 17 tahun</strong>. Jika kamu orang tua/wali dan mengetahui anakmu mendaftar tanpa
            persetujuanmu, hubungi privacy@bukudigi.com — kami akan hapus akun dan datanya.
        </p>
        <p>
            Anak 17+ yang sudah punya KTP boleh menggunakan platform sebagai pembeli, dengan persetujuan orang tua/wali.
        </p>

        <h2 id="11-perubahan">11. Perubahan Kebijakan</h2>
        <p>
            Kebijakan ini dapat berubah seiring perkembangan layanan dan regulasi. Perubahan signifikan
            akan dinotifikasi via email dan banner di Platform minimal 14 hari sebelum efektif. Lihat tanggal
            "Terakhir diperbarui" di header untuk memastikan kamu membaca versi terkini.
        </p>

        <h2 id="12-kontak">12. Kontak Privacy Officer</h2>
        <p>
            Untuk pertanyaan, keberatan, atau penggunaan hak subjek data:
        </p>
        <ul>
            <li>Email: <a href="mailto:privacy@bukudigi.com" class="text-brand-600 font-semibold">privacy@bukudigi.com</a></li>
            <li>Subjek email: "Permintaan Hak Subjek Data" untuk percepatan respons</li>
        </ul>
        <p>
            Kamu juga berhak menyampaikan pengaduan kepada Lembaga Pelindungan Data Pribadi (begitu dibentuk
            sesuai UU PDP) atau Kementerian Komunikasi &amp; Informatika Republik Indonesia.
        </p>

    </div>
</section>
@endsection
