# Deployment dan Operasional Production

Dokumen ini adalah release checklist Buku Tamu PTA Manado. Jalankan staging terlebih dahulu; jangan menggunakan database production untuk percobaan.

## 1. Prasyarat server

- Linux, Nginx/Apache, PHP 8.3+ (`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`), Composer 2, MySQL 8, Node.js 22 untuk build, Supervisor, cron, `mysqldump`, `tar`, dan HTTPS.
- Document root wajib menuju `public/`, bukan root repository.
- `storage/` dan `bootstrap/cache/` dapat ditulis user PHP; `.env` hanya dapat dibaca user deployment/PHP.
- Foto tetap pada private disk. Jangan menjalankan `storage:link` untuk foto tamu.

## 2. Deployment pertama

```bash
git clone <repository> /var/www/buku-tamu
cd /var/www/buku-tamu
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Isi `.env` production sebelum migration: `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, MySQL, `SESSION_SECURE_COOKIE=true`, database queue/cache/session, API client key acak, Fonnte, nomor resepsionis, dan timezone Asia/Makassar. Jangan menyalin `APP_KEY` development bila database production baru.

Bootstrap admin dilakukan satu kali:

```bash
php artisan db:seed --class=ProductionAdminSeeder --force
```

Gunakan password kuat melalui `INITIAL_ADMIN_PASSWORD`. Setelah berhasil, ubah `INITIAL_ADMIN_ENABLED=false`, hapus nilai password dari `.env`, lalu jalankan `php artisan config:cache`.

Pasang contoh Nginx, Supervisor, dan cron dari folder `deploy/`, sesuaikan domain/path/user, kemudian:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart buku-tamu-worker:*
php artisan app:production-check
php artisan operations:status
```

Pantau `GET /up`, login admin, buat satu kunjungan uji, keputusan pegawai, WhatsApp resepsionis, dan survei tertunda.

## 3. Deployment pembaruan

1. Pastikan backup terbaru valid dan catat versi aktif.
2. `php artisan down --retry=60`.
3. Ambil release, jalankan Composer/npm build, `php artisan migrate --force`, dan `php artisan optimize`.
4. `php artisan queue:restart`, restart Supervisor, lalu `php artisan up`.
5. Jalankan smoke test dan pantau log/failed jobs minimal 30 menit.

## 4. Rollback

- Arahkan symlink/current release ke versi aplikasi sebelumnya dan restart PHP-FPM/worker.
- Migration hanya di-rollback jika migration tersebut terbukti reversibel dan backup tersedia. Untuk perubahan destruktif, pulihkan database serta storage dari pasangan backup yang sama.
- Catat waktu insiden, request ID terkait, keputusan rollback, versi, hasil health check, dan pemilik tindakan.

## 5. Backup dan restore drill

Jalankan `scripts/backup-production.sh` harian dari cron dengan environment yang dilindungi. Salin backup terenkripsi ke lokasi/server berbeda. Script menyimpan database dan private storage sebagai satu pasangan, checksum SHA-256, serta membersihkan backup lokal di atas 30 hari.

Restore hanya pada staging kosong memakai `scripts/restore-production.sh`. Ukur waktu sejak permintaan pemulihan sampai smoke test lulus. Target awal RPO 24 jam dan RTO 8 jam. Restore production memerlukan persetujuan penanggung jawab kantor.

## 6. Monitoring dan alert

- `/up`: HTTP 200; alert setelah 3 kegagalan berurutan.
- `php artisan operations:status --json`: jalankan tiap 5 menit; alert bila scheduler stale, backlog melewati ambang, atau failed job terdeteksi.
- Pantau disk pada 70/85/95%, umur backup terakhir >26 jam, error HTTP 5xx, waktu respons p95, dan kegagalan Fonnte berulang.
- Jalankan `php artisan queue:failed`, periksa kode error tersanitasi, perbaiki penyebab, lalu gunakan `queue:retry` secara terkontrol.
- Jangan masukkan token, nomor WhatsApp, nama/alamat tamu, URL token keputusan/survei, atau isi komentar ke alert/tiket.

## 7. Performance dan UAT gate

Seed 100.000 data sintetis hanya pada local/staging dengan `php artisan performance:seed-visits --count=100000`. Jalankan `k6 run -e BASE_URL=https://staging.example -e API_CLIENT_KEY=... tests/load/visits.js`. Skenario tulis tersedia pada `tests/load/visits-write.js` dan hanya boleh memakai akun pegawai, nomor tamu, serta token Fonnte sandbox/dummy agar tidak mengirim ribuan pesan kepada nomor nyata. Isi `EMPLOYEE_ID` dan `TEST_GUEST_WHATSAPP` saat menjalankannya. Simpan output sebagai bukti. Target p95 read non-upload <500 ms, input kunjungan <2 detik di luar upload, error <1%, dan 95% job mulai diproses <60 detik.

Uji pada 360/768/1024/1280 px dan keyboard. Admin, petugas, dan minimal tiga pegawai menjalankan alur end-to-end. Production hanya boleh dibuka jika backup restore terbukti, HTTPS/worker/scheduler/monitoring aktif, tidak ada secret repository atau defect Severity 1/2, serta pemilik produk menandatangani UAT.

Gunakan pilot 3–5 hari, review harian delivery Fonnte/queue/storage/error, lalu putuskan perluasan atau rollback.
