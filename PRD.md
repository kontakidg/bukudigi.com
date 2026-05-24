# PRD — bukudigi.com

**Versi:** v0.1 (draft awal, hasil konsolidasi konsep + tech stack)
**Tanggal:** 2026-05-20
**Status:** DRAFT — iterasi terus dilakukan, update file ini setiap ada keputusan baru.
**Source of truth:** dokumen ini. Kalau ada beda dengan memori/chat, file ini menang.

---

## 1. Ringkasan Produk

**bukudigi.com** adalah marketplace ebook PDF untuk pasar Indonesia. Pembeli bayar via QRIS/VA → instan download PDF ber-watermark personal. Author individu (dosen, content creator, novelis indie, UMKM) bisa self-publish dengan moderasi AI + admin. Royalti dibayar bulanan ke rekening author.

### Pitch satu kalimat
> "Tempat jualan ebook digital lokal — bayar pakai QRIS, watermark di nama pembeli, royalti masuk rekening tanggal 5 tiap bulan."

### Diferensiasi
- vs **Google Play Books**: pembayaran lokal QRIS/VA (bukan kartu kredit), komisi lebih kecil, bahasa Indonesia full
- vs **Gramedia Digital**: terbuka untuk author indie, tanpa kontrak penerbit
- vs **Tokopedia/Shopee ebook**: kurasi & moderasi, visibility untuk niche edukasi, watermark personal bukan DRM
- vs **Telegram bajakan**: legal, author dapat royalti, pembeli dapat support resmi

---

## 2. Target User

### Pembeli (B2C)
- Mahasiswa S1/S2 — beli diktat/buku referensi
- Profesional muda 22–35 — beli ebook bisnis, programming, self-help
- Hobi self-learning — beli tutorial spesifik (Excel, design, masak)
- Persona umum: sering beli ebook bajakan di Telegram karena male/ribet pakai Google Play

### Penjual (B2B-individu)
- Dosen/guru jual diktat & modul kuliah
- Content creator jual ebook tutorial spesifik
- Novelis & penulis indie
- UMKM jual ebook resep, panduan bisnis, modul training

---

## 3. Tech Stack (mengikuti pola tarikgas.com)

| Layer | Pilihan | Catatan |
|---|---|---|
| Framework | Laravel 11 | Sama persis dengan tarikgas |
| Frontend | Blade + Livewire 3 + Tailwind CSS | SSR cepat, SEO friendly |
| Admin panel | Filament v3 | Hemat waktu CRUD admin |
| Database | MariaDB / MySQL | utf8mb4_unicode_ci |
| PHP | 8.2 lokal (XAMPP) / 8.3 prod (aaPanel) | |
| Web server | nginx + PHP-FPM | Config sama dengan tarikgas (lihat docs/DEPLOY.md tarikgas) |
| Hosting | VPS IDCloudHost Ubuntu 22.04 + aaPanel | Latency Indonesia |
| Storage publik | Cloudflare R2 (10GB free tier) | `cdn.bukudigi.com` untuk cover & preview |
| Storage privat (PDF asli) | R2 bucket terpisah, signed URL only | `bukudigi-private` — tidak public access |
| CDN/DNS/SSL | Cloudflare Free | Brotli + cache static aggressive |
| WhatsApp/OTP | Fonnte | Re-use akun tarikgas |
| Payment | Midtrans Snap | QRIS, VA BCA/Mandiri/BNI, GoPay, OVO, DANA, ShopeePay |
| Auth sosial | Google OAuth (utama), Facebook (opsional fase 2) | |
| Queue worker | Supervisor + Laravel Queue | WAJIB untuk watermarking PDF |
| Email | Mailgun / SMTP gratis (Brevo 300/hari) | Untuk receipt & notif |
| Search | MySQL LIKE/fulltext MVP, Meilisearch fase 2 | |
| Backup | mysqldump harian + upload R2 (bucket `bukudigi-backups`) | Cron via scheduler |
| Deploy | git push → VPS git pull → `bash deploy.sh` | Pola identik tarikgas |
| Domain dev | `bukudigi.test` via XAMPP virtual host | |

### Library khusus ebook (di luar stack tarikgas)
- **`setasign/fpdi`** + **`tecnickcom/tcpdf`** — inject watermark teks ke setiap halaman PDF saat checkout
- **`smalot/pdfparser`** — extract text untuk preview & search indexing
- **`spatie/pdf-to-image`** (atau imagick) — generate preview cover halaman 1 + preview 3-5 halaman
- **`intervention/image`** — resize cover ke variant (thumbnail 300x400, hero 800x1067, og 1200x630)

