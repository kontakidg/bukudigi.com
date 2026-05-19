# Pre-Deploy Checklist — bukudigi.com

Daftar yang harus disiapkan **sebelum** mulai deploy ke VPS.

---

## 1. Domain (Rp 12.400/bulan)

**Recommended:** Cloudflare Registrar (at-cost, no markup, ~$9/tahun ≈ Rp 148.500/tahun)

- [ ] Daftar https://dash.cloudflare.com (kalau belum)
- [ ] Cek availability `bukudigi.com` — domain registrar (mana saja). Lihat juga `bukudigi.id` / `.co.id` sebagai backup
- [ ] Beli domain. Opsi:
  - **Cloudflare Registrar** — paling murah, butuh domain di-add ke Cloudflare DNS dulu
  - **Niagahoster** — Rp 165k/tahun, payment lokal IDR mudah
  - **Domainesia** — Rp 159k/tahun
- [ ] Set nameserver ke Cloudflare (NS1.CLOUDFLARE.COM + NS2.CLOUDFLARE.COM yang dikasih saat add site)
- [ ] Tambah subdomain `cdn.bukudigi.com` (untuk R2 nanti) — A record dummy, akan diganti CNAME ke R2

**Lokasi propagasi cek:** https://dnschecker.org/#A/bukudigi.com

---

## 2. VPS (Rp 60.000-105.000/bulan)

**Recommended:** IDCloudHost (latency lokal Indonesia)

- [ ] Pilih plan:
  - **X1** Rp 60k — launch (1 vCPU, 1 GB RAM, 20 GB SSD) — sufficient untuk 0-300 traffic/day
  - **X2** Rp 105k — kalau watermark PDF terasa lambat (1 vCPU, 2 GB RAM, 40 GB SSD)
- [ ] Pilih OS: **Ubuntu 22.04 LTS** (atau 24.04 LTS)
- [ ] Pilih region: **Jakarta**
- [ ] Setup SSH key di account IDCloudHost (atau pakai password sekali, lalu ganti ke key)
- [ ] Catat: IP public, root password (kalau ada)
- [ ] Login awal: `ssh root@IP_VPS`

**Alternatif VPS:**
- Niagahoster VPS X (Rp 104k) — support ID 24 jam
- Biznet Gio NEO Lite (Rp 89k) — alternative lokal lain

---

## 3. GitHub Repo

- [ ] Buat repo **private**: `https://github.com/USERNAME/bukudigi.com`
- [ ] Jangan init README/gitignore dari UI — kita sudah punya di local
- [ ] Catat URL SSH clone, mis. `git@github.com:USERNAME/bukudigi.com.git`

---

## 4. Cloudflare Account (gratis)

- [ ] Daftar https://dash.cloudflare.com/sign-up
- [ ] Verifikasi email
- [ ] Add Site → `bukudigi.com` → plan **Free**
- [ ] Catat 2 nameserver yang dikasih → set di registrar
- [ ] Tunggu status "Active" (1-24 jam)
- [ ] Aktifkan **R2** (perlu credit card buat anti-abuse, tapi 10 GB free) — lihat tarikgas `STORAGE-SETUP.md` untuk detail

---

## 5. Email Transactional (opsional, gratis)

- [ ] Daftar https://app.brevo.com (Sendinblue) — gratis 300 email/hari, cukup MVP
  - Atau: Mailgun, Postmark, Resend
- [ ] Verify domain `bukudigi.com` di Brevo (set DKIM TXT records di Cloudflare)
- [ ] Generate SMTP credentials → simpan untuk `.env`

---

## 6. Payment (Midtrans — saat siap monetize)

- [ ] Daftar https://midtrans.com → pilih **Snap**
- [ ] Verifikasi KTP + rekening usaha
- [ ] Dapatkan **Sandbox** Server Key + Client Key dulu (untuk testing)
- [ ] Set webhook `https://bukudigi.com/api/midtrans/webhook` di Midtrans dashboard
  - ⚠️ Route ini **belum diimplementasikan** — pakai mode stub dulu, implement webhook nanti
