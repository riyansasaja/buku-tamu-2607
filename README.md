# Buku Tamu PTA Manado

Fondasi aplikasi buku tamu single-office untuk PTA Manado. Aplikasi menggunakan Laravel 13, Eloquent ORM, MySQL, database queue, private file storage, Blade, dan Tailwind CSS.

PRD produk tersedia di [`document/prd/PRD-Aplikasi-Buku-Tamu-PTA-Manado.md`](document/prd/PRD-Aplikasi-Buku-Tamu-PTA-Manado.md).

## Requirements

- PHP 8.3 atau lebih baru beserta ekstensi `mbstring` dan `pdo_mysql`
- Composer 2
- Node.js 22 dan npm
- MySQL 8

## Setup development

1. Salin konfigurasi environment:

   ```bash
   cp .env.example .env
   ```

   Pada Windows PowerShell gunakan `Copy-Item .env.example .env`.

2. Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Buat database dan user MySQL tersebut terlebih dahulu.
3. Install dependency, buat application key, jalankan migration, dan build aset:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate
   npm install
   npm run build
   ```

   Jika PowerShell memblokir `npm.ps1`, gunakan `npm.cmd install` dan `npm.cmd run build`.

4. Jalankan server aplikasi, queue worker, dan Vite sekaligus:

   ```bash
   composer run dev
   ```

   Aplikasi tersedia di `http://localhost:8000` dan health check di `http://localhost:8000/up`.

   Script development tidak menjalankan Laravel Pail karena Pail memerlukan ekstensi `pcntl` yang tidak tersedia di PHP Windows. Untuk memantau log pada PowerShell, buka terminal lain dan jalankan:

   ```powershell
   Get-Content storage/logs/laravel.log -Wait
   ```

   Tekan `Ctrl+C` pada terminal `composer run dev` untuk menghentikan server, queue worker, dan Vite.

## Konfigurasi utama

- `APP_TIMEZONE=Asia/Makassar` dan `APP_LOCALE=id` mengatur zona waktu serta locale aplikasi.
- `DB_CONNECTION=mysql` memastikan Eloquent menggunakan MySQL.
- `QUEUE_CONNECTION=database` menyimpan antrean pada tabel jobs bawaan Laravel.
- `FILESYSTEM_DISK=local` memakai `storage/app/private`; file pada disk ini tidak boleh dihubungkan ke direktori public.
- `.env` tidak boleh di-commit. Hanya `.env.example` dengan placeholder yang menjadi dokumentasi konfigurasi.

## Quality checks

Setelah dependency PHP dan frontend terpasang, jalankan seluruh test, formatter check, static analysis, dan production asset build dengan satu perintah:

```bash
composer quality
```

Pemeriksaan yang sama dijalankan oleh GitHub Actions pada setiap push dan pull request.

## Health check

`GET /up` mengembalikan HTTP 200 ketika aplikasi dan database tersedia. Jika aplikasi hidup tetapi database gagal, endpoint mengembalikan HTTP 503 dengan status dependency generik tanpa exception, credential, hostname internal, atau detail sensitif lainnya.

## Private storage

Disk default `local` diarahkan ke `storage/app/private`. Jangan menjalankan `php artisan storage:link` untuk foto tamu; symbolic link tersebut hanya ditujukan bagi disk `public`, yang tidak dipakai untuk data tamu.

## CI

Workflow quality menyediakan PHP 8.3, Node.js 22, dan MySQL 8, menjalankan migration, kemudian mengeksekusi `composer quality`. Tidak ada credential produksi di workflow; seluruh nilai hanya khusus service MySQL ephemeral di runner CI.

## Dokumentasi produk

Scope dan acceptance criteria lengkap aplikasi terdapat pada [PRD Buku Tamu PTA Manado](document/prd/PRD-Aplikasi-Buku-Tamu-PTA-Manado.md).