### AI / Moderasi
- **Google Vision SafeSearch API** — cek cover image (adult, violence, racy)
- **OpenAI Moderation API** (gratis) — cek teks metadata + sample halaman PDF
- Threshold: auto-reject jika `adult > LIKELY` atau `violence > LIKELY`; auto-flag (manual review) jika `POSSIBLE`
- Fallback: kalau API down, semua submission masuk antrian manual

---

## 4. Arsitektur Domain & Storage

```
bukudigi.com              → app Laravel (Livewire SPA-ish)
cdn.bukudigi.com          → R2 bucket public: cover, preview-pages, marketing asset
admin.bukudigi.com        → opsional subdomain, atau /admin via Filament
bukudigi.test             → local dev (XAMPP vhost)
```

### Pemisahan bucket R2
- `bukudigi-public` — cover, preview, asset marketing → public access via `cdn.bukudigi.com`
- `bukudigi-private` — PDF asli (master + watermarked per-user) → **NO public access**, akses via signed URL 24 jam
- `bukudigi-backups` — daily mysqldump, retention 30 hari

### Flow file PDF
```
1. Author upload PDF → bukudigi-private/originals/{book_id}/master.pdf
2. Background job: ekstrak preview (5 halaman) → bukudigi-public/previews/{book_id}/preview.pdf
3. Author submit moderasi → status = pending_review
4. Admin approve → status = active, muncul di toko
5. Pembeli checkout → background job watermark:
   - copy master.pdf → bukudigi-private/watermarked/{order_id}.pdf
   - inject "Dibeli oleh: {nama} – {email} – {order_code}" di tiap halaman (footer semi-transparent)
6. Email + WA ke pembeli: link download (signed URL, expiry 24 jam, max 5 re-download dalam 30 hari)
```

---

## 5. Fitur MVP (LOCK — tidak nambah tanpa diskusi)

### Pembeli
- [ ] Daftar/login: email+password, Google OAuth, WhatsApp OTP via Fonnte
- [ ] Browse buku: grid cover, filter kategori + harga + rating (rating diisi fase 2)
- [ ] Search MySQL fulltext judul + author + tag
- [ ] Halaman buku: cover, deskripsi, daftar isi, **preview 3-5 halaman** (embed PDF viewer or image), info author
- [ ] Checkout: 1 buku per transaksi (no cart MVP), pilih metode pembayaran via Midtrans Snap
- [ ] Setelah bayar: redirect ke "Perpustakaan saya" + email + WA dengan link download
- [ ] Perpustakaan: list buku yang sudah dibeli, re-download (max 5x dalam 30 hari per buku)
- [ ] Riwayat transaksi
- [ ] Profile: ubah nama, password, foto, nomor WA

### Author
- [ ] Daftar author (upgrade dari akun biasa) → isi data:
  - Nama lengkap, NIK, NPWP (opsional, mempengaruhi PPh 23 2% vs 4%)
  - Rekening bank (nama bank, no rekening, atas nama)
  - Foto KTP + selfie (verifikasi manual admin)
- [ ] Verifikasi WA via Fonnte (wajib)
- [ ] Dashboard author:
  - Saldo tersedia, saldo menahan (cooling 7 hari)
  - Penjualan hari ini / minggu / bulan
  - Daftar buku (status: draft / pending / active / rejected / archived)
- [ ] Upload buku baru:
  - Upload PDF (max 50 MB MVP), upload cover (JPG/PNG, max 5 MB)
  - Judul, deskripsi, kategori (single), tag (multi), harga
  - **Wajib centang:** "Saya konfirmasi konten asli/saya berhak menjual" + "Disclosure AI: Ya/Tidak"
- [ ] Edit buku (sebelum approve) / archive buku (setelah approve)
- [ ] Laporan penjualan (export CSV)
- [ ] Request payout manual (kalau sudah lewat tanggal 5 dan belum auto-process)

### Admin (Filament)
- [ ] Antrian moderasi: list buku pending, sort by submitted_at
  - Preview cover, baca PDF inline (iframe), score AI moderasi
  - Approve / Reject (wajib isi alasan kalau reject → otomatis WA ke author)
