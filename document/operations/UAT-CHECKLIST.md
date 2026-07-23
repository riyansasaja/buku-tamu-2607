# Checklist UAT dan Release Gate

Tanggal: ____  Versi/commit: ____  Environment: ____

- [ ] Admin login, forgot password WhatsApp, tambah/edit/nonaktifkan admin.
- [ ] Tablet memuat pegawai dan mengirim kunjungan beserta foto.
- [ ] Pegawai menerima tautan, menerima dan menolak dengan alasan; token tidak dapat dipakai ulang.
- [ ] Petugas menerima hasil keputusan melalui WhatsApp.
- [ ] Tamu accepted menerima survei setelah tiga jam; token hanya sekali pakai.
- [ ] Dashboard tahun berjalan dan filter historis sesuai data detail.
- [ ] PDF kunjungan dan survei sesuai filter, multipage, tanpa WhatsApp/foto.
- [ ] Retensi dijalankan dry-run; tidak ada data dalam periode aktif yang terhapus.
- [ ] Tampilan 360/768/1024/1280 px dan navigasi keyboard dasar lulus.
- [ ] Backup harian, checksum, restore drill, RPO/RTO terdokumentasi.
- [ ] Load test 50 concurrent/100.000 data memenuhi target atau deviasi disetujui.
- [ ] Queue, scheduler, health, Fonnte, disk, backup, dan alert terpantau.
- [ ] Tidak ada secret repository atau defect Severity 1/2.

Admin: ____  Petugas: ____  Pegawai 1: ____  Pegawai 2: ____  Pegawai 3: ____  Product owner: ____

Keputusan: [ ] Lulus  [ ] Lulus bersyarat  [ ] Ditolak

Catatan/defect dan penanggung jawab: ____
