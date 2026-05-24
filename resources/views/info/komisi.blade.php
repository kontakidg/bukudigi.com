@extends('layouts.app')

@section('content')
@include('info._layout', [
    'title' => 'Komisi & Royalti',
    'lead' => 'Skema bagi hasil yang transparan untuk penulis bukudigi.com. Tidak ada potongan tersembunyi.',
])

<section class="mx-auto max-w-3xl px-4 py-12">
    <div class="prose prose-slate max-w-none">

        <h2>Pembagian sederhana</h2>
        <div class="not-prose my-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-brand-200 bg-brand-50 p-6 text-center">
                <p class="text-xs font-semibold uppercase text-brand-700">Penulis</p>
                <p class="mt-1 text-5xl font-bold text-brand-700">70%</p>
                <p class="mt-2 text-sm text-brand-900">Dari setiap pembelian</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <p class="text-xs font-semibold uppercase text-amber-700">Affiliate</p>
                <p class="mt-1 text-5xl font-bold text-amber-700">10%</p>
                <p class="mt-2 text-sm text-amber-900">Komisi referrer (kalau ada)</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center">
                <p class="text-xs font-semibold uppercase text-slate-600">Platform</p>
                <p class="mt-1 text-5xl font-bold text-slate-600">20%</p>
                <p class="mt-2 text-sm text-slate-700">Untuk operasional, marketing, support</p>
            </div>
        </div>
        <p class="text-sm text-slate-600">
            <em>Catatan:</em> kalau pembeli datang tanpa link affiliate, porsi 10% affiliate menjadi bonus untuk penulis (jadi penulis dapat 80%).
        </p>

        <h2>Contoh perhitungan</h2>
        <p>Buku kamu dijual seharga <strong>Rp 50.000</strong>, dibeli oleh seorang pembeli pakai QRIS. Begini alur uangnya:</p>

        <div class="not-prose my-4 overflow-hidden rounded-lg border border-slate-200">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="px-4 py-2">Harga buku (gross)</td>
                        <td class="px-4 py-2 text-right font-semibold">Rp 50.000</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-slate-600">– Midtrans fee (QRIS 0,7%)</td>
                        <td class="px-4 py-2 text-right text-slate-600">– Rp 350</td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td class="px-4 py-2 font-semibold">Net masuk ke kami</td>
                        <td class="px-4 py-2 text-right font-semibold">Rp 49.650</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-slate-600">Komisi platform 20% (dari gross Rp 50.000)</td>
                        <td class="px-4 py-2 text-right text-slate-600">Rp 10.000</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-amber-700">Komisi affiliate 10% (dari gross Rp 50.000)</td>
                        <td class="px-4 py-2 text-right text-amber-700">Rp 5.000</td>
                    </tr>
                    <tr class="bg-brand-50">
                        <td class="px-4 py-2 font-semibold text-brand-700">Bagian penulis (70% gross)</td>
                        <td class="px-4 py-2 text-right font-bold text-brand-700">Rp 35.000</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-sm text-slate-600">
            <strong>Catatan:</strong> Midtrans fee ditanggung platform dari porsi komisi 20% — bukan dipotong dari porsi kamu.
            Kalau pembelian <strong>tanpa affiliate</strong>, porsi 10% affiliate masuk ke penulis (jadi Rp 40.000 = 80% gross).
        </p>

        <h2>Yang dipotong dari saldo kamu saat payout</h2>
        <ol>
            <li>
                <strong>PPh Pasal 23</strong> sebagai pajak penghasilan atas royalti:
                <ul>
                    <li><strong>2%</strong> kalau kamu kasih NPWP saat daftar penulis</li>
                    <li><strong>4%</strong> kalau tanpa NPWP (sesuai UU 36/2008 Pasal 23 ayat 1a)</li>
                </ul>
                <p class="text-sm">Bukti potong (Bukti Potong PPh 23) akan diterbitkan elektronik dan dikirim ke email kamu setiap akhir tahun untuk lapor SPT pribadi.</p>
            </li>
            <li>
                <strong>Biaya transfer bank</strong> Rp 6.500 per payout, dibagi 50:50:
                <ul>
                    <li>Penulis: Rp 3.250</li>
                    <li>Platform: Rp 3.250</li>
                </ul>
            </li>
        </ol>

        <h2>Contoh perhitungan payout 1 bulan</h2>
        <p>Misalkan dalam bulan Mei buku kamu terjual 10x @ Rp 50.000 (semuanya via link affiliate):</p>

        <div class="not-prose my-4 overflow-hidden rounded-lg border border-slate-200">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="px-4 py-2">Total earning gross (10 × Rp 35.000)</td>
                        <td class="px-4 py-2 text-right">Rp 350.000</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-slate-600">– PPh 23 (2% jika NPWP)</td>
                        <td class="px-4 py-2 text-right text-slate-600">– Rp 7.000</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-slate-600">– Biaya transfer 50:50</td>
                        <td class="px-4 py-2 text-right text-slate-600">– Rp 3.250</td>
                    </tr>
                    <tr class="bg-brand-50">
                        <td class="px-4 py-2 font-semibold text-brand-700">Net transfer ke rekening (5 Juni)</td>
                        <td class="px-4 py-2 text-right font-bold text-brand-700">Rp 339.750</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-sm text-slate-600">Tanpa NPWP, PPh 23 = Rp 14.000, net = Rp 332.750. Kalau semua 10 transaksi tanpa affiliate, gross earning = Rp 400.000 (80% × 10 × 50rb).</p>

        <h2>Aturan payout</h2>
        <ul>
            <li><strong>Jadwal:</strong> tanggal 5 setiap bulan, untuk semua transaksi bulan sebelumnya yang sudah lewat masa cooling 7 hari sejak <em>paid_at</em>.</li>
            <li><strong>Cooling period:</strong> 7 hari sejak transaksi sukses — selama window ini, pembeli masih bisa minta refund kalau ada masalah teknis (lihat <a href="{{ route('info.syarat') }}#11-refund">§Refund</a>).</li>
            <li><strong>Threshold minimum:</strong> Rp 100.000. Kalau saldo available di bawah ini, akan <strong>carry-over</strong> ke bulan depan sampai mencapai threshold.</li>
            <li><strong>Cut-off:</strong> transaksi sukses sampai akhir bulan (jam 23:59 WIB tanggal terakhir bulan) ikut payout bulan berikutnya, dengan syarat sudah lewat cooling.</li>
            <li><strong>Verifikasi rekening:</strong> nama atas rekening wajib <strong>persis</strong> sesuai KTP. Beda nama = payout ditunda sampai update data.</li>
            <li><strong>Hari libur:</strong> kalau tanggal 5 jatuh di Sabtu/Minggu/libur nasional, payout di hari kerja berikutnya.</li>
        </ul>

        <h2>Refund &amp; saldo kamu</h2>
        <p>
            Kalau ada refund yang disetujui untuk buku kamu, saldo author terkait akan dipotong sesuai
            nominal earning yang sudah dialokasikan untuk transaksi tersebut. Kalau saldo sudah lewat
            cooling (sudah masuk available), jadi piutang yang dipotong dari payout berikutnya.
        </p>

        <h2>PPN — wajib pungut</h2>
        <p>
            Mulai 2025, perdagangan ebook digital tunduk pada PPN 12% (PMK 60/2022). PPN <strong>dipungut oleh platform</strong>
            dari pembeli (sudah <em>included</em> di harga jual), dan kami setor ke negara. Penulis tidak perlu mengurus PPN sendiri.
        </p>
        <p class="text-sm text-slate-600">
            <em>Detail teknis penetapan harga sudah/belum termasuk PPN akan diperjelas dalam dashboard penulis
            saat mode produksi aktif. Saat ini, harga yang kamu set adalah <strong>harga gross termasuk PPN</strong>
            — kami akan otomatis pisahkan saat pelaporan.</em>
        </p>

        <h2>Apa yang TIDAK kami potong</h2>
        <ul>
            <li>Biaya hosting / storage file PDF — gratis selama buku aktif</li>
            <li>Biaya marketing organik (SEO, kategori, search) — gratis</li>
            <li>Biaya watermarking PDF tiap pembelian — gratis (sudah include di komisi)</li>
            <li>Biaya generate preview (PDF + PNG gallery) — gratis</li>
            <li>Biaya support pembeli — gratis</li>
        </ul>

        <h2>Apa yang berubah setelah skala besar</h2>
        <p>
            Kami berkomitmen mempertahankan <strong>komisi platform 20% flat selama minimum 24 bulan</strong> sejak launch (porsi affiliate 10% terpisah dan hanya berlaku kalau pembelian via referral link).
            Setelah itu kalau ada perubahan, akan dinotifikasi minimal 60 hari sebelumnya — buku yang sudah
            terjual sebelum perubahan tetap pakai rate lama. Skema tier (komisi turun untuk penulis volume tinggi)
            sedang dipertimbangkan untuk masa depan.
        </p>

        <h2 class="mt-12">Siap mulai jualan?</h2>
        <div class="not-prose mt-4 rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-center text-white">
            <p class="text-brand-100">Daftar gratis, tidak ada biaya bulanan, tidak ada minimum sales.</p>
            @auth
                @if(auth()->user()->author)
                    <a href="{{ route('author.dashboard') }}" class="mt-3 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">📊 Dashboard Penulis →</a>
                @else
                    <a href="{{ route('author.register.show') }}" class="mt-3 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">✍️ Daftar Penulis →</a>
                @endif
            @else
                <a href="{{ route('register') }}" class="mt-3 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">Daftar Akun Dulu →</a>
            @endauth
        </div>
    </div>
</section>
@endsection
