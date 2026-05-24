# Setup Cloudflare R2 untuk bukudigi.com

R2 = object storage compatible S3 dari Cloudflare. **No egress fees** (download gratis), beda sama AWS S3.

## Step-by-step setup

### 1. Sign-up Cloudflare + aktifkan R2

1. Buat akun di [cloudflare.com](https://cloudflare.com) (kalau belum punya)
2. Dashboard → sidebar kiri → **R2 Object Storage**
3. Klik **"Purchase R2"** (free tier: 10GB storage + 1M Class A operations/bulan, ga butuh kartu kredit kalau di-bawah free tier)

### 2. Buat bucket

1. R2 → **Create bucket**
2. Bucket name: `bukudigi-private`
3. Location: **Asia-Pacific (APAC)** untuk latency optimal ke user Indonesia
4. Default storage class: **Standard**
5. Klik **Create bucket**

⚠️ **Penting**: Bucket biarkan **PRIVATE** (default). Jangan enable public access — file PDF/EPUB harus stream lewat Laravel (untuk authorization + watermark verification).

### 3. Generate API Token

1. R2 → **Manage R2 API tokens** (atau langsung [dash.cloudflare.com/?to=/:account/r2/api-tokens](https://dash.cloudflare.com/?to=/:account/r2/api-tokens))
2. Klik **Create API token**
3. Setting:
   - **Token name**: `bukudigi-prod`
   - **Permissions**: **Object Read & Write**
   - **Specify bucket(s)**: pilih `bukudigi-private` (jangan all, biar scope sempit)
   - **TTL**: pilih sesuai preference (default = never expires)
   - **Client IP Address Filtering**: kosongkan (atau set IP VPS kalau mau ekstra aman)
4. Klik **Create API token**
5. **COPY 3 nilai ini sekarang** — ga akan ditampilkan lagi setelah pindah halaman:
   - **Access Key ID**
   - **Secret Access Key**
   - **Endpoint URL** (format: `https://<account_id>.r2.cloudflarestorage.com`)
6. Save ke password manager / tempat aman

### 4. Update .env di VPS

```bash
nano /www/wwwroot/bukudigi.com/.env
```

Tambahkan/update bagian R2:

```env
PRIVATE_DISK_DRIVER=s3
R2_ACCESS_KEY_ID=<access_key_dari_cloudflare>
R2_SECRET_ACCESS_KEY=<secret_key_dari_cloudflare>
R2_BUCKET=bukudigi-private
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
```

⚠️ Pastikan `PRIVATE_DISK_DRIVER=s3` (sebelumnya default `local`). Tanpa ini, app masih pakai local storage.

### 5. Install dependency & clear cache

```bash
cd /www/wwwroot/bukudigi.com
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan config:cache
```

### 6. Test connection

```bash
php artisan tinker --execute="
\$disk = Storage::disk('private');
\$disk->put('test/connectivity-'.time().'.txt', 'hello from bukudigi at '.now());
\$files = \$disk->files('test');
echo 'Test files in R2:'.PHP_EOL;
foreach (\$files as \$f) echo '  - '.\$f.PHP_EOL;
echo PHP_EOL.'R2 connection: OK ✓';
"
```

Output ideal:
```
Test files in R2:
  - test/connectivity-1717000000.txt
R2 connection: OK ✓
```

Kalau error `S3Exception` atau `Unable to connect`: cek lagi endpoint URL, access key, secret key di `.env`.

### 7. Migrate file existing dari local → R2

```bash
# Preview dulu (dry-run)
php artisan files:migrate-to-r2 --dry-run
```

Cek output: list semua file yang bakal di-upload. Kalau OK:

```bash
# Beneran upload (file local TETAP ada)
php artisan files:migrate-to-r2
```

Output: ringkasan total uploaded / skipped / failed.

Setelah verify R2 berisi semua file (cek di Cloudflare dashboard → R2 → bucket → Objects), baru cleanup local:

```bash
# Hapus file local setelah confirmed semua di R2
php artisan files:migrate-to-r2 --cleanup-local
```

### 8. Test app end-to-end

1. Login customer di `bukudigi.com/library`
2. Klik Download PDF → harusnya tetap jalan (stream dari R2)
3. Klik Baca Online EPUB → reader load (R2 → server → browser)
4. Author upload buku baru → file masuk ke R2 (bukan storage/app/private lagi)

## Troubleshooting

### "The bucket you tried to access does not exist"
- Cek `R2_BUCKET` di `.env` match dengan bucket name di dashboard
- Pastikan tidak ada typo

### "SignatureDoesNotMatch"
- Access Key / Secret Key salah copy
- Pastikan endpoint URL tidak ada trailing slash `/`

### "Could not resolve host"
- Endpoint URL salah (cek format `https://<id>.r2.cloudflarestorage.com`)
- Cek DNS VPS bisa resolve cloudflarestorage.com: `dig <account_id>.r2.cloudflarestorage.com`

### File ada di R2 tapi app return 404
- Cek `PRIVATE_DISK_DRIVER=s3` (bukan `local`)
- `php artisan config:cache` setelah update `.env`

## Cost estimate

R2 free tier (per bulan):
- Storage: 10 GB
- Class A operations (PUT/POST): 1 juta
- Class B operations (GET): 10 juta
- Egress: **GRATIS** (selamanya)

Untuk bukudigi.com MVP (target 100-500 books × 5-50MB rata-rata = 0.5-25 GB storage):
- Bisa stay di free tier sampai ratusan ribu download/bulan
- Lewat free tier: $0.015/GB-bulan storage, $4.50/juta Class A ops

## Backup strategy (opsional, recommended sebelum production)

Setup automated daily sync dari local backup → R2:

```bash
# Cron weekly snapshot ke bucket terpisah
0 3 * * 0 cd /www/wwwroot/bukudigi.com && php artisan backup:r2-snapshot
```

(Belum di-implement — bisa ditambah nanti via Spatie/Laravel-Backup.)

## Rollback ke local kalau ada masalah

```bash
# Di .env, ubah balik:
PRIVATE_DISK_DRIVER=local

php artisan config:clear && php artisan config:cache
```

File yang sudah di-upload ke R2 akan tetap di-cari di local — pastikan local masih punya copy (jangan jalankan `--cleanup-local` sampai 100% confident).