- [ ] Kelola user, author, buku, transaksi, kategori
- [ ] Halaman payout:
  - List author yang `saldo >= 100.000` dan tanggal sekarang >= 5
  - Tombol "Generate batch CSV" → file CSV format upload bank → admin transfer manual → klik "Tandai selesai"
- [ ] Laporan: penjualan harian/bulanan, komisi platform, top buku, top author
- [ ] Settings: konfigurasi komisi, threshold payout, hari cooling, etc.

---

## 6. Keputusan Bisnis (LOCK)

| Topik | Keputusan | Catatan |
|---|---|---|
| Komisi platform | **20% flat** | Tanpa affiliate: author 80%. Dengan affiliate: platform tetap 20%, affiliate 10% (dipotong dari net), author 70%. |
| Komisi affiliate | **10% dari net** | Dibayar dari porsi gabungan author+platform → split final 70/10/20 jika ada affiliate. Cookie 30 hari, cooling 7 hari, threshold Rp 100.000 ikut jadwal payout author tgl 5. Approval admin wajib. |
| Harga minimum | **Rp 15.000** | |
| Harga maksimum | **Rp 500.000** | Buku premium butuh approval admin |
| Mata uang | **IDR only** | |
| Format file | **PDF only MVP** | EPUB fase 2 |
| Pembeli scope | **Individu only** | Tidak ada akun korporat MVP |
| Author scope | **Individu only** | Tidak ada penerbit/agency MVP |
| Cart | **1 buku per transaksi** | Cart multi-item fase 2 |
| Rating/Review | **Tidak ada MVP** | Fase 2 |
| Payout schedule | **Tanggal 5 tiap bulan** | Semua transaksi yang sudah lewat cooling |
| Cooling period | **7 hari** sejak transaksi success | Antifraud + window refund |
| Threshold payout | **Rp 100.000** | Di bawah threshold → carry-over bulan depan |
| Biaya transfer bank | **50:50** author–platform | Asumsi Rp 6.500 → author Rp 3.250 |
| PPh 23 | **2%** dengan NPWP, **4%** tanpa NPWP | Dipotong otomatis dari payout |
| Refund | **7 hari** sejak transaksi | HANYA untuk PDF rusak/tidak bisa dibuka; bukan "buku jelek" |
| Disclosure AI | Checkbox Ya/Tidak + opsional list platform (ChatGPT/Claude/dll) | Tidak ada field "seberapa besar bantuan AI" |
| Watermark | Teks: "Dibeli oleh: {nama} – {email} – {order_code}" footer tiap halaman, semi-transparent abu | Tidak ada DRM lock |
| Payout method MVP | **Manual transfer admin** | Auto-disbursement via Iris di v2 saat author aktif > 50/siklus |

---

## 7. Skema Database (high level — detail saat coding)

```
users (id, name, email, password, phone, role[user|author|admin], google_id, wa_verified_at)
authors (id, user_id, nik, npwp, bank_name, bank_account, bank_holder, ktp_image, status[pending|verified|suspended])
books (id, author_id, title, slug, description, cover_path, pdf_path, price, category_id, status, ai_score, submitted_at, approved_at)
book_tags (book_id, tag_id)
categories (id, name, slug, parent_id)
tags (id, name, slug)
orders (id, user_id, book_id, gross_amount, commission, author_earning, status, midtrans_order_id, paid_at, watermarked_pdf_path, download_count, download_expires_at)
transactions_log (id, order_id, event, midtrans_response_json, created_at)
author_balances (author_id, available, pending, total_earned, last_payout_at)
payouts (id, author_id, period[YYYY-MM], gross_earning, pph23_amount, transfer_fee, net_transfer, status[pending|processed|failed], processed_at, admin_note)
moderation_logs (id, book_id, ai_provider, ai_response_json, admin_id, decision, reason, created_at)
download_logs (id, order_id, user_id, ip, user_agent, downloaded_at)
```

---

## 8. Flow Kritis (untuk dipikirkan saat coding)

### 8.1 Checkout → Watermark → Download
1. User klik "Beli" → buat record `orders` status `pending` → Midtrans Snap pop-up
2. User bayar → Midtrans callback `/api/midtrans/webhook` → verify signature → update order status `paid`
3. Dispatch job `WatermarkPdfJob` ke queue
4. Worker: download master PDF → inject watermark per halaman (fpdi+tcpdf) → upload ke `bukudigi-private/watermarked/{order_id}.pdf`
5. Update order: `watermarked_pdf_path`, `download_expires_at = paid_at + 30 days`
6. Kirim email + WA berisi link `bukudigi.com/download/{order_code}`
7. User klik link → cek auth + order ownership + expiry + counter → generate signed URL R2 24 jam → redirect

