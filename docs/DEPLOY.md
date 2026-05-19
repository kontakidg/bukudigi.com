# Deploy bukudigi.com ke Production

Panduan deploy dari nol — VPS Ubuntu + nginx + PHP 8.3 + MariaDB + Ghostscript + Supervisor.

Stack target sesuai PRD section 3 — diadaptasi dari pola tarikgas.com.

---

## Checklist Sebelum Deploy

- [ ] **Domain `bukudigi.com`** dibeli (Cloudflare Registrar ~Rp 148k/tahun, atau Niagahoster Rp 165k)
- [ ] **VPS** disiapkan (rekomendasi IDCloudHost X1/X2)
- [ ] **GitHub repo private** dibuat (untuk source code)
- [ ] **Cloudflare account** dibuat (untuk DNS, CDN, R2)
- [ ] **Email akun Cloudflare, GitHub** sama / siap di-link

---

## 1. Pilih VPS

| Provider | Spek minimum | Harga/bulan | Catatan |
|---|---|---|---|
| **IDCloudHost X1** | 1 vCPU, 1 GB RAM, 20 GB SSD | Rp 60.000 | Untuk launch (PRD §13.1) |
| **IDCloudHost X2** | 1 vCPU, 2 GB RAM, 40 GB SSD | Rp 105.000 | Saat traffic naik. RAM 1 GB sempit untuk Ghostscript + queue worker bersamaan. |
| Niagahoster VPS X | 1 vCPU, 1 GB RAM | Rp 104.000 | Support ID 24 jam |
| Biznet Gio NEO Lite | 1 vCPU, 1 GB RAM | Rp 89.000 | Alternatif lokal |

**Recommended untuk launch:** IDCloudHost X1, upgrade ke X2 begitu watermarking PDF mulai lambat / queue lag.

OS: **Ubuntu 22.04 LTS** atau 24.04 LTS.

---

## 2. Setup Server (sekali saja, ~45 menit)

SSH ke VPS sebagai root:

```bash
ssh root@SERVER_IP
```

### 2.1 Update + buat user non-root

```bash
apt update && apt upgrade -y

# User deploy
adduser bukudigi
usermod -aG sudo bukudigi

# SSH key dari laptop
mkdir -p /home/bukudigi/.ssh
chmod 700 /home/bukudigi/.ssh
nano /home/bukudigi/.ssh/authorized_keys     # paste ~/.ssh/id_rsa.pub
chmod 600 /home/bukudigi/.ssh/authorized_keys
chown -R bukudigi:bukudigi /home/bukudigi/.ssh

# Test login dari laptop: ssh bukudigi@SERVER_IP

# Disable root SSH (recommended)
nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
systemctl restart ssh
```

### 2.2 Install nginx + PHP 8.3 + MariaDB + **Ghostscript**

```bash
su - bukudigi
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Stack utama
sudo apt install -y \
    nginx \
    mariadb-server \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
    php8.3-bcmath php8.3-tokenizer \
    git unzip curl mysql-client \
    ghostscript

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify
php -v          # PHP 8.3.x
composer -V     # Composer 2.x
node -v         # v20.x
gs -v           # GPL Ghostscript 10.x
```

### 2.3 Tune PHP untuk upload PDF 50 MB

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Cari & set:
```ini
upload_max_filesize = 60M
post_max_size = 60M
memory_limit = 256M
max_execution_time = 120
```

```bash
sudo systemctl restart php8.3-fpm
```

### 2.4 Secure MariaDB

```bash
sudo mysql_secure_installation
# Switch to unix_socket: n
# Change root password: y (set strong)
# Remove anonymous users: y
# Disallow root login remotely: y
# Remove test database: y
# Reload privileges: y
```

### 2.5 Buat database + user

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE bukudigi DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bukudigi'@'127.0.0.1' IDENTIFIED BY 'PASSWORD-KUAT-DISINI';
GRANT ALL PRIVILEGES ON bukudigi.* TO 'bukudigi'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

Simpan password DB — isi ke `.env` nanti.

### 2.6 Firewall

```bash
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
sudo ufw status
```

---

## 3. Clone Repo + Setup App

### 3.1 Deploy key GitHub

Di VPS sebagai `bukudigi`:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_bukudigi -N ""
cat ~/.ssh/github_bukudigi.pub
```

GitHub → **Repo settings → Deploy keys → Add deploy key**:
- Title: `vps-bukudigi`
- Key: paste public key
- Allow write access: ❌

SSH config:
```bash
nano ~/.ssh/config
```

```
Host github.com-bukudigi
    Hostname github.com
    User git
    IdentityFile ~/.ssh/github_bukudigi
    IdentitiesOnly yes
