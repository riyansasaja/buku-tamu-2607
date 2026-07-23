# Runbook Insiden

## Fonnte gagal atau limit provider

1. Periksa status provider dan saldo tanpa menyalin token ke tiket.
2. Cari delivery berdasarkan ID kunjungan/request ID, bukan nomor WhatsApp.
3. Hentikan retry massal bila provider bermasalah; gunakan prosedur komunikasi manual kantor.
4. Setelah pulih, retry job gagal secara bertahap dan pastikan tidak ada pesan ganda.

## Queue atau scheduler berhenti

1. Jalankan `php artisan operations:status` dan `php artisan queue:failed`.
2. Periksa Supervisor/cron, koneksi database, disk, dan log worker.
3. Restart worker melalui Supervisor; jangan menghapus tabel jobs.
4. Pastikan heartbeat kembali normal dan survei tertunda diproses sesuai jadwal.

## Admin tidak menerima aktivasi/reset

Verifikasi status akun serta nomor melalui UI admin, periksa delivery tersanitasi, lalu gunakan resend yang tersedia. Jika nomor tidak aktif, koreksi nomor admin dan kirim ulang token baru; token lama harus tetap tidak dapat dipakai setelah alur selesai.

## Rotasi credential

Siapkan token Fonnte/API baru di secret store, aktifkan maintenance singkat bila diperlukan, ubah `.env`, jalankan `php artisan config:cache`, restart worker, lalu lakukan smoke test. Cabut credential lama setelah hasil pengiriman terverifikasi.

## Insiden data atau restore

Batasi akses, jangan mengubah bukti/log, catat rentang waktu dan request ID, beri tahu penanggung jawab, lalu tentukan pemulihan memakai backup terverifikasi. Restore production memerlukan persetujuan eksplisit dan harus memakai pasangan database/storage dari timestamp yang sama.