### 8.2 Author Submit Buku → Moderasi
1. Author upload PDF + cover via form (chunked upload untuk file > 5 MB)
2. Background job: ekstrak preview 5 halaman, ekstrak text sample untuk AI moderasi
3. Panggil Google Vision + OpenAI Moderation
4. Simpan score di `moderation_logs`
5. Threshold otomatis:
   - Semua aman → status `pending_review` (admin masih review, tapi tidak urgent)
   - Ada flag → status `pending_review` + flag merah di antrian admin
   - Auto-reject → status `rejected`, notif author
6. Admin approve via Filament → status `active`, kirim WA "Buku kamu sudah live di bukudigi.com/buku/{slug}"

### 8.3 Payout Bulanan
1. Cron job tanggal 5 jam 02:00: hitung saldo `available` per author (transaksi `paid_at < tanggal 5 - 7 hari`)
2. Generate record `payouts` per author yang saldo >= 100.000:
   - `gross_earning` = total saldo eligible
   - `pph23_amount` = gross × (NPWP ? 0.02 : 0.04)
   - `transfer_fee` = 6.500 / 2 = 3.250 (dipotong dari author)
   - `net_transfer` = gross - pph23 - transfer_fee
3. Status awal `pending` → muncul di Filament admin
4. Admin export CSV → upload ke internet banking → transfer batch
5. Admin tandai `processed` → update `author_balances.available` → kirim WA "Payout Rp X masuk ke rekening Y, bukti: ..."

### 8.4 Refund (7 hari)
1. User klik "Laporkan masalah" di order → form: alasan (PDF tidak bisa dibuka / halaman kosong / corrupt)
2. Tiket masuk ke admin → admin verifikasi (download PDF watermarked, coba buka)
3. Kalau valid: refund via Midtrans API → status order `refunded` → kurangi saldo author (kalau sudah masuk available)
4. Kalau saldo author < refund: jadi piutang, dipotong dari payout berikutnya
5. Buku ke status review ulang kalau ternyata file rusak (author wajib re-upload)

---

## 9. Roadmap Fase

| Fase | Target | Scope inti |
|---|---|---|
| **F1 — M1** | Foundation | Auth, browse, halaman buku, checkout, watermark, download. Seed 5-10 buku admin. |
| **F2 — M2** | Author flow | Author onboarding, upload, moderasi AI, admin approve, WA notif |
| **F3 — M3** | Payout & ops | Payout manual flow, laporan, refund, cron backup, monitoring |
| **F4 — M4** | Growth | SEO, sitemap, artikel review buku (mirip artikel tarikgas), kategori landing page |
| **F5+** | Skala | Rating/review, bundle, Iris auto-disbursement, search Meilisearch |

> **Catatan:** Fitur **Affiliate** & **Voucher** & **EPUB** sudah dipindah ke MVP/F1 atas keputusan iteratif (2026-05-24). Lihat section 6 dan flow di section 8.

### 8.5 Affiliate (alur)
1. User biasa atau author daftar via `/affiliate/register` (komitmen + channel promosi + motivasi)
2. Admin review di Filament `/admin/affiliates` → approve / reject / suspend (notif WA via Fonnte)
3. Approved → dapat kode unik (auto-generate) + link `?ref=KODE`
4. Pengunjung klik → middleware `AffiliateTracker` set cookie 30 hari + log klik (dedup 1x per ip+affiliate+hari)
5. Pembeli checkout → `CheckoutController` resolve cookie → attach `affiliate_id` ke order, hitung split 70/10/20 (no self-refer)
6. Order paid (stub/webhook) → `AffiliateService::recordEarning` buat record pending dengan `available_at = paid_at + 7 hari`
7. Order failed/refund → `cancelEarning` rollback saldo
8. Cron `affiliate:mature-earnings` harian: pindah pending → available kalau cooling lewat
9. Admin generate `AffiliatePayout` (Filament) per affiliate yang saldo >= 100rb tgl 5 → transfer manual → action "Tandai Diproses" → earnings status `paid`, saldo dikurangi, WA notif ke affiliate

---

## 10. Pertanyaan Belum Terjawab (untuk iterasi berikutnya)