```

### 3.2 Clone

```bash
sudo mkdir -p /var/www
sudo chown bukudigi:bukudigi /var/www
cd /var/www
git clone git@github.com-bukudigi:USERNAME/bukudigi.com.git
cd bukudigi.com
```

### 3.3 Setup `.env`

```bash
cp .env.production.example .env
nano .env
```

Isi minimal:
- `APP_KEY=` (generate setelah composer install)
- `APP_URL=https://bukudigi.com`
- `DB_PASSWORD=PASSWORD-DARI-STEP-2.5`
- `R2_*=` (kosongkan dulu, pakai local storage; setup R2 di [STORAGE-SETUP.md](STORAGE-SETUP.md))
- `MIDTRANS_*=` (kosongkan, mode stub)
- `FONNTE_TOKEN=` (kosongkan, mode stub sampai SIM card siap)
- `GOOGLE_*=`, `OPENAI_*=` (kosongkan, fitur belum aktif)
- `GHOSTSCRIPT_BIN=` (kosongkan, auto-detect `/usr/bin/gs`)

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate
php artisan storage:link
bash deploy.sh --full
```

Run seeder kategori (sekali saja):

```bash
php artisan db:seed --class=CategorySeeder --force
```

(Skip `DummyBooksSeeder` di production — itu untuk dev.)

---

## 4. Setup nginx

```bash
sudo nano /etc/nginx/sites-available/bukudigi.com
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name bukudigi.com www.bukudigi.com;

    root /var/www/bukudigi.com/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 60M;       # upload PDF 50 MB

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_read_timeout 180;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|svg|woff2)$ {
        expires 1M;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Security
    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.(env|log|sql|md)$ { deny all; }

    access_log /var/log/nginx/bukudigi-access.log;
    error_log  /var/log/nginx/bukudigi-error.log;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/bukudigi.com /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### Permissions

```bash
sudo chown -R bukudigi:www-data /var/www/bukudigi.com
sudo chmod -R 775 /var/www/bukudigi.com/storage
sudo chmod -R 775 /var/www/bukudigi.com/bootstrap/cache
```

---

## 5. DNS + SSL

### 5.1 Pointing domain (Cloudflare)

1. Cloudflare dashboard → **Add Site** → `bukudigi.com`
2. Pilih plan Free
3. Cloudflare kasih 2 nameserver → ganti di registrar
4. Tunggu propagasi (1-24 jam)
5. Tambah A records:
   - `bukudigi.com` → IP VPS (proxy 🟠 ON)
   - `www` → IP VPS (proxy 🟠 ON)

### 5.2 SSL Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d bukudigi.com -d www.bukudigi.com
```

Pilih: redirect HTTP → HTTPS.

Test auto-renew:
```bash
sudo certbot renew --dry-run
```

---

## 6. Queue Worker via Supervisor

**WAJIB** — bukudigi pakai queue untuk `WatermarkPdfJob` & `GeneratePreviewJob`. Set `QUEUE_CONNECTION=database` di `.env` prod (bukan `sync` seperti dev).

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/bukudigi-queue.conf
```

```ini
[program:bukudigi-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bukudigi.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=bukudigi
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/bukudigi.com/storage/logs/queue.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bukudigi-queue:*
sudo supervisorctl status
```

---

## 7. Cron untuk Scheduler

```bash
crontab -e
```

```cron
* * * * * cd /var/www/bukudigi.com && php artisan schedule:run >> /dev/null 2>&1
```

Test:
```bash
php artisan schedule:list
```

---

## 8. Test Aplikasi

Buka https://bukudigi.com:

- [ ] Homepage tampil
- [ ] `/login` & `/register` jalan
- [ ] `/admin/login` — login `admin@bukudigi.com` / **GANTI PASSWORD DULU**
- [ ] Login admin → cek `/admin/books` accessible
- [ ] Buka satu buku → klik preview PNG → modal lightbox jalan
- [ ] Test upload buku via author flow (`/author/register`)

### Ganti password admin (WAJIB sebelum live)
```bash
php artisan tinker
>>> $u = User::where('email','admin@bukudigi.com')->first();
>>> $u->password = Hash::make('PASSWORD_BARU_KUAT');
>>> $u->save();
```

---

## 9. Daily Deploy Workflow

Local:
```bash
git add <file-specific>
git commit -m "Pesan jelas"
git push origin main
```

VPS:
```bash
cd /var/www/bukudigi.com
git pull
bash deploy.sh           # standard (cache + migrate kalau ada)
bash deploy.sh --build   # kalau ada perubahan Tailwind/Vite
bash deploy.sh --composer # kalau composer.json berubah
bash deploy.sh --full    # semua di atas
```

---

## 10. Setup R2 Storage (setelah aplikasi live)

Lihat [STORAGE-SETUP.md](STORAGE-SETUP.md) — adopsi pola tarikgas, dengan 3 bucket:
- `bukudigi-public` (covers, preview PDF, preview PNG, asset marketing)
- `bukudigi-private` (PDF master, watermarked PDFs — NO public access)
- `bukudigi-backups` (mysqldump harian)

---

## 11. External Services (sesuai kebutuhan)

### Fonnte WhatsApp
1. Daftar https://fonnte.com (free 1000 pesan/bulan)
2. Beli SIM card baru untuk gateway (jangan pakai nomor pribadi — bisa di-ban)
3. Connect via QR
4. Copy token → `.env` `FONNTE_TOKEN=...`, set `FONNTE_MODE=production`
5. `bash deploy.sh`

### Midtrans
1. Daftar https://midtrans.com → Snap
2. Verifikasi KTP + rekening
3. Sandbox: copy Server Key + Client Key → `.env`, set `MIDTRANS_MODE=production`
4. Set webhook: `https://bukudigi.com/api/midtrans/webhook` (route belum dibuat — perlu implementasi)
5. Test transaksi sandbox dulu
6. Apply production approval

### Google OAuth
1. https://console.cloud.google.com → Create project
2. OAuth consent → External
3. Credentials → OAuth client ID → Web app
4. Redirect URI: `https://bukudigi.com/auth/google/callback`
5. Copy client ID + secret → `.env`

### AI Moderation (opsional MVP)
1. **OpenAI Moderation API** — GRATIS, daftar https://platform.openai.com → get API key
2. **Google Vision** — free tier 1000 unit/bulan, https://console.cloud.google.com → enable Vision API → get key
3. Update `.env`: `OPENAI_API_KEY=`, `GOOGLE_VISION_API_KEY=`, `AI_MODERATION_MODE=production`

---

## 12. Monitoring & Maintenance

```bash
# Logs
tail -f /var/www/bukudigi.com/storage/logs/laravel.log
tail -f /var/log/nginx/bukudigi-error.log
tail -f /var/www/bukudigi.com/storage/logs/queue.log

# Disk
df -h
du -sh /var/www/bukudigi.com/storage/

# Restart services
sudo systemctl restart nginx php8.3-fpm mariadb
sudo supervisorctl restart bukudigi-queue:*

# Cek queue jobs pending
php artisan tinker
>>> DB::table('jobs')->count();
```

---

## 13. Rollback

```bash
git log --oneline -5
git reset --hard <commit-hash>
bash deploy.sh
php artisan migrate:rollback --step=N    # kalau perlu
```

---

## Troubleshooting

### 500 error
```bash
tail -100 storage/logs/laravel.log
sudo chown -R bukudigi:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Upload gagal "413 Request Entity Too Large"
- nginx config: `client_max_body_size 60M` di server block
- PHP: `upload_max_filesize` & `post_max_size` di `php.ini`
- Reload nginx + php-fpm

### Watermark gagal: "Ghostscript not found"
- `which gs` — harus output `/usr/bin/gs`
- `sudo apt install -y ghostscript`
- Atau set `GHOSTSCRIPT_BIN=/usr/bin/gs` di `.env`

### Preview PNG kosong
- Cek log queue: `tail -100 storage/logs/queue.log`
- Cek Ghostscript jalan: `gs -dNOPAUSE -dBATCH -dQUIET -sDEVICE=png16m -r120 -dFirstPage=1 -dLastPage=1 -sOutputFile=/tmp/test.png /var/www/bukudigi.com/storage/app/public/book-previews/.../preview.pdf`

### Queue stuck
```bash
sudo supervisorctl status
sudo supervisorctl restart bukudigi-queue:*
php artisan queue:work --stop-when-empty   # manual drain
```

---

## Quick Reference — Daily Deploy

```bash
# Local
git add . && git commit -m "..." && git push

# VPS
cd /var/www/bukudigi.com && git pull && bash deploy.sh
```
