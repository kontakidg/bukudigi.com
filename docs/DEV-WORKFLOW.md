# DEV WORKFLOW — bukudigi.com

Panduan menjaga 3 environment sinkron: Local (XAMPP Windows) ↔ GitHub ↔ VPS prod.

Pola sama dengan tarikgas.com (referensi: `C:\xampp\htdocs\tarikgas.com\docs\DEV-WORKFLOW.md`).

---

## 1. Topologi

```
  ┌─────────────┐         ┌──────────────┐         ┌──────────┐
  │  LOCAL DEV  │  push   │   GITHUB     │  pull   │   VPS    │
  │  (XAMPP)    ├────────▶│  bukudigi.com├────────▶│   prod   │
  └─────────────┘         └──────────────┘         └──────────┘
```

**Source of truth = GitHub `main`.**

Aturan keras:
- Local CUMA boleh **push** ke GitHub
- VPS CUMA boleh **pull** dari GitHub
- VPS TIDAK PERNAH commit / push (kalau ada hot-fix di VPS, scp turunkan ke local dulu, baru commit)

---

## 2. File yang TIDAK PERNAH di-commit

Sudah dihandle `.gitignore`:
```
.env, .env.backup, .env.production    # secrets
storage/app/*                          # user uploads (PDFs, covers, previews)
storage/framework/*                    # cache
storage/logs/*                         # logs
vendor/                                # composer install per machine
node_modules/                          # npm install per machine
public/build/                          # vite build (regen di VPS)
public/storage                         # symlink (re-create via storage:link)
.claude/                               # tooling
NEXT-STEPS.md                          # dev workspace, bukan source code
```

---

## 3. Workflow Standar Per Tugas

### A. Mulai kerja di Local

```bash
# Sync main terbaru
git checkout main
git pull origin main

# (Opsional) feature branch
git checkout -b feature/nama-fitur
```

### B. Selesai, push & deploy

**Local:**
```bash
git add <file-specific>           # JANGAN `git add .` (hindari nyasar)
git commit -m "Update: deskripsi singkat why"
git push origin main
```

**VPS:**
```bash
ssh bukudigi@IP_VPS
cd /var/www/bukudigi.com
git pull origin main
bash deploy.sh              # standard
# atau
bash deploy.sh --build      # kalau ada perubahan Tailwind/Vite class baru
bash deploy.sh --composer   # kalau composer.json berubah
bash deploy.sh --full       # semua
```

---

## 4. .env Sync (Hati-hati!)

`.env` punya secret beda per environment:
- Local: pakai DB lokal, Mailtrap, R2 dev (atau local), Midtrans sandbox, Fonnte stub
- VPS: DB prod, mail real, R2 prod, Midtrans production, Fonnte production

**JANGAN copy-paste .env antar environment.**

### Saat ada var .env baru
1. Edit `.env.production.example` di local — tambah var baru + comment
2. Commit `.env.production.example`
3. Di VPS: pull → `nano .env` → tambah var sesuai contoh → isi value prod
4. Verify:
   ```bash
   diff <(grep -oE '^[A-Z_]+' .env.production.example | sort) <(grep -oE '^[A-Z_]+' .env | sort)
   # output kosong = .env complete
   ```

Backup sebelum edit:
```bash
cp .env .env.backup.$(date +%Y%m%d)
```

---

## 5. Database Sync

### Migrations
```bash
# Local
php artisan make:migration tambah_kolom_xxx
# edit migration, test:
php artisan migrate
php artisan migrate:rollback     # uji reversibility
php artisan migrate
git add database/migrations/*.php
git commit -m "Migration: ..."
git push

# VPS (setelah pull)
php artisan migrate --force
```

**Aturan keras:** migration WAJIB punya `down()` method yang benar. Test rollback di local sebelum push.

### Seeders
```bash
# VPS — run seeder specific
php artisan db:seed --class=CategorySeeder --force
```

Jangan run `DummyBooksSeeder` di production — itu untuk dev only.

---

## 6. Build Frontend (Tailwind + Vite)

**Tailwind JIT scan classes pas `npm run build`.** Class baru di .blade tidak otomatis ke CSS.

Gejala kalau lupa build:
- Local: dev mode `npm run dev` → live reload, OK
- VPS: layout pecah / class baru tidak apply

**WAJIB `bash deploy.sh --build`** kalau:
- Ada class Tailwind BARU di .blade/.js
- Ada perubahan `tailwind.config.js`
- Ada perubahan di `resources/css/app.css`

Skip `--build` kalau cuma edit logic PHP / pakai class Tailwind yang sudah ada.

---

## 7. Queue Worker

Di VPS, queue worker jalan via **Supervisor** (background).

Job yang lewat queue:
- `WatermarkPdfJob` — inject watermark saat pembeli bayar
- `GeneratePreviewJob` — extract preview PDF + 5 PNG saat author upload

**Cek queue:**
```bash
sudo supervisorctl status
tail -f /var/www/bukudigi.com/storage/logs/queue.log
php artisan tinker
>>> DB::table('jobs')->count();    # jobs pending
>>> DB::table('failed_jobs')->count();
```

**Restart worker** setelah deploy (deploy.sh sudah handle):
```bash
sudo supervisorctl restart bukudigi-queue:*
```

---

## 8. Storage di VPS

3 lokasi storage di Laravel:
- `storage/app/private/` — disk `local` (PDF master, watermarked, KTP author)
- `storage/app/public/` — disk `public` (covers, preview PDF, preview PNG)
- VPS local filesystem (eventually di-migrate ke Cloudflare R2 — lihat STORAGE-SETUP.md)

**Setelah deploy pertama:**
```bash
php artisan storage:link     # symlink public/storage → storage/app/public
sudo chown -R bukudigi:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 9. Common Issues

### "The environment file is invalid"
`.env` ada baris yang nyangkut jadi satu. Buka nano, pisahkan baris yang harusnya beberapa var.

### Site 500 setelah pull
```bash
php artisan optimize:clear
tail -100 storage/logs/laravel.log
```

### Permission denied saat upload buku
```bash
sudo chown -R bukudigi:www-data storage
sudo chmod -R 775 storage
```

### Watermark / preview tidak jalan
- Cek Ghostscript: `gs -v`
- Cek queue worker: `sudo supervisorctl status`
- Cek log: `tail -f storage/logs/queue.log`

### Git merge conflict
```bash
git status
# Edit file, resolve marker <<<<<<<
git add <file>
git commit
```

---

## 10. Multi-Person Setup (Future)

Saat tim bertambah:
- Setiap dev punya branch sendiri `feature/<nama>`
- Merge ke `main` via PR di GitHub
- Konvensi commit message: `[fitur/area] aksi pendek`
- VPS otomatis di-pull saat ada merge ke main (bisa setup GitHub Action / webhook deploy)

---

## 11. Rollback di VPS

```bash
git log --oneline -5             # lihat 5 commit terakhir
git reset --hard <commit-hash>   # rollback ke commit yg working
bash deploy.sh
php artisan migrate:rollback --step=N    # kalau perlu
```

**Jangan rollback di local.** Local TIDAK PERNAH commit/push selain via push baru. Untuk fix issue, commit perbaikan baru lalu push.

---

## 12. Audit

```bash
# Commits bulan terakhir
git shortlog -sn --since="1 month ago"

# File paling sering diubah
git log --pretty=format: --name-only --since="1 month ago" | sort | uniq -c | sort -rn | head -20
```

---

## Quick Reference

```bash
# Local
git add . && git commit -m "..." && git push

# VPS
cd /var/www/bukudigi.com && git pull && bash deploy.sh
```