- [ ] Preview PDF: pakai PDF.js embed (interaktif) atau render ke image (lebih ringan, no leak)? → **default pilih image (5 halaman PNG @ cdn)** kecuali ada alasan UX kuat
- [ ] Apakah pembeli boleh beli buku yang sudah dibeli sebelumnya? (untuk hadiahkan?) → **default tidak MVP**, fase 2 fitur "gift"
- [ ] Watermark visible / invisible (LSB-style)? → **MVP visible** di footer, deterrent sosial cukup
- [ ] Bisakah author hapus buku setelah ada pembeli? → **tidak**, hanya archive. Pembeli existing tetap bisa re-download.
- [ ] Pembeli boleh print PDF? → secara teknis bisa (tidak ada DRM); kebijakan: untuk konsumsi pribadi saja, watermark personal sebagai pengingat.
- [ ] Kategori awal apa saja? → draft: Bisnis & Investasi, Edukasi & Akademik, Tutorial & Skill, Fiksi & Sastra, Resep & Kuliner, Kesehatan, Religi, Self-Help, Anak. Diskusi lanjut.

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Pembeli bocorkan PDF watermarked | Author kehilangan revenue | Watermark identitas pembeli + ToS pelarangan + suspend akun pelanggar; tidak ada solusi 100% |
| Author upload konten bajakan | Tuntutan hukum | Verifikasi KTP author + ToS klaim asli + AI scan + admin manual review + DMCA takedown channel |
| Konten porno/SARA lolos | Reputasi & hukum | 2-layer moderasi (AI + admin) + reporting button |
| Midtrans webhook gagal | Pembeli sudah bayar tapi order pending | Polling job tiap 5 menit untuk order pending > 10 menit, cek status via Midtrans API |
| R2 down | Download buku gagal | Cloudflare R2 SLA 99.9%; backup PDF master snapshot tetap di VPS storage (storage/app/originals-backup) untuk emergency |
| VPS down | Total downtime | Cloudflare Always Online untuk static; runbook recovery di docs/DEPLOY.md |
| Author tipu: terima payout lalu hilang | Pembeli komplain tidak ada support | Cooling 7 hari + verifikasi KTP + reputation score author di fase 2 |

---

## 12. Referensi

- Pola tech & deploy: `C:\xampp\htdocs\tarikgas.com\docs\DEPLOY.md`, `STORAGE-SETUP.md`, `DEV-WORKFLOW.md`
- Memori auto-claude: `C:\Users\arsk1\.claude\projects\C--xampp-htdocs-bukudigi-com\memory\`
- Domain dev XAMPP: `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (perlu di-setup `bukudigi.test`)

---

## 13. Estimasi Biaya Operasional

Semua angka kurs ~Rp 16.500/USD per 2026-05-20. Upper-bound (bisa lebih murah dengan promo). Verifikasi ulang harga aktual sebelum sign-up.

### 13.1 VPS Hosting

| Provider | Spek | Harga/bulan | Catatan |
|---|---|---|---|
| **IDCloudHost X1** | 1 vCPU, 1 GB RAM, 20 GB SSD, Jakarta | **Rp 60.000** | ✅ Launch — sama dengan tarikgas |
| IDCloudHost X2 | 1 vCPU, 2 GB RAM, 40 GB SSD | Rp 105.000 | Upgrade saat traffic > 1000 visitor/hari |
| IDCloudHost X4 | 2 vCPU, 4 GB RAM, 80 GB SSD | Rp 235.000 | Saat queue watermark padat |
| Niagahoster VPS X | 1 vCPU, 1 GB RAM | Rp 104.000 | Support ID 24 jam |
| Biznet Gio NEO Lite | 1 vCPU, 1 GB RAM | Rp 89.000 | Alternatif lokal |

⚠️ Beda dengan tarikgas, bukudigi butuh queue worker untuk watermarking PDF. RAM 1 GB sempit kalau PDF master > 30 MB. Siapkan budget upgrade ke X2 saat sudah ada 50+ transaksi/hari.

### 13.2 Domain

| Registrar | Harga/tahun | Per bulan |
|---|---|---|
| **Cloudflare Registrar** | ~$9 (Rp 148.500) | **Rp 12.400** | ✅ At-cost, tanpa markup
| Niagahoster | Rp 165.000 | Rp 13.750 |
| Domainesia | Rp 159.000 | Rp 13.250 |