- [ ] Apply production approval setelah test sandbox sukses

---

## 7. WhatsApp Gateway (Fonnte — saat siap notifikasi real)

- [ ] **Beli SIM card baru** dedicated untuk WA bisnis (Rp 100k one-time)
  - JANGAN pakai nomor pribadi — bisa di-ban WhatsApp karena automation
  - Rekomendasi: Telkomsel/Indosat reguler, paket data minimal aktif
- [ ] Daftar https://fonnte.com (gratis 1000 pesan/bulan)
- [ ] Connect device WA via QR
- [ ] Generate token → simpan untuk `.env`

---

## 8. AI Moderation (opsional)

- [ ] **OpenAI Moderation API** — GRATIS unlimited
  - Daftar https://platform.openai.com
  - Generate API key
  - Tidak perlu top-up untuk Moderation endpoint
- [ ] **Google Vision SafeSearch** — free 1000 unit/bulan
  - https://console.cloud.google.com → Create project → Enable Vision API
  - Credentials → Create API key
  - Set budget alert di Cloudflare? Tidak — Google Cloud Console punya alert sendiri

---

## 9. Setup Lokal — Sebelum Push Pertama

Di laptop Windows kamu:

```bash
cd C:\xampp\htdocs\bukudigi.com

# 1. Verify git OK
git status

# 2. Set remote
git remote add origin git@github.com:USERNAME/bukudigi.com.git

# 3. Push initial
git push -u origin main
```

(Push pertama mungkin perlu SSH key di laptop sudah ter-link ke akun GitHub.)

---

## 10. Estimasi Total Cost Awal

**One-time (setup):**
- Domain tahun pertama: Rp 148.500
- SIM card WA: Rp 100.000
- **Total one-time: ~Rp 250.000**

**Bulanan (recurring):**
- Domain: Rp 12.400
- VPS X1: Rp 60.000
- **Total bulanan launch: ~Rp 72.400/bulan**

Naik ke ~Rp 120k bulan 4-6 (upgrade VPS X2), ~Rp 400k stabil di bulan 12 (X4 + Fonnte Reguler).

Detail di [PRD.md](../PRD.md) section 13.

---

## 11. Order Eksekusi Rekomendasi

1. **Beli domain** (1 hari — propagasi DNS)
2. **Daftar Cloudflare** + add domain (paralel dengan #1)
3. **Beli VPS** (instant)
4. **Buat GitHub repo private** (5 menit)
5. **Push code dari local ke GitHub** (lihat docs/DEPLOY.md §3)
6. **Setup VPS** (Ubuntu update, install stack — ~45 menit, lihat docs/DEPLOY.md §2)
7. **Clone dari GitHub di VPS** + setup .env + run deploy.sh (§3)
8. **Configure nginx + DNS A record** (§4-5)
9. **SSL Certbot** (§5.2)
10. **Setup Supervisor queue worker** (§6)
11. **Setup cron scheduler** (§7)
12. **Smoke test live** + **GANTI PASSWORD ADMIN** (§8)
13. (Opsional) R2 setup (STORAGE-SETUP.md)
14. (Opsional) Midtrans/Fonnte production saat siap monetize/notif

Total waktu deploy pertama: ~3-4 jam kalau lancar.

---

## 12. Setelah Live — Day 1 TODOs

- [ ] Ganti password admin via tinker (PASSWORD `password` masih default!)
- [ ] Test register user baru via /register
- [ ] Test author register via /author/register (kalau mau jadi penulis sendiri dulu)
- [ ] Tambah beberapa buku via Filament admin (atau via author flow)
- [ ] Approve buku → test public di /buku
- [ ] Test purchase end-to-end (stub mode, langsung paid)
- [ ] Verify preview PNG generate otomatis
- [ ] Tambah Google Search Console (verify via DNS atau meta tag)
- [ ] Submit sitemap (route /sitemap.xml — belum dibuat, perlu implementasi)
