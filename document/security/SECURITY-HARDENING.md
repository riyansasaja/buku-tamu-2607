# Hardening Keamanan dan Retensi

## Konfigurasi produksi

- Gunakan `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` HTTPS.
- Atur `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, serta `SESSION_SAME_SITE=lax`.
- Jalankan aplikasi hanya di belakang reverse proxy yang meneruskan informasi HTTPS secara tepercaya.
- Simpan `APP_KEY`, API client key, dan token Fonnte di secret manager/environment; jangan rotasi `APP_KEY` tanpa rencana migrasi data terenkripsi.
- Jalankan queue worker `notifications,default` dan scheduler Laravel secara terus-menerus.

## Retensi kunjungan

Retensi adalah tiga tahun kalender. Pada 1 Januari 2029, kunjungan tahun 2026 dan sebelumnya eligible dihapus. Scheduler menjalankan cleanup tanggal 2 Januari pukul 02:30 WITA.

Sebelum mengaktifkan cleanup production:

1. Pastikan backup database dan storage foto berhasil serta dapat dipulihkan.
2. Jalankan `php artisan visits:purge-expired --dry-run`.
3. Tinjau jumlah eligible tanpa mencatat identitas tamu.
4. Jalankan command tanpa `--dry-run` hanya setelah persetujuan operasional.
5. Set `RETENTION_AUTOMATIC_ENABLED=true` hanya setelah hasil dry-run dan pemulihan backup disetujui. Nilai bawaan `false` mencegah scheduler menghapus data sebelum persetujuan tersebut.

Token dan respons survei menggunakan foreign key `cascadeOnDelete`, sehingga ikut terhapus secara atomik ketika kunjungan melewati batas retensi. Queue worker `notifications` wajib selalu aktif agar undangan survei yang dijadwalkan tiga jam setelah penerimaan dapat dikirim.

Command menggunakan batch dan distributed lock. Foto dipindahkan ke area karantina sebelum record database dihapus. Data survei akan ikut dibersihkan melalui foreign key kunjungan setelah issue #14 tersedia.

## Upload dan logging

Foto dibatasi JPEG/PNG/WebP, maksimum 5 MB, harus dapat dibaca sebagai gambar, disimpan privat dengan nama acak, dan ditolak bila mengandung signature executable/PHP/script. Untuk production berskala penuh, aktifkan scanner malware infrastruktur sebagai lapisan tambahan.

Processor logging meredaksi password, token, authorization, API key, kontak WhatsApp, alamat, dan path foto bila key tersebut muncul dalam context log.

## Pemeriksaan rilis

Jalankan `composer audit`, `npm audit`, seluruh test, PHPStan, Pint, serta verifikasi bahwa `.env` dan credential tidak terlacak Git. Temuan high/critical harus diperbaiki atau memiliki mitigasi tertulis sebelum go-live.