Cloudflare Registrar wajib pakai DNS Cloudflare (yang memang sudah kita pakai untuk CDN).

### 13.3 Cloudflare CDN + R2 Storage

**CDN + DNS + SSL:** Rp 0/bulan (Free plan, bandwidth unlimited)

**R2 Object Storage billing dimensions:**

| Item | Free tier | Setelah free |
|---|---|---|
| Storage | **10 GB** | $0.015 / GB / bulan |
| Class A ops (write) | 1.000.000 / bulan | $4.50 / juta |
| Class B ops (read) | 10.000.000 / bulan | $0.36 / juta |
| Egress (download) | UNLIMITED | gratis selamanya |

**Asumsi per ebook:**
- Master PDF: 5 MB
- Cover image (3 varian WebP): 200 KB
- Preview 5 halaman: 1 MB
- Total per buku: ~6.2 MB
- Watermarked PDF per order: 5 MB

| Skenario | Buku live | Order/bulan | Storage | Biaya |
|---|---|---|---|---|
| Launch M1-M3 | 50 | 30 | ~0.5 GB | **Rp 0** |
| Tumbuh M4-M6 | 200 | 200 | ~2 GB | **Rp 0** |
| Stable M7-M12 | 500 | 1.000 | ~8 GB | **Rp 0** |
| Skala besar | 2.000 | 5.000 | ~25 GB | **~Rp 3.800/bulan** |

**Hemat storage:** auto-delete watermarked PDF yang `download_expires_at` sudah lewat (job harian). Master PDF tetap, watermarked ephemeral.

### 13.4 Fonnte WhatsApp Gateway

| Paket | Pesan/bulan | Harga/bulan |
|---|---|---|
| **Free** | 1.000 | **Rp 0** ✅ |
| Reguler | 30.000 | Rp 99.000 |
| Bisnis | 100.000 | Rp 199.000 |

**Estimasi traffic WA bukudigi:**

Per transaksi: ~3 pesan (OTP login + receipt + link download)
Per author submit: ~2 pesan (konfirmasi submit + hasil moderasi)
Per payout: ~1 pesan

| Skenario | Total pesan/bulan |
|---|---|
| Launch | ~125 |
| Tumbuh | ~700 |
| Stable | ~3.250 → butuh paket Reguler |

⚠️ Fonnte butuh nomor WA khusus (SIM card terpisah). Nomor pribadi bisa di-ban karena automation. **Setup one-time: Rp 100.000** (kartu perdana + paket data minimum).

### 13.5 AI Moderasi

**Layer 1 — OpenAI Moderation API (teks)**

- Endpoint: `/v1/moderations`
- **Harga: GRATIS unlimited** (OpenAI tidak charge — dipakai sebagai safety layer mereka sendiri)
- Cek: hate, sexual, violence, self-harm, harassment
- Input: judul + deskripsi + 5000 char sample dari halaman 1-5 PDF

**Cost: Rp 0/bulan ✅**

**Layer 2 — Google Vision SafeSearch API (gambar cover)**

| Tier | Range unit/bulan | Harga |
|---|---|---|
| Free | 0 – 1.000 unit | **GRATIS** |
| Tier 1 | 1.001 – 5jt unit | $1.50 / 1.000 unit |
| Tier 2 | > 5jt unit | $0.60 / 1.000 unit |

1 image SafeSearch = 1 unit. SafeSearch + Label Detection sekaligus = 2 unit.

**Strategi 2 mode:**
- **MVP (cover only):** 1 unit per submit buku
- **Paranoid (cover + 5 preview pages):** 6 unit per submit buku

| Skenario | Submit/bulan | Unit cover-only | Unit +preview | Biaya |
|---|---|---|---|---|
| Launch | 20 | 20 | 120 | **Rp 0** |
| Tumbuh | 100 | 100 | 600 | **Rp 0** |
| Stable | 500 | 500 | 3.000 | **Rp 0–50.000** |
| Skala | 2.000 | 2.000 | 12.000 | **Rp 25.000–271.000** |

**Hemat:**
1. MVP cek cover saja → free selamanya kecuali submit > 1.000/bulan
2. Cek preview pages HANYA jika cover SUSPICIOUS
3. Cache hasil per md5 hash file (kalau author submit ulang file sama)

**Alternatif kalau Google Vision mulai mahal:**
- AWS Rekognition Moderation: $1.00 / 1k images, free 5k/bulan tahun 1
- Sightengine: $0.0006/image (~Rp 10)
- OpenAI GPT-4o-mini Vision: $0.15 / 1M input tokens, fleksibel custom prompt

