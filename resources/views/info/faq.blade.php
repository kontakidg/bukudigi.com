@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Pertanyaan yang Sering Diajukan (FAQ)',
    'lead' => 'Jawaban cepat untuk pertanyaan paling umum dari pembeli dan penulis bukudigi.com.',
])

<section class="mx-auto max-w-3xl px-4 py-12" x-data="{ open: null }">

    <h2 class="text-2xl font-bold">Untuk Pembeli</h2>
    <div class="mt-4 space-y-3">
        @foreach([
            ['Apa itu bukudigi.com?', 'Marketplace ebook PDF & EPUB dari penulis Indonesia. Beli sekali, watermark personal, baca online di reader atau download permanen ke perangkat kamu (sampai limit 10× re-download dalam 30 hari).'],
            ['Format apa saja yang tersedia?', 'Setiap buku tersedia dalam format PDF. Banyak buku juga punya versi EPUB — bisa dibaca online di reader bukudigi.com (HP/laptop) atau di-download untuk app lain (Apple Books, Calibre, Kindle, dll). Buku yang punya EPUB ditandai badge "📖 EPUB" di katalog.'],
            ['Apa bedanya dengan Google Play Books / Gramedia Digital?', 'Pembayaran lokal (QRIS/VA, gak butuh kartu kredit), terbuka untuk penulis indie tanpa kontrak penerbit, watermark personal bukan DRM yang ribet, komisi adil untuk penulis lokal.'],
            ['Cara bayarnya gimana?', 'Klik Beli Sekarang → pilih metode bayar via Midtrans (QRIS, Virtual Account BCA/BNI/Mandiri/Permata, GoPay, OVO, DANA, ShopeePay, kartu kredit). Setelah bayar sukses, langsung redirect ke Perpustakaan Saya.'],
            ['Saya gak punya kartu kredit, bisa beli?', 'Bisa banget. Yang paling mudah pakai QRIS (semua mobile banking & e-wallet support) atau Virtual Account.'],
            ['File PDF-nya aman gak? Ada watermark apa?', 'PDF kamu punya watermark berisi nama, email, dan order code di footer setiap halaman + watermark diagonal samar "PREVIEW" tidak ada (cuma di preview gratis, bukan di file beli). Watermark ini supaya kalau ada yang membocorkan, terlacak ke akun aslinya.'],
            ['Bisa baca offline?', 'Bisa. Download sekali, file disimpan di device kamu (HP, laptop, tablet), bisa dibuka kapan saja tanpa internet.'],
            ['Berapa kali bisa download file yang sama?', 'Maks 10× dalam 30 hari sejak pembelian. Setelah expired, hubungi support kalau perlu reset.'],
            ['Bisa print? Bisa pinjamkan ke teman?', 'Print untuk konsumsi pribadi: BOLEH. Pinjamkan ke orang lain: tidak diperkenankan — watermark identitas kamu ada di file, kalau dibagikan masih bisa terlacak.'],
            ['Bisa minta refund kalau nggak suka isi bukunya?', 'Sayang, tidak. Refund hanya untuk alasan teknis (file rusak, halaman kosong, double charge). Pakai preview gratis 5 halaman sebelum beli untuk pastikan cocok.'],
            ['Kapan refund cair?', '3-14 hari kerja setelah disetujui. Dikembalikan via metode pembayaran asal, atau jadi saldo platform (atas pilihan kamu).'],
            ['Saya lupa password, gimana?', 'Klik "Lupa Password?" di halaman login → masukkan email → cek inbox untuk link reset.'],
            ['Bisa hapus akun pembeli saya?', 'Bisa, di Profile → Hapus Akun. Data dihapus permanen dalam 30 hari, kecuali catatan transaksi yang wajib disimpan untuk audit pajak.'],
        ] as $i => $qa)
            <div class="card overflow-hidden">
                <button @click="open === 'b{{$i}}' ? open = null : open = 'b{{$i}}'"
                        class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                    <span class="font-semibold text-slate-900">{{ $qa[0] }}</span>
                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === 'b{{$i}}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === 'b{{$i}}'" x-cloak x-transition class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                    {{ $qa[1] }}
                </div>
            </div>
        @endforeach
    </div>

    <h2 class="mt-12 text-2xl font-bold">Untuk Penulis</h2>
    <div class="mt-4 space-y-3">
        @foreach([
            ['Saya bukan penulis terkenal. Bisa daftar?', 'Bisa! bukudigi.com terbuka untuk semua penulis individu yang punya karya orisinal. Tidak ada syarat portofolio atau followers minimum.'],
            ['Biaya pendaftaran berapa?', 'Gratis. Tidak ada biaya bulanan, tidak ada minimum sales. Kami hanya potong komisi 20% saat ada pembelian.'],
            ['Kenapa harus kasih NIK & KTP?', 'Untuk verifikasi identitas (mencegah fraud, pembajakan, akun palsu) dan kewajiban pajak (PPh 23). Data disimpan terenkripsi, hanya admin verifikator yang akses.'],
            ['Saya gak punya NPWP. Tetap bisa?', 'Bisa. Tanpa NPWP, PPh 23 dipotong 4% (vs 2% dengan NPWP). Sangat dianjurkan punya NPWP — gratis daftar di kantor pajak terdekat atau online di pajak.go.id.'],
            ['Berapa lama verifikasi admin?', '1-2 hari kerja sejak submit. Kamu akan dapat WhatsApp notifikasi (kalau sudah verifikasi WA) saat sudah disetujui atau ditolak (dengan alasan).'],
            ['Bisa pakai pen name (nama samaran)?', 'Bisa. Display name di toko boleh beda dengan nama KTP. Tapi KTP &amp; rekening tetap atas nama asli untuk verifikasi internal.'],
            ['Buku saya akan dipromosikan?', 'Setiap buku live otomatis muncul di kategori, search, halaman home (kalau termasuk Terbaru/Populer). Marketing organik gratis. Kalau ada fitur "featured" di masa depan, akan diumumkan.'],
            ['Bisa upload buku berseri / multi-volume?', 'Bisa, masing-masing volume = 1 listing terpisah. Kasih nomor di judul (mis. "Belajar PHP — Volume 1: Sintaks Dasar") untuk jelas.'],
            ['Bisa hapus buku setelah upload?', 'Bisa archive (sembunyikan dari publik), tapi tidak bisa hapus permanen kalau sudah ada pembeli. Pembeli existing tetap bisa download buku yang sudah dibeli.'],
            ['Boleh saya menjual buku yang sama di platform lain (Tokopedia, Shopee, blog pribadi)?', 'Boleh — lisensi yang kami pegang non-eksklusif. Tapi ingat: tiap platform punya cara watermarking sendiri. Pastikan kamu tidak melanggar T&amp;C platform lain.'],
            ['Bagaimana kalau pembeli membocorkan PDF saya ke grup Telegram?', 'Watermark personal mempermudah lacak ke pembeli asli. Kalau ada bocoran terdeteksi, lapor ke support@bukudigi.com — kami suspend akun pelanggar dan, jika kerugian besar, bisa proses hukum.'],
            ['PPN-nya bagaimana?', 'PPN dipungut oleh platform dari pembeli (12% sesuai PMK 60/2022), kami setor ke negara. Penulis tidak perlu urus PPN. Detail di <a href="' . route('info.komisi') . '" class="text-brand-600 underline">halaman komisi</a>.'],
        ] as $i => $qa)
            <div class="card overflow-hidden">
                <button @click="open === 'p{{$i}}' ? open = null : open = 'p{{$i}}'"
                        class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                    <span class="font-semibold text-slate-900">{{ $qa[0] }}</span>
                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === 'p{{$i}}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === 'p{{$i}}'" x-cloak x-transition class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                    {!! $qa[1] !!}
                </div>
            </div>
        @endforeach
    </div>

    <h2 class="mt-12 text-2xl font-bold">Hak Cipta &amp; Legal</h2>
    <div class="mt-4 space-y-3">
        @foreach([
            ['Karya saya dibajak / dijual orang lain di bukudigi.com tanpa izin. Apa yang saya lakukan?', 'Kirim laporan lengkap ke <a href="mailto:abuse@bukudigi.com" class="text-brand-600 underline">abuse@bukudigi.com</a>. Sertakan: URL buku terlapor, bukti kepemilikan karya, identitas kamu. Kami review dalam 3 hari kerja dan menonaktifkan buku terlapor selama investigasi. Detail prosedur di <a href="' . route('info.syarat') . '#dmca" class="text-brand-600 underline">§Syarat & Ketentuan</a>.'],
            ['Apakah saya bisa pakai cover buku saya yang sudah pernah terbit di penerbit lain?', 'Tergantung kontrak kamu dengan penerbit. Banyak kontrak tradisional memberi hak eksklusif cover ke penerbit. Cek kontrak, atau pakai cover baru yang kamu kustom desain.'],
            ['Apakah AI-generated content (ChatGPT/Claude) boleh?', 'Boleh, tapi <strong>wajib disclosure</strong> di form upload (checkbox "Saya menggunakan AI"). Pembeli berhak tahu. Konten AI murni tanpa editing manusia tidak punya hak cipta menurut hukum Indonesia, tapi tetap boleh dijual sebagai produk digital.'],
            ['Bagaimana cara saya melindungi konten saya?', 'Watermark personal otomatis di file pembeli. Untuk klaim lebih kuat: daftarkan karya kamu ke <a href="https://hakcipta.dgip.go.id" target="_blank" class="text-brand-600 underline">DJKI</a> (biaya ~Rp 200rb/karya, hasilnya sertifikat HKI legal).'],
            ['Data pribadi saya disimpan di mana?', 'Database utama di server Indonesia (Jakarta). File di Cloudflare R2 dengan replikasi global. Detail lengkap di <a href="' . route('info.privasi') . '#5-penyimpanan" class="text-brand-600 underline">§Kebijakan Privasi</a>.'],
        ] as $i => $qa)
            <div class="card overflow-hidden">
                <button @click="open === 'l{{$i}}' ? open = null : open = 'l{{$i}}'"
                        class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                    <span class="font-semibold text-slate-900">{{ $qa[0] }}</span>
                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === 'l{{$i}}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === 'l{{$i}}'" x-cloak x-transition class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                    {!! $qa[1] !!}
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-12 rounded-xl bg-gradient-to-br from-brand-50 to-white p-6 text-center">
        <h2 class="text-xl font-bold">Pertanyaan kamu tidak ada di sini?</h2>
        <p class="mt-2 text-sm text-slate-700">Hubungi kami via email — biasanya kami balas dalam 1-2 hari kerja.</p>
        <a href="mailto:support@bukudigi.com" class="btn-primary mt-4 inline-block">📧 support@bukudigi.com</a>
    </div>
</section>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
