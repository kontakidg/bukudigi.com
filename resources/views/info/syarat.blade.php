@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Syarat & Ketentuan Layanan',
    'lead' => 'Aturan main penggunaan bukudigi.com. Dengan mendaftar, kamu menyetujui semua poin di bawah.',
    'updated' => '20 Mei 2026',
])

<section class="mx-auto max-w-3xl px-4 py-12">
    <div class="prose prose-slate max-w-none prose-headings:scroll-mt-20 prose-h2:text-xl prose-h2:font-bold prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2 prose-h2:mt-10">

        <h2 id="1-definisi">1. Definisi</h2>
        <ul>
            <li><strong>Platform / Layanan</strong> — situs web, aplikasi, API, dan seluruh sistem teknologi yang dioperasikan di bawah nama bukudigi.com.</li>
            <li><strong>Kami / Operator</strong> — penyelenggara bukudigi.com dan badan hukum yang menaungi (akan didaftarkan sebagai PSE lingkup privat di Kominfo).</li>
            <li><strong>Pengguna</strong> — siapa pun yang mengakses Platform, baik dengan akun maupun tidak.</li>
            <li><strong>Pembeli</strong> — Pengguna yang membeli ebook di Platform.</li>
            <li><strong>Penulis</strong> — Pengguna yang telah diverifikasi sebagai penulis dan menggunakan Platform untuk menjual karyanya.</li>
            <li><strong>Karya</strong> — ebook PDF berikut metadata (judul, deskripsi, cover, tag) yang di-upload oleh Penulis.</li>
            <li><strong>Transaksi</strong> — pembelian satu Karya oleh satu Pembeli.</li>
        </ul>

        <h2 id="2-penerimaan-syarat">2. Penerimaan Syarat</h2>
        <p>
            Dengan mendaftar akun, mengakses, atau menggunakan Platform, Pengguna menyatakan telah membaca,
            memahami, dan menyetujui seluruh isi Syarat &amp; Ketentuan ini, serta
            <a href="{{ route('info.privasi') }}" class="text-brand-600">Kebijakan Privasi</a>. Jika tidak setuju,
            Pengguna wajib berhenti menggunakan Platform.
        </p>
        <p>
            Kami berhak mengubah Syarat &amp; Ketentuan kapan saja. Perubahan signifikan akan dinotifikasi via email
            dan/atau banner di Platform minimal 14 hari sebelum berlaku. Penggunaan Platform setelah tanggal efektif
            perubahan berarti Pengguna menerima syarat baru.
        </p>

        <h2 id="3-eligibilitas">3. Kelayakan Pengguna</h2>
        <ul>
            <li>Pengguna minimal berusia <strong>17 tahun</strong> dan/atau telah memiliki KTP yang sah.</li>
            <li>Pengguna di bawah 17 tahun hanya boleh menggunakan Platform di bawah pengawasan orang tua/wali.</li>
            <li>Penulis wajib WNI atau penduduk tetap Indonesia dengan NIK aktif &amp; rekening bank Indonesia atas nama sendiri.</li>
            <li>Pengguna yang sebelumnya akunnya di-suspend secara permanen tidak diperkenankan mendaftar ulang.</li>
        </ul>

        <h2 id="4-akun">4. Akun Pengguna</h2>
        <ul>
            <li>Pengguna wajib memberikan data yang <strong>akurat, lengkap, dan terkini</strong> saat registrasi.</li>
            <li>Satu orang hanya boleh memiliki satu akun. Duplikasi akun akan disuspend.</li>
            <li>Pengguna bertanggung jawab penuh atas kerahasiaan password dan semua aktivitas dari akunnya.</li>
            <li>Pengguna wajib segera memberitahu kami jika akun diretas atau diakses pihak tidak berwenang.</li>
            <li>Kami tidak bertanggung jawab atas kerugian akibat kelalaian Pengguna menjaga akunnya sendiri.</li>
        </ul>

        <h2 id="5-pembeli">5. Hak &amp; Kewajiban Pembeli</h2>
        <h3>5.1 Hak Pembeli</h3>
        <ul>
            <li>Akses preview gratis 5 halaman pertama setiap buku sebelum membeli.</li>
            <li>Setelah membayar, mendapat link download PDF ber-watermark personal yang berlaku 30 hari, maks 5x re-download.</li>
            <li>Membaca, menyimpan, dan mencetak PDF yang telah dibeli <em>untuk konsumsi pribadi</em>.</li>
            <li>Mengajukan refund dalam 7 hari untuk alasan teknis (file rusak / tidak bisa dibuka).</li>
            <li>Mengajukan keluhan melalui jalur dukungan resmi.</li>
        </ul>
        <h3>5.2 Kewajiban Pembeli</h3>
        <ul>
            <li><strong>Dilarang keras</strong> mendistribusikan, meng-upload, membagikan, menjual kembali, atau menyiarkan ulang PDF yang dibeli — baik sebagian maupun keseluruhan, baik dengan/tanpa watermark dihapus.</li>
            <li>Dilarang menghapus, memodifikasi, mengaburkan, atau mencoba mengaburkan watermark personal.</li>
            <li>Dilarang melakukan reverse-engineering, screenscraping otomatis, atau menyalahgunakan API Platform.</li>
            <li>Dilarang membuat akun palsu / pakai identitas orang lain saat membeli.</li>
            <li>Bertanggung jawab penuh atas keamanan perangkat tempat menyimpan PDF — bocoran dari perangkat Pembeli adalah tanggung jawab Pembeli.</li>
        </ul>
        <p>
            <strong>Konsekuensi pelanggaran:</strong> kami berhak menonaktifkan akun, melaporkan kepada penegak hukum,
            dan menuntut ganti rugi atas kerugian yang ditimbulkan, termasuk kerugian Penulis akibat penyebaran karya
            secara ilegal.
        </p>

        <h2 id="6-penulis">6. Hak &amp; Kewajiban Penulis</h2>
        <h3>6.1 Hak Penulis</h3>
        <ul>
            <li>Menentukan harga Karya dalam rentang Rp 15.000 – Rp 500.000.</li>
            <li>Menerima 70% dari harga gross setiap penjualan (affiliate 10%, platform 20%, lihat <a href="{{ route('info.komisi') }}" class="text-brand-600">halaman komisi</a>).</li>
            <li>Menerima royalti via transfer bank setiap tanggal 5 bulan berikutnya (dengan threshold &amp; cooling).</li>
            <li>Mengedit, archive, atau menarik Karya kapan saja (Pembeli existing tetap dapat mengakses download).</li>
            <li>Melihat statistik penjualan real-time di dashboard Penulis.</li>
        </ul>
        <h3>6.2 Pernyataan &amp; Jaminan Penulis (sangat penting)</h3>
        <p>Dengan submit Karya, Penulis menyatakan dan menjamin bahwa:</p>
        <ol>
            <li>Penulis adalah <strong>pemilik sah seluruh hak cipta</strong> atas Karya tersebut, atau telah memperoleh seluruh izin/lisensi yang diperlukan dari pemilik hak;</li>
            <li>Karya <strong>tidak melanggar hak cipta, merek dagang, paten, hak privasi, atau hak hukum lain pihak ketiga mana pun</strong>;</li>
            <li>Karya bukan hasil plagiat, pembajakan, atau salinan tidak sah dari karya lain;</li>
            <li>Karya tidak mengandung konten yang dilarang (lihat §7);</li>
            <li>Semua data yang diberikan (identitas, rekening, NPWP, dll) adalah benar dan akurat;</li>
            <li>Jika Karya dibuat dengan bantuan AI (ChatGPT, Claude, dll), Penulis menyatakannya secara jujur di kolom "Disclosure AI" saat upload.</li>
        </ol>
        <p>
            Penulis <strong>memberikan ganti rugi penuh (indemnify)</strong> kepada kami atas seluruh klaim, gugatan,
            kerugian, dan biaya hukum yang timbul akibat pelanggaran pernyataan di atas. Termasuk biaya kuasa hukum
            kami untuk membela atau menyelesaikan klaim pihak ketiga yang muncul karena Karya yang di-upload Penulis.
        </p>

        <h2 id="6-3-lisensi">6.3 Lisensi yang Diberikan Penulis kepada Platform</h2>
        <p>
            Dengan submit Karya, Penulis memberi kami <strong>lisensi non-eksklusif, bebas royalti, tidak dapat dialihkan</strong>
            (kecuali kepada penyedia infrastruktur kami seperti CDN, storage), <strong>terbatas pada kegunaan operasional Platform</strong>, yaitu untuk:
        </p>
        <ul>
            <li>Menyimpan, mereproduksi, dan menampilkan metadata Karya (judul, cover, deskripsi, preview 5 halaman) di Platform dan kanal marketing kami;</li>
            <li>Memproses pembayaran dan menghasilkan PDF ber-watermark untuk Pembeli sah;</li>
            <li>Mengarsipkan Karya untuk keperluan backup dan audit;</li>
            <li>Menampilkan Karya dalam hasil pencarian dan rekomendasi.</li>
        </ul>
        <p>
            Lisensi ini berlaku selama Karya aktif di Platform, dan otomatis berakhir saat Karya di-archive
            (kecuali untuk Pembeli yang telah membeli sebelumnya — mereka tetap berhak mengakses file mereka selama masa berlaku link).
        </p>
        <p>
            <strong>Hak cipta Karya tetap 100% milik Penulis.</strong> Kami tidak mengklaim kepemilikan apa pun atas Karya itu sendiri.
        </p>

        <h2 id="7-konten-dilarang">7. Konten yang Dilarang</h2>
        <p>Karya yang di-upload <strong>dilarang</strong> mengandung, mempromosikan, atau memfasilitasi:</p>
        <ul>
            <li>Pornografi, eksploitasi seksual, atau konten cabul (termasuk yang melibatkan anak — pidana berat sesuai UU PKS &amp; UU Pornografi);</li>
            <li>Ujaran kebencian, diskriminasi SARA, hasutan kekerasan;</li>
            <li>Konten yang memprovokasi separatisme atau menentang ideologi Pancasila;</li>
            <li>Plagiat / pembajakan dari karya pihak lain;</li>
            <li>Petunjuk pembuatan senjata, bahan peledak, narkoba;</li>
            <li>Informasi yang melanggar privasi orang lain (data pribadi tanpa izin);</li>
            <li>Penipuan, skema piramida, multi-level marketing terselubung;</li>
            <li>Praktik kesehatan/medis yang tidak berdasar yang bisa membahayakan;</li>
            <li>Material yang melanggar UU ITE, UU Hak Cipta, UU Perlindungan Anak, UU Pornografi, atau peraturan lain yang berlaku di Indonesia.</li>
        </ul>
        <p>
            Karya yang melanggar akan <strong>langsung diturunkan</strong>, dan tergantung tingkat pelanggaran:
            akun dapat di-suspend permanen, royalti yang belum dicairkan dibatalkan, dan dilaporkan ke aparat
            penegak hukum.
        </p>

        <h2 id="8-moderasi">8. Proses Moderasi</h2>
        <ul>
            <li>Setiap Karya yang di-upload masuk antrian <strong>review manual oleh admin</strong> sebelum dipublikasi (target 1–2 hari kerja).</li>
            <li>Kami berhak menolak Karya dengan/tanpa alasan, atau meminta revisi.</li>
            <li>Untuk Karya yang di-edit setelah live: perubahan substansial (file PDF baru, judul, deskripsi) akan kembali masuk review.</li>
            <li>Kami juga menggunakan automated tool (AI) untuk pre-screening cover dan konten — tapi keputusan final tetap di admin manusia.</li>
        </ul>

        <h2 id="dmca" class="scroll-mt-20">9. Pelaporan Pelanggaran Hak Cipta (Notice &amp; Takedown)</h2>
        <p>
            Berdasarkan UU 28/2014 tentang Hak Cipta dan praktik internasional <em>notice &amp; takedown</em>,
            pemilik hak cipta yang merasa karyanya dijual di bukudigi.com tanpa izin dapat mengajukan laporan dengan menyertakan:
        </p>
        <ol>
            <li>Identifikasi karya orisinal yang dimiliki (judul, deskripsi, link kalau ada);</li>
            <li>URL spesifik Karya di Platform yang dilaporkan;</li>
            <li>Bukti kepemilikan hak (sertifikat HKI, naskah asli ber-timestamp, kontrak, ISBN, dll);</li>
            <li>Identitas pelapor &amp; kontak balik (email, telepon, alamat);</li>
            <li>Pernyataan dengan itikad baik bahwa penggunaan karya tersebut di Platform tidak diotorisasi oleh pemilik hak;</li>
            <li>Pernyataan bahwa informasi dalam laporan adalah akurat, dan pelapor adalah pemilik hak atau diberi kuasa untuk bertindak atas nama pemilik;</li>
            <li>Tanda tangan (fisik atau elektronik).</li>
        </ol>
        <p>
            Kirim laporan ke <a href="mailto:abuse@bukudigi.com" class="text-brand-600 font-semibold">abuse@bukudigi.com</a>.
            Kami akan:
        </p>
        <ul>
            <li>Konfirmasi penerimaan dalam 1 hari kerja;</li>
            <li>Menonaktifkan Karya terlapor selama proses verifikasi (paling lambat 3 hari kerja sejak laporan diterima);</li>
            <li>Memberi tahu Penulis yang dilaporkan untuk memberi tanggapan (counter-notice);</li>
            <li>Memutuskan tindak lanjut berdasarkan bukti dari kedua pihak.</li>
        </ul>
        <p>
            <strong>Laporan palsu / fitnah</strong> dapat menimbulkan konsekuensi hukum bagi pelapor sesuai
            Pasal 27 ayat (3) UU ITE.
        </p>

        <h2 id="10-pembayaran">10. Pembayaran &amp; Royalti</h2>
        <ul>
            <li>Pembayaran diproses oleh penyedia pihak ketiga (Midtrans). Pembeli tunduk juga pada Syarat Layanan Midtrans saat bertransaksi.</li>
            <li>Pembagian komisi: <strong>Penulis 70%, Affiliate 10%, Platform 20%</strong> dari harga gross.</li>
            <li>Biaya transfer bank saat payout: <strong>50:50</strong> antara Penulis &amp; Platform (estimasi Rp 6.500 → ditanggung Rp 3.250 masing-masing).</li>
            <li><strong>PPh 23</strong> sesuai Pasal 23 UU PPh: 2% (dengan NPWP) atau 4% (tanpa NPWP) dari penghasilan bruto royalti, dipotong otomatis oleh Platform sebagai wajib pungut.</li>
            <li><strong>PPN</strong> sesuai PMK 60/2022 untuk perdagangan digital: dipungut oleh Platform dari harga jual ke Pembeli akhir, disetor ke negara.</li>
            <li>Payout royalti: <strong>tanggal 5 bulan berikutnya</strong>, untuk transaksi yang sudah lewat masa cooling 7 hari, dengan threshold minimum Rp 100.000. Saldo di bawah threshold carry-over.</li>
            <li>Platform berhak menahan payout dalam kasus dugaan fraud, sengketa, atau kewajiban pajak yang belum terselesaikan.</li>
        </ul>

        <h2 id="11-refund">11. Refund</h2>
        <p>Pembeli berhak refund dalam <strong>7 hari kalender</strong> setelah pembayaran, <strong>hanya untuk alasan teknis</strong>:</p>
        <ul>
            <li>File PDF rusak / tidak dapat dibuka di reader standar;</li>
            <li>Halaman PDF kosong, ter-corrupt, atau tidak sesuai deskripsi secara teknis (bukan keluhan subjektif tentang isi);</li>
            <li>Pembelian ganda (double charge) — wajib lapor dalam 24 jam.</li>
        </ul>
        <p>
            <strong>Bukan alasan valid untuk refund</strong>: tidak suka isi, sudah baca lalu menyesal, gagal paham,
            harga dianggap mahal, atau alasan subjektif lainnya. Pembeli dianjurkan pakai fitur preview 5 halaman gratis
            sebelum membeli.
        </p>
        <p>
            Refund yang disetujui akan dikembalikan via metode pembayaran asal dalam 3–14 hari kerja, atau sebagai
            kredit saldo (atas pilihan Pembeli). Saldo Penulis terkait akan dipotong sesuai nominal refund (kalau
            sudah masuk available balance, jadi piutang yang dipotong dari payout berikutnya).
        </p>

        <h2 id="12-suspensi">12. Penghentian &amp; Suspensi Akun</h2>
        <p>Kami berhak menonaktifkan atau menutup akun, dengan/tanpa pemberitahuan sebelumnya, jika:</p>
        <ul>
            <li>Pengguna melanggar Syarat &amp; Ketentuan ini;</li>
            <li>Data registrasi terbukti palsu atau menyesatkan;</li>
            <li>Ada indikasi pembajakan, plagiat, fraud, atau aktivitas ilegal;</li>
            <li>Konten Karya melanggar §7;</li>
            <li>Ada permintaan resmi dari aparat penegak hukum atau putusan pengadilan;</li>
            <li>Pengguna tidak aktif lebih dari 24 bulan (akun pembeli/penulis biasa).</li>
        </ul>
        <p>
            Penulis yang akunnya di-suspend: royalti yang sudah masuk available balance tetap dibayarkan
            (kecuali ada putusan hukum sebaliknya); royalti pending dari Karya yang ditarik karena pelanggaran:
            ditahan / dialokasikan untuk ganti rugi.
        </p>
        <p>
            Pembeli yang akunnya di-suspend tetap dapat mengakses Karya yang telah dibelinya selama masa berlaku link,
            kecuali pelanggaran terkait penyebaran karya secara ilegal.
        </p>

        <h2 id="13-konten-pengguna">13. Konten Buatan Pengguna (UGC)</h2>
        <p>
            Saat Pengguna mengirim feedback, review (fase 2), komentar, atau komunikasi lain ke Platform,
            Pengguna memberi kami lisensi non-eksklusif untuk menampilkan, memodifikasi (untuk moderasi), dan
            menggunakan konten tersebut sesuai operasional Platform. Kami berhak menghapus konten yang melanggar
            §7 atau tidak relevan tanpa pemberitahuan.
        </p>

        <h2 id="14-pihak-ketiga">14. Layanan &amp; Tautan Pihak Ketiga</h2>
        <p>
            Platform menggunakan layanan pihak ketiga termasuk Midtrans (pembayaran), Cloudflare (CDN &amp; storage),
            Fonnte (WhatsApp), Google (OAuth), dan layanan AI moderasi. Penggunaan layanan tersebut tunduk pada
            ketentuan masing-masing penyedia. Kami tidak bertanggung jawab atas tindakan/pelanggaran/kelalaian
            pihak ketiga.
        </p>

        <h2 id="15-disclaimer">15. Penolakan Jaminan (Disclaimer)</h2>
        <p>
            Platform disediakan "<em>as-is</em>" dan "<em>as-available</em>". Sejauh diizinkan oleh hukum, kami tidak memberi
            jaminan apa pun, baik tersurat maupun tersirat, termasuk jaminan kelayakan untuk tujuan tertentu,
            tidak adanya gangguan, atau ketepatan informasi. Kami tidak menjamin Platform akan bebas dari bug,
            downtime, atau gangguan keamanan, meski berusaha sekuat tenaga.
        </p>
        <p>
            Kualitas konten Karya adalah tanggung jawab Penulis. Kami tidak memverifikasi keakuratan, kebenaran,
            atau kelengkapan isi Karya — Pembeli wajib menilai sendiri sebelum membeli (gunakan preview gratis).
        </p>

        <h2 id="16-pembatasan-tanggung-jawab">16. Pembatasan Tanggung Jawab</h2>
        <p>Sejauh diizinkan hukum yang berlaku:</p>
        <ul>
            <li>Kami tidak bertanggung jawab atas kerugian tidak langsung, insidental, konsekuensial, atau ganti rugi punitif (kehilangan keuntungan, kehilangan data, dll);</li>
            <li>Total tanggung jawab kumulatif kami kepada satu Pengguna untuk klaim apa pun dalam 12 bulan terakhir <strong>tidak melebihi total transaksi yang Pengguna bayarkan kepada kami</strong> dalam periode tersebut, atau Rp 1.000.000 (mana yang lebih besar);</li>
            <li>Pembatasan ini berlaku untuk klaim apa pun (kontrak, perbuatan melawan hukum, kelalaian, atau lainnya), kecuali untuk: kewajiban yang tidak dapat dikecualikan menurut UU Perlindungan Konsumen.</li>
        </ul>

        <h2 id="17-force-majeure">17. Keadaan Memaksa (Force Majeure)</h2>
        <p>
            Kami tidak bertanggung jawab atas keterlambatan atau kegagalan layanan akibat keadaan di luar
            kendali wajar kami, termasuk bencana alam, perang, kerusuhan, pandemi, kegagalan listrik/internet
            skala luas, serangan siber dengan dampak besar, perintah pemerintah, atau gangguan pada penyedia
            pihak ketiga yang kami gunakan.
        </p>

        <h2 id="18-ip-platform">18. Hak Kekayaan Intelektual Platform</h2>
        <p>
            Logo, nama "bukudigi" / "bukudigi.com", desain antarmuka, kode sumber Platform, ilustrasi/grafis
            yang kami buat, adalah <strong>milik kami</strong> dan dilindungi UU Hak Cipta &amp; UU Merek. Pengguna
            tidak diperkenankan menggunakan, mereproduksi, atau membuat karya turunan tanpa izin tertulis.
        </p>
        <p>
            Penggunaan nama/logo bukudigi.com untuk testimonial atau menyebut nama Platform (mis. "tersedia di
            bukudigi.com") diperbolehkan secara wajar (fair use) tanpa izin, sepanjang tidak menyesatkan.
        </p>

        <h2 id="19-hukum">19. Hukum yang Berlaku &amp; Penyelesaian Sengketa</h2>
        <ul>
            <li>Syarat &amp; Ketentuan ini diatur oleh dan ditafsirkan menurut hukum Republik Indonesia.</li>
            <li>Setiap sengketa diselesaikan terlebih dahulu secara <strong>musyawarah mufakat</strong> dalam 30 hari kerja sejak salah satu pihak menyampaikan keberatan tertulis.</li>
            <li>Jika tidak tercapai mufakat, sengketa diselesaikan di <strong>Pengadilan Negeri Jakarta Selatan</strong> sebagai forum eksklusif yang dipilih para pihak.</li>
            <li>Pengguna yang merupakan konsumen tidak melepas hak penyelesaian sengketa melalui Badan Penyelesaian Sengketa Konsumen (BPSK) atau jalur perlindungan konsumen lain yang dijamin UU.</li>
        </ul>

        <h2 id="20-kontak">20. Kontak Resmi</h2>
        <ul>
            <li>Umum: <a href="mailto:hello@bukudigi.com" class="text-brand-600 font-semibold">hello@bukudigi.com</a></li>
            <li>Hak cipta &amp; legal: <a href="mailto:abuse@bukudigi.com" class="text-brand-600 font-semibold">abuse@bukudigi.com</a></li>
            <li>Privasi data: <a href="mailto:privacy@bukudigi.com" class="text-brand-600 font-semibold">privacy@bukudigi.com</a></li>
            <li>Korespondensi tertulis: alamat resmi akan dipublikasikan setelah PSE Kominfo aktif.</li>
        </ul>

    </div>
</section>
@endsection