### 13.6 Midtrans (Variable, per transaksi)

Tidak ada biaya bulanan / setup fee.

| Metode | Fee |
|---|---|
| QRIS | 0.7% |
| Virtual Account BCA/BNI/Mandiri/Permata | Rp 4.000 / transaksi |
| GoPay / OVO / DANA / ShopeePay | 2% |
| Kartu kredit | 2.9% + Rp 2.000 |
| BNPL Akulaku / Kredivo | 4–5% |

**Kebijakan default (perlu konfirmasi):** Midtrans fee ditanggung **platform** (potong dari komisi 20%), bukan author. Author terima 80% gross, platform terima 20% gross dikurangi fee gateway.

### 13.7 Hidden / Optional Cost

| Item | Estimasi |
|---|---|
| Anthropic Claude API (chatbot fase 2) | $5 minimum top-up (~Rp 80.000) |
| Email transactional (Brevo free 300/hari → cukup MVP) | Rp 0 |
| Sentry error tracking (free tier 5k events) | Rp 0 |
| Uptime monitoring (UptimeRobot free) | Rp 0 |
| PPh Final UMKM 0.5% dari omzet platform (kalau ada NPWP usaha) | 0.5% omzet |

### 13.8 Ringkasan Total per Skenario

#### Launch (Bulan 1-3) — traffic minimal, 30 order/bulan
| Item | Biaya |
|---|---|
| VPS IDCloudHost X1 | Rp 60.000 |
| Domain Cloudflare | Rp 12.400 |
| Cloudflare CDN + R2 | Rp 0 |
| Fonnte Free | Rp 0 |
| OpenAI Moderation | Rp 0 |
| Google Vision SafeSearch | Rp 0 |
| **TOTAL FIXED** | **Rp 72.400/bulan** |
| Setup one-time (SIM WA + domain tahun 1) | ~Rp 250.000 |

#### Tumbuh (Bulan 4-6) — 200 order/bulan
| Item | Biaya |
|---|---|
| VPS IDCloudHost X2 | Rp 105.000 |
| Domain | Rp 12.400 |
| Cloudflare + R2 | Rp 0 |
| Fonnte Free | Rp 0 |
| AI moderasi | Rp 0 |
| **TOTAL FIXED** | **Rp 117.400/bulan** |

#### Stable (Bulan 7-12) — 1.000 order/bulan
| Item | Biaya |
|---|---|
| VPS IDCloudHost X4 | Rp 235.000 |
| Domain | Rp 12.400 |
| R2 (mendekati 10 GB) | Rp 0–10.000 |
| Fonnte Reguler | Rp 99.000 |
| AI moderasi (kalau preview di-scan) | Rp 0–50.000 |
| **TOTAL FIXED** | **~Rp 350.000–400.000/bulan** |

### 13.9 Breakeven Cepat

Asumsi harga buku rata-rata Rp 50.000, komisi 20% = **Rp 10.000/buku terjual** (gross, sebelum Midtrans fee).

| Fase | Cost/bulan | Buku perlu terjual untuk BEP |
|---|---|---|
| Launch | Rp 72.400 | ~8 buku/bulan |
| Tumbuh | Rp 117.400 | ~12 buku/bulan |
| Stable | Rp 400.000 | ~40 buku/bulan |

8 buku/bulan = ~1 buku per 4 hari. Realistis dari minggu kedua launch kalau marketing organik OK.

### 13.10 Trigger Upgrade Resource

Update tabel ini setiap ada perubahan plan:

| Resource | Sekarang | Trigger upgrade ke level berikutnya |
|---|---|---|
| VPS | X1 (1GB RAM) | RAM usage > 80% sustained / queue lag > 30 detik |
| R2 storage | Free 10 GB | Storage > 8 GB (set Cloudflare alert) |
| Fonnte | Free 1k pesan | Pesan > 800/bulan (cek dashboard tiap awal bulan) |
| Google Vision | Free 1k unit | Submit > 800/bulan |
| Email | Brevo 300/hari | Bounce rate > 5% atau hit daily limit |

---

**Cara update PRD ini:**
- Setiap keputusan baru → tambah ke section yang relevan + update tanggal di header
- Setiap scope berubah → catat di section 5 dengan strikethrough + alasan
- Setiap risiko baru muncul → tambah ke section 11
