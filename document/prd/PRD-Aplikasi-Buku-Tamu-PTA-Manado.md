# Product Requirements Document (PRD)

## Aplikasi Buku Tamu PTA Manado

| Atribut | Nilai |
|---|---|
| Status dokumen | Draft v1.3 — rekap dan laporan PDF survei dikonfirmasi |
| Tanggal penyusunan | 22 Juli 2026 |
| Pemilik produk | PTA Manado |
| Target MVP | 5 Agustus 2026 |
| Target operasional | 22 September 2026 |
| Platform | REST API untuk aplikasi mobile dan web admin responsif |
| Teknologi utama | Laravel, Eloquent ORM, MySQL, Tailwind CSS |

---

### 1. Executive Summary

#### Problem Statement

PTA Manado membutuhkan pencatatan terpusat untuk mendokumentasikan tamu yang datang, pegawai yang dituju, maksud kedatangan, foto tamu, serta keputusan pegawai atas kunjungan tersebut. Proses tanpa sistem terpusat menyulitkan kantor mengetahui jumlah kunjungan, tujuan kunjungan, status penerimaan, dan menyusun laporan yang dapat dipertanggungjawabkan.

#### Proposed Solution

Membangun aplikasi buku tamu single-tenant untuk satu kantor, terdiri dari REST API Laravel untuk aplikasi kiosk pada tablet, halaman keputusan terbatas bagi pegawai, halaman survei terbatas bagi tamu, dan web admin responsif. Sistem mencatat kunjungan beserta nomor WhatsApp tamu, mengirim pemberitahuan melalui Fonnte API kepada pegawai tujuan, memfasilitasi keputusan melalui tautan sekali pakai, mengirim hasil keputusan kepada petugas, lalu mengirim survei kepuasan sekali pakai kepada tamu tiga jam setelah kunjungan diterima. Admin dapat mengevaluasi hasil survei yang terhubung dengan identitas kunjungan dan mengekspor rekap terfilter ke PDF.

Aplikasi harus dapat direplikasi untuk kantor lain melalui deployment dan konfigurasi terpisah; satu instalasi hanya melayani satu kantor dan tidak memerlukan fitur multi-tenant.

#### Success Criteria

- 100% kunjungan yang berhasil dikirim melalui client tersimpan dengan nama, alamat, nomor WhatsApp tamu, pegawai tujuan, maksud kedatangan, foto, waktu kunjungan, dan status awal.
- Dashboard dan laporan menampilkan jumlah total, diterima, ditolak, dan belum diputuskan (`pending`) dengan hasil yang sama dengan data kunjungan pada database.
- Sekurang-kurangnya 95% pekerjaan pengiriman notifikasi diproses oleh antrean dalam waktu 60 detik setelah kunjungan tersimpan, diukur dari log aplikasi; keberhasilan sampai ke perangkat bergantung pada penyedia eksternal.
- 100% kunjungan yang diterima menjadwalkan satu survei untuk dikirim tiga jam setelah waktu keputusan, tanpa mengirim survei kepada kunjungan yang ditolak.
- Ringkasan, distribusi rating, tingkat respons, dan detail laporan survei menghasilkan jumlah yang sama dengan data survei berdasarkan filter identik.
- Sekurang-kurangnya 95% permintaan API non-unggah selesai dalam waktu kurang dari 500 ms pada persentil ke-95, dengan beban acuan 50 pengguna bersamaan dan maksimum 100.000 data kunjungan.
- MVP dapat diuji oleh pengguna pada 5 Agustus 2026 dan versi operasional dapat digunakan pada 22 September 2026.

#### Product Objectives

- Mendokumentasikan jumlah tamu, pegawai tujuan, maksud kedatangan, foto, waktu, dan keputusan atas kunjungan ke PTA Manado.
- Mempercepat penyampaian informasi kunjungan kepada pegawai tujuan.
- Menyediakan rekap operasional dan laporan PDF yang konsisten.
- Menyediakan fondasi API stabil yang dapat digunakan framework mobile tanpa ketergantungan pada tampilan web admin.

---

### 2. User Experience & Functionality

#### User Personas

| Persona | Kebutuhan utama | Hak akses |
|---|---|---|
| Tamu | Mencatat kunjungan dan memberi umpan balik pelayanan | Mengisi data pada tablet, lalu mengisi satu survei kepuasan dari tautan WhatsApp setelah kunjungan diterima |
| Petugas penerima tamu | Mengetahui hasil keputusan agar dapat mengarahkan tamu | Menerima pemberitahuan hasil keputusan melalui WhatsApp kantor |
| Pegawai tujuan | Mengetahui kedatangan dan memberi keputusan tanpa memasang aplikasi | Membuka tautan rahasia dari WhatsApp, melihat satu kunjungan, lalu menerima atau menolak |
| Admin | Mengawasi kunjungan, mengevaluasi kepuasan, dan mengelola data master | Dashboard, data kunjungan, rekap survei, laporan PDF, user admin, dan pegawai |

#### User Flow

1. Client mengambil daftar pegawai aktif dari API.
2. Tamu mengisi nama, alamat, nomor WhatsApp, pegawai tujuan, maksud kedatangan, dan foto.
3. API memvalidasi input, menyimpan foto secara privat, dan menyimpan kunjungan dengan status `pending`.
4. API segera memberi respons sukses dan memasukkan pekerjaan notifikasi pegawai ke antrean.
5. Sistem membuat tautan keputusan dengan token acak yang hanya disimpan dalam bentuk hash, lalu mengirim nama, alamat, maksud kedatangan, dan tautan tersebut ke nomor WhatsApp pegawai melalui Fonnte API.
6. Pegawai membuka tautan dan melihat satu halaman detail tamu tanpa login akun.
7. Selama status masih `pending`, pegawai memilih `accepted` atau `rejected`; alasan wajib diisi untuk penolakan.
8. Sistem menyimpan keputusan secara atomik. Setelah keputusan tersimpan, token tidak dapat digunakan kembali dan halaman keputusan tidak lagi menampilkan data tamu.
9. Sistem mengantrekan WhatsApp hasil keputusan kepada petugas penerima tamu. Pesan penerimaan berisi arahan tindak lanjut; pesan penolakan menyertakan alasan.
10. Untuk kunjungan `accepted`, sistem menjadwalkan pengiriman WhatsApp survei kepada tamu tepat tiga jam setelah waktu keputusan. Kunjungan `rejected` tidak menerima survei.
11. Tamu membuka tautan survei sekali pakai, memilih rating bintang 1–5, dan dapat mengisi komentar/saran.
12. Setelah respons tersimpan, token survei tidak dapat digunakan kembali dan halaman tidak lagi menerima jawaban.
13. Admin memantau agregat di dashboard, menelusuri data, dan mengekspor laporan kunjungan PDF.
14. Admin membuka rekap survei, memfilter hasil berdasarkan periode, rating, pegawai, unit kerja, atau status respons, lalu mengekspor hasil identik ke PDF untuk evaluasi pelayanan.

#### User Stories & Acceptance Criteria

##### US-01 — Mencatat kunjungan

**User Story:** Sebagai tamu atau petugas penerima tamu, saya ingin mencatat identitas dan tujuan kunjungan agar kedatangan terdokumentasi.

**Acceptance Criteria:**

- Form/API menerima `guest_name`, `address`, `guest_whatsapp`, `employee_id`, `visit_purpose`, dan `photo`.
- Nama terdiri dari 2–150 karakter; alamat 5–500 karakter; maksud kedatangan 3–1.000 karakter.
- Pegawai tujuan harus ada dan berstatus aktif.
- Nomor WhatsApp tamu wajib, dinormalisasi ke format internasional yang diterima Fonnte, dan harus valid secara struktur.
- Foto wajib berformat JPEG, PNG, atau WebP dan berukuran maksimum 5 MB.
- Data valid disimpan dengan ID unik, nomor kunjungan unik, waktu server, dan status `pending`.
- API merespons HTTP `201` beserta data ringkas kunjungan, tanpa menunggu pengiriman notifikasi selesai.
- Input tidak valid merespons HTTP `422` dengan pesan kesalahan per field dan tidak membuat kunjungan parsial.
- Foto tidak tersedia melalui URL publik permanen tanpa otorisasi.

##### US-01A — Mengisi survei kepuasan

**User Story:** Sebagai tamu yang telah diterima, saya ingin menerima dan mengisi survei singkat agar dapat memberikan penilaian terhadap pelayanan.

**Acceptance Criteria:**

- Hanya kunjungan berstatus `accepted` yang menjadwalkan satu pengiriman survei, tepat tiga jam setelah `decided_at`.
- WhatsApp survei dikirim melalui Fonnte API ke `guest_whatsapp` yang dicatat saat registrasi.
- Tautan memakai token acak berentropi tinggi yang terikat pada satu kunjungan; database hanya menyimpan hash token.
- Halaman survei meminta rating bintang integer 1–5 dan menyediakan komentar/saran opsional maksimum 1.000 karakter.
- Satu kunjungan hanya dapat menyimpan satu respons survei.
- Setelah respons pertama berhasil disimpan, tautan tidak dapat digunakan kembali atau diubah.
- Token salah, dicabut, sudah dipakai, atau terkait kunjungan yang bukan `accepted` tidak mengungkapkan identitas tamu.
- Kegagalan pengiriman mengikuti mekanisme retry notifikasi dan tidak membatalkan keputusan kunjungan.

##### US-02 — Mengambil daftar pegawai tujuan

**User Story:** Sebagai pengguna client mobile, saya ingin memilih pegawai dari daftar resmi agar tujuan kunjungan valid.

**Acceptance Criteria:**

- API hanya menampilkan pegawai aktif.
- Data minimal berisi ID, nama, jabatan, dan unit kerja.
- Daftar mendukung pencarian nama, pagination, dan pengurutan alfabetis.
- Pegawai nonaktif tidak dapat dipakai untuk kunjungan baru, tetapi tetap tampil pada riwayat lama.

##### US-03 — Melihat detail tamu melalui tautan WhatsApp

**User Story:** Sebagai pegawai tujuan, saya ingin membuka detail satu tamu dari tautan WhatsApp agar dapat menentukan tindak lanjut tanpa login atau memasang aplikasi.

**Acceptance Criteria:**

- Setiap kunjungan memiliki token keputusan acak berentropi tinggi; database hanya menyimpan hash token.
- Tautan hanya menampilkan satu kunjungan yang terikat dengan token tersebut dan tidak menyediakan daftar kunjungan pegawai.
- Halaman memuat nama, alamat, maksud kedatangan, waktu, dan foto tamu melalui akses privat.
- Tautan aktif hanya selama kunjungan masih `pending` dan token belum dicabut.
- Token salah, token milik kunjungan lain, atau tautan yang sudah dipakai tidak mengungkapkan data tamu dan menampilkan status tidak tersedia.
- Admin tetap melihat kunjungan melalui web admin dengan autentikasi session dan policy admin, bukan melalui tautan keputusan pegawai.

##### US-04 — Mengirim notifikasi kedatangan

**User Story:** Sebagai pegawai tujuan, saya ingin menerima WhatsApp ketika ada tamu agar dapat segera membuka detail dan memberikan keputusan.

**Acceptance Criteria:**

- Setelah transaksi kunjungan berhasil, sistem membuat pekerjaan notifikasi asynchronous.
- Kanal MVP adalah WhatsApp melalui Fonnte API; credential dan endpoint provider hanya berada pada environment.
- Pesan pegawai memuat nama tamu, alamat, maksud kedatangan, dan tautan keputusan. Foto tidak dikirim sebagai media WhatsApp.
- Setiap percobaan mencatat kanal, status, waktu, jumlah percobaan, respons penyedia yang telah disanitasi, dan pesan gagal.
- Pengiriman gagal dicoba ulang sekurang-kurangnya 3 kali dengan jeda bertingkat dan tidak membatalkan data kunjungan.
- Admin dapat melihat status notifikasi pada detail kunjungan.
- Setelah keputusan tersimpan, sistem mengantrekan pesan WhatsApp kepada nomor petugas yang dikonfigurasi. Pesan menyatakan tamu diterima beserta arahan atau ditolak beserta alasannya.

##### US-05 — Memverifikasi kunjungan

**User Story:** Sebagai pegawai tujuan, saya ingin menerima atau menolak tamu dari halaman keputusan agar petugas mengetahui tindak lanjut kunjungan.

**Acceptance Criteria:**

- Pilihan keputusan MVP hanya `accepted` atau `rejected`.
- Alasan sepanjang 3–500 karakter wajib untuk `rejected` dan tidak ditampilkan untuk `accepted`.
- Keputusan menyimpan waktu keputusan, status, alasan, dan jejak token/link yang digunakan secara atomik.
- Otorisasi keputusan berasal dari token rahasia yang terikat pada kunjungan, bukan login akun pegawai; admin tidak mengambil keputusan atas nama pegawai pada MVP.
- Perubahan keputusan setelah keputusan pertama ditolak dengan HTTP `409`; mekanisme koreksi oleh admin berada di luar MVP dan harus meninggalkan audit trail bila dibuat pada fase berikutnya.
- Setelah keputusan pertama, pengiriman ulang form atau pemakaian ulang link tidak mengubah data dan memberikan halaman bahwa keputusan telah diproses.

##### US-06 — Sign in dan lupa password admin

**User Story:** Sebagai admin, saya ingin masuk dengan aman dan memulihkan password agar dapat mengakses sistem.

**Acceptance Criteria:**

- Login admin menggunakan email dan password melalui koneksi HTTPS.
- Password disimpan menggunakan hasher bawaan Laravel, bukan teks asli.
- Lima kegagalan login dalam satu menit dari kombinasi akun/IP dikenai rate limit.
- Fitur lupa password mengirim tautan sekali pakai yang kedaluwarsa maksimum dalam 60 menit.
- Pesan lupa password tidak mengungkapkan apakah email terdaftar.
- Logout membatalkan sesi/token aktif yang digunakan.

##### US-07 — Melihat dashboard

**User Story:** Sebagai admin, saya ingin melihat ringkasan kunjungan agar mengetahui kondisi pelayanan tamu.

**Acceptance Criteria:**

- Dashboard menampilkan jumlah total kunjungan, diterima, ditolak, dan belum diputuskan (`pending`).
- Data default menggunakan tahun kalender berjalan pada zona waktu `Asia/Makassar`; filter eksplisit dapat menampilkan periode sebelumnya.
- Admin dapat memilih rentang tanggal; seluruh kartu menggunakan rentang yang sama.
- Nilai pada kartu dapat ditelusuri ke daftar kunjungan dengan filter yang sesuai.
- Halaman responsif pada lebar viewport minimum 360 px dan desktop 1.280 px tanpa scroll horizontal pada konten utama.

##### US-08 — Menghasilkan laporan PDF

**User Story:** Sebagai admin, saya ingin mengunduh laporan PDF agar rekap kunjungan dapat diarsipkan atau dibagikan.

**Acceptance Criteria:**

- Admin dapat memfilter laporan berdasarkan rentang tanggal, status, dan pegawai tujuan.
- PDF memuat identitas kantor, periode, waktu pembuatan, ringkasan jumlah, dan tabel detail: nomor, tanggal/waktu, nama tamu, alamat, pegawai tujuan, maksud, status, serta alasan.
- Foto tidak disertakan dalam laporan standar untuk membatasi ukuran dan paparan data pribadi.
- Jumlah ringkasan sama dengan jumlah detail berdasarkan filter yang sama.
- Dokumen menggunakan ukuran A4, memiliki nomor halaman, dan dapat dibuka oleh pembaca PDF standar.
- Hanya admin terautentikasi yang dapat membuat laporan; aktivitas ekspor dicatat.

##### US-08A — Merekap dan mengekspor hasil survei

**User Story:** Sebagai admin, saya ingin melihat dan mengekspor rekap hasil survei beserta identitas kunjungan agar dapat mengevaluasi mutu pelayanan dan melakukan tindak lanjut yang relevan.

**Acceptance Criteria:**

- Halaman rekap hanya dapat diakses admin aktif dan menampilkan total survei terkirim, jumlah respons, tingkat respons, rata-rata rating, serta distribusi rating 1–5.
- Admin dapat memfilter berdasarkan rentang tanggal kunjungan, rating 1–5, pegawai tujuan, unit kerja, dan status respons `responded` atau `not_responded`.
- Tabel detail memuat nomor kunjungan, tanggal kunjungan, nama tamu, alamat, pegawai dan unit tujuan, status survei, rating, komentar/saran, serta waktu respons bila tersedia.
- Filter menggunakan zona waktu `Asia/Makassar`, batas tanggal inklusif, pagination, dan query yang sama untuk ringkasan, detail, serta PDF.
- PDF memuat identitas kantor, filter/periode, waktu pembuatan, ringkasan metrik, distribusi rating, dan tabel detail hasil survei; foto dan nomor WhatsApp tidak disertakan.
- Hasil PDF harus sama dengan rekap web untuk filter identik, menggunakan A4, nomor halaman, header/footer, dan penanganan page break untuk komentar panjang.
- Ekspor PDF hanya dapat dilakukan admin aktif, dikenai rate limit, dan dicatat dalam audit log tanpa menyimpan isi komentar, alamat, atau identitas tamu pada metadata audit.
- Tampilan web responsif pada viewport 360–1.280 px, menyediakan empty state, dan tidak menampilkan data di luar filter.
- Data survei mengikuti retensi kunjungan tiga tahun dan terhapus bersama kunjungan terkait.

**Non-Goals:**

- Mengubah atau menghapus jawaban survei melalui halaman rekap.
- Mengirim tindak lanjut otomatis berdasarkan rating rendah.
- Analisis sentimen, klasifikasi komentar berbasis AI, atau perbandingan lintas-instansi.

##### US-09 — Mengelola user admin

**User Story:** Sebagai admin, saya ingin menambah dan mengelola user admin agar akses pengelolaan dapat dibagikan secara resmi.

**Acceptance Criteria:**

- Admin dapat membuat user dengan nama, email unik, status aktif, dan peran `admin`.
- Password awal dibuat melalui tautan aktivasi/reset; password tidak ditampilkan kembali setelah disimpan.
- Admin dapat menonaktifkan user lain.
- Admin tidak dapat menonaktifkan akun sendiri atau admin aktif terakhir.
- Pembuatan dan perubahan status user tercatat dalam audit log.

##### US-10 — Mengelola pegawai

**User Story:** Sebagai admin, saya ingin mengelola pegawai agar daftar tujuan selalu sesuai data kantor.

**Acceptance Criteria:**

- Admin dapat membuat dan mengubah pegawai dengan nama, NIP/identifier opsional, jabatan, unit kerja, nomor WhatsApp, dan status aktif.
- Nomor WhatsApp wajib tersedia dan valid sebelum pegawai dapat dipilih sebagai tujuan kunjungan aktif.
- Sistem menolak NIP/identifier duplikat jika field tersebut diisi.
- Pegawai yang sudah memiliki riwayat kunjungan tidak dapat dihapus permanen; admin hanya dapat menonaktifkannya.
- Daftar mendukung pencarian, filter status, pengurutan, dan pagination.
- Data kontak notifikasi hanya dapat dilihat atau diubah oleh admin yang berwenang.

#### Functional Scope by Release

| Kemampuan | MVP — 5 Agustus 2026 | Operasional — 22 September 2026 |
|---|---:|---:|
| Master pegawai aktif | Ya | Ya |
| Input kunjungan dan unggah foto | Ya | Ya |
| Detail tamu melalui link WhatsApp sekali pakai | Ya | Ya |
| Verifikasi diterima/ditolak | Ya | Ya |
| WhatsApp melalui Fonnte API | Ya | Ya, dengan retry dan monitoring matang |
| Survei kepuasan sekali pakai 3 jam setelah diterima | Dapat ditunda setelah alur keputusan stabil | Ya |
| Login admin | Ya | Ya |
| Lupa password | Dapat ditunda bila layanan email belum tersedia | Ya |
| Dashboard ringkas | Ya | Ya, dengan filter periode |
| Laporan PDF | Versi dasar | Ya, terformat dan tercatat di audit log |
| Manajemen user admin | Satu admin awal melalui seeder/CLI | Ya, melalui web |
| UI responsif Tailwind | Halaman inti | Seluruh halaman admin |
| Audit log administratif | Minimal untuk keputusan | Ya untuk aksi sensitif dan ekspor |

#### Non-Goals

- Satu instalasi melayani banyak kantor atau pemisahan tenant dalam satu database.
- Pembuatan client mobile; proyek ini hanya menyediakan API yang akan digunakan client tersebut.
- Login, akun, daftar kunjungan, atau aplikasi khusus pada perangkat pribadi pegawai untuk MVP.
- Push notification; seluruh notifikasi operasional MVP menggunakan WhatsApp melalui Fonnte API.
- Registrasi mandiri tamu atau pegawai melalui aplikasi.
- Integrasi biometrik, OCR KTP, pengenalan wajah, QR check-in, atau pencetakan badge.
- Penjadwalan janji temu sebelum tamu datang.
- Pelacakan lokasi tamu atau proses check-out pada MVP.
- Survei multi-pertanyaan, survei anonim tanpa kaitan kunjungan, atau perubahan jawaban setelah survei dikirim.
- Analitik prediktif atau fitur kecerdasan buatan.
- Integrasi ke sistem kepegawaian eksternal.
- Status `waiting` dan perubahan keputusan setelah pegawai memilih menerima atau menolak.
- Koreksi keputusan kunjungan tanpa audit trail.

#### Assumptions and Open Decisions

| Item | Status/Asumsi | Pemilik keputusan | Batas keputusan |
|---|---|---|---|
| Kanal notifikasi MVP | Diputuskan: WhatsApp | Product owner | 23 Juli 2026 |
| Penyedia WhatsApp | Diputuskan: Fonnte API, kantor telah berlangganan | Product owner + tim teknis | 23 Juli 2026 |
| Otorisasi keputusan pegawai | Diputuskan: tautan rahasia sekali pakai, aktif hanya selama kunjungan `pending`; tanpa login pegawai | Product owner + tim teknis | 23 Juli 2026 |
| Nomor WhatsApp petugas | `TBD`: satu nomor kantor melalui environment atau master petugas | Product owner | Sebelum implementasi notifikasi hasil |
| Pemicu survei | Diputuskan: otomatis 3 jam setelah `accepted`; tanpa proses check-out | Product owner | 23 Juli 2026 |
| Isi survei | Diputuskan: rating wajib 1–5 dan komentar/saran opsional | Product owner | 23 Juli 2026 |
| Pengirim email reset password | `TBD`: SMTP kantor atau layanan email | Infrastruktur | Sebelum rilis operasional |
| Retensi data kunjungan dan foto | `TBD` sesuai kebijakan arsip dan privasi kantor | Pimpinan/pejabat data | Sebelum rilis operasional |
| Volume penggunaan | Asumsi desain: hingga 100.000 kunjungan per instalasi | Product owner | Validasi saat UAT |

---

### 3. AI System Requirements (If Applicable)

Tidak berlaku. Produk ini tidak menggunakan model AI, machine learning, pengenalan wajah, atau pengambilan keputusan otomatis. Status kunjungan dipilih secara eksplisit oleh pegawai tujuan.

#### Tool Requirements

Tidak ada tool atau API AI yang diperlukan.

#### Evaluation Strategy

Tidak ada evaluasi kualitas keluaran AI. Evaluasi produk dilakukan melalui pengujian fungsional, keamanan, performa, integritas data, dan UAT sebagaimana dijelaskan pada spesifikasi teknis.

---

### 4. Technical Specifications

#### Architecture Overview

```text
Tablet Kiosk ────────┐
                    ├── HTTPS ── Laravel Application/API ── Eloquent ── MySQL
Web Admin + Tailwind ┤                    │
                    │                    ├── Private Object/File Storage (foto)
WhatsApp Pegawai ────┤                    ├── Queue Worker ── Fonnte API ── WhatsApp
                    │                    └── Mail Provider (reset password admin)
Halaman Keputusan ───┘
```

- Laravel menyediakan REST API versioned dan halaman admin server-rendered atau komponen web yang tetap berada dalam satu aplikasi.
- Eloquent menjadi satu-satunya lapisan akses data utama ke MySQL; query agregasi boleh menggunakan query builder Laravel untuk efisiensi.
- Pengiriman WhatsApp kepada pegawai dan petugas memakai queue agar input kunjungan maupun keputusan tidak tertahan oleh Fonnte API.
- Scheduler Laravel menangani pembersihan token kedaluwarsa, retry terjadwal, dan pekerjaan pemeliharaan.
- Foto disimpan pada disk privat lokal atau object storage kompatibel S3 berdasarkan konfigurasi deployment.
- Konfigurasi yang berbeda memungkinkan codebase yang sama direplikasi per kantor tanpa menggabungkan datanya.

#### Proposed Domain Model

| Entitas | Field utama | Catatan |
|---|---|---|
| `users` | id, name, email, password, role, is_active, timestamps | Akun web admin; akun pegawai tidak diperlukan pada MVP |
| `employees` | id, employee_no nullable, name, position, unit, whatsapp_number encrypted, is_active, timestamps | Master pegawai tujuan dan nomor penerima WhatsApp; soft delete atau nonaktif |
| `visits` | id, visit_number, guest_name, address, guest_whatsapp encrypted, employee_id, purpose, photo_path, status, reason nullable, decided_at nullable, created_at, updated_at | Status aktif MVP: pending, accepted, rejected |
| `visit_decision_tokens` | id, visit_id unique, token_hash unique, revoked_at nullable, used_at nullable, timestamps | Token asli hanya dikirim melalui link WhatsApp dan tidak disimpan dalam bentuk teks asli |
| `survey_invitations` | id, visit_id unique, token_hash unique nullable, status, scheduled_at, sent_at nullable, revoked_at nullable, used_at nullable, timestamps | Hanya untuk kunjungan accepted; token asli tidak disimpan; status scheduled/sent/used/revoked |
| `survey_responses` | id, survey_invitation_id unique, visit_id unique, rating, comment nullable, submitted_at, timestamps | Rating integer 1–5; satu respons per kunjungan |
| `notification_deliveries` | id, visit_id, employee_id, channel, provider_message_id nullable, status, attempts, last_attempt_at, error_message nullable, timestamps | Audit teknis pengiriman |
| `audit_logs` | id, actor_id nullable, action, auditable_type, auditable_id, metadata JSON, ip_address, created_at | Tidak menyimpan password/token/isi foto |
| `password_reset_tokens` | email, token, created_at | Mengikuti mekanisme Laravel |

Indeks minimum: `visits(employee_id, created_at)`, `visits(status, created_at)`, unique `visits.visit_number`, unique `users.email`, serta unique nullable `employees.employee_no` sesuai dukungan MySQL.

#### State Model

```text
pending ──► accepted
   │
   └──────► rejected
```

Pada MVP, setiap kunjungan menerima tepat satu keputusan final. Status `waiting` tidak digunakan dalam alur kiosk–WhatsApp yang telah disepakati. Kartu “menunggu” pada dashboard berarti kunjungan berstatus `pending` yang belum diputuskan.

#### API Contract (Proposed)

Base path: `/api/v1`. Format respons: JSON. Semua tanggal menggunakan ISO 8601 dengan offset zona waktu; penyimpanan database dianjurkan UTC dan penyajian default `Asia/Makassar`.

| Method | Endpoint | Kegunaan | Akses |
|---|---|---|---|
| `POST` | `/visits` | Membuat kunjungan multipart/form-data | Client terotorisasi/rate-limited |
| `GET` | `/employees` | Daftar pegawai aktif | Client terotorisasi |
| `GET` | `/decisions/{token}` | Halaman web detail satu kunjungan | Token rahasia aktif milik kunjungan |
| `POST` | `/decisions/{token}` | Menerima atau menolak kunjungan | Token rahasia aktif milik kunjungan, rate-limited |
| `GET` | `/surveys/{token}` | Halaman web survei kepuasan | Token survei aktif milik kunjungan accepted |
| `POST` | `/surveys/{token}` | Menyimpan rating dan komentar | Token survei aktif, sekali pakai, rate-limited |
| `POST` | `/auth/forgot-password` | Memulai reset password | Publik, rate-limited |
| `POST` | `/auth/reset-password` | Menetapkan password baru | Token reset valid |
| `GET` | `/admin/dashboard` | Agregat dashboard | Admin |
| `GET` | `/admin/reports/visits.pdf` | Laporan PDF | Admin |
| `GET` | `/admin/surveys` | Rekap dan detail hasil survei | Admin |
| `GET` | `/admin/reports/surveys.pdf` | Laporan PDF hasil survei | Admin, rate-limited dan diaudit |
| CRUD | `/admin/users` | Manajemen admin | Admin |
| CRUD | `/admin/employees` | Manajemen pegawai | Admin |

Ketentuan kontrak:

- Gunakan shared client key terkonfigurasi untuk aplikasi kiosk pada tablet, session/cookie aman untuk web admin, dan capability token acak sekali pakai untuk halaman keputusan pegawai.
- Token keputusan disimpan sebagai hash, dibandingkan secara konstan, terikat pada satu kunjungan, serta dicabut atomik saat keputusan pertama tersimpan.
- Token survei mengikuti kontrol yang sama dan dicabut atomik saat respons pertama tersimpan.
- Gunakan Laravel API Resources agar bentuk respons konsisten.
- Pagination default 20, maksimum 100 item per halaman.
- Error menggunakan struktur konsisten: `message`, `errors`, dan `request_id`.
- Terapkan idempotency key pada `POST /visits` untuk mencegah data ganda ketika client mengulang request akibat koneksi terputus.
- Dokumentasikan API dengan OpenAPI 3.1 dan sediakan koleksi pengujian sebelum integrasi client mobile.

#### Integration Points

- **MySQL:** penyimpanan transaksi, master data, status, dan audit.
- **Private storage:** penyimpanan foto tamu; akses melalui signed URL berumur maksimum 10 menit atau streaming setelah otorisasi.
- **WhatsApp provider:** Fonnte API untuk pemberitahuan pegawai, hasil keputusan kepada petugas, dan tautan survei kepada tamu; membutuhkan API token, nomor tujuan tervalidasi, timeout, retry, dan sanitasi log respons.
- **Email:** SMTP atau layanan email untuk reset password, `TBD`.
- **Queue backend:** database queue dapat dipakai untuk MVP; Redis direkomendasikan bila beban atau kebutuhan observabilitas meningkat.

#### Web Admin UX Requirements

- Tailwind CSS menjadi dasar styling dan tidak bergantung pada layout fixed-width.
- Breakpoint wajib diuji pada 360 px, 768 px, 1.024 px, dan 1.280 px.
- Navigasi dapat digunakan dengan keyboard; setiap input memiliki label; fokus terlihat; kontras mengacu WCAG 2.1 level AA.
- Tabel pada layar kecil menggunakan responsive overflow atau tampilan kartu tanpa menyembunyikan aksi penting.
- Setiap operasi mutasi menampilkan status berhasil/gagal yang jelas dan mencegah double submission.
- Zona waktu yang terlihat oleh admin menggunakan `Asia/Makassar`.

#### Security & Privacy

- Semua lingkungan produksi wajib menggunakan HTTPS dan cookie web `Secure`, `HttpOnly`, serta `SameSite` yang sesuai.
- Gunakan Laravel validation, authorization policy, CSRF protection untuk web, rate limiting, dan prepared query melalui Eloquent/query builder.
- Terapkan role-based access pada web admin. Halaman keputusan wajib memverifikasi token rahasia dan status `pending` tanpa menerima `visit_id` dari client sebagai sumber otorisasi.
- Jangan menyimpan token keputusan asli di database atau log. Respons untuk token salah, sudah dipakai, dan dicabut tidak boleh mengungkapkan identitas tamu.
- Jangan menyimpan token survei asli atau isi komentar dalam log. Nomor tamu hanya boleh digunakan untuk komunikasi terkait kunjungan dan survei yang ditentukan PRD.
- Rekap survei dapat menampilkan identitas tamu hanya kepada admin aktif untuk kebutuhan evaluasi. PDF tidak memuat foto atau nomor WhatsApp, dan aktivitas ekspor dicatat tanpa menyalin identitas, alamat, atau komentar ke metadata audit.
- Nomor WhatsApp pegawai dan tamu dienkripsi at rest menggunakan mekanisme aplikasi; secret provider hanya berada di environment/secret manager.
- Foto dan alamat diperlakukan sebagai data pribadi, tidak dimasukkan ke log aplikasi, analytics, atau payload notifikasi secara lengkap.
- Batasi MIME berdasarkan inspeksi server, ubah nama file menjadi identifier acak, dan tolak executable/polyglot yang terdeteksi. Antivirus scanning dipertimbangkan untuk rilis operasional.
- Backup database dan storage dilakukan setiap hari; target awal RPO maksimum 24 jam dan RTO maksimum 8 jam, lalu divalidasi oleh tim infrastruktur.
- Retensi dan prosedur penghapusan data wajib ditetapkan PTA Manado sebelum produksi; sampai kebijakan disahkan, data tidak boleh dihapus otomatis.
- Audit log mencakup login sensitif, keputusan pegawai, perubahan user/pegawai, dan ekspor laporan, dengan metadata seminimal mungkin.
- Patuhi kebijakan internal PTA Manado dan peraturan perlindungan data Indonesia yang berlaku; verifikasi legal/compliance menjadi gate sebelum produksi.

#### Non-Functional Requirements

| Area | Target |
|---|---|
| Availability | Minimum 99,5% per bulan di luar maintenance terjadwal setelah rilis operasional |
| API latency | p95 < 500 ms untuk endpoint non-upload pada beban acuan |
| Visit creation | p95 < 2 detik untuk foto hingga 5 MB, tidak termasuk waktu upload jaringan client |
| Notification queue | 95% job mulai diproses < 60 detik setelah commit transaksi |
| Data capacity | Sedikitnya 100.000 kunjungan per instalasi tanpa perubahan arsitektur utama |
| Accessibility | Alur inti admin memenuhi WCAG 2.1 AA dalam audit manual dasar |
| Compatibility | Dua versi stabil terbaru Chrome, Edge, Firefox; Safari mobile untuk layout responsif |
| Observability | Structured log dengan request ID, queue failure log, health check, dan alert untuk job gagal berulang |
| Localization | Antarmuka dan pesan utama berbahasa Indonesia; penyimpanan timestamp konsisten |

#### Testing and Validation

- **Unit test:** validasi status, policy akses, pembuatan nomor kunjungan, agregasi dashboard, dan formatter laporan.
- **Feature/API test:** seluruh endpoint sukses/gagal, autentikasi admin/client, token keputusan/survei, pagination, filter, idempotency, dan upload file.
- **Integration test:** sandbox Fonnte, delayed survey job tiga jam, queue retry/failure, storage privat, email reset, dan database migration.
- **Security test:** CSRF, substitusi token/IDOR, replay keputusan/survei, brute-force rate limit, file upload berbahaya, akses signed URL kedaluwarsa, serta kebocoran field sensitif/token.
- **Performance test:** 50 pengguna bersamaan, dataset 100.000 kunjungan, dengan hasil p95 dibandingkan target NFR.
- **PDF test:** verifikasi filter, agregasi, distribusi rating, tingkat respons, dan total secara otomatis, lalu pemeriksaan visual sampel laporan panjang, komentar hingga 1.000 karakter, karakter Indonesia, page break, header, dan footer.
- **Responsive test:** viewport 360/768/1024/1280 px dan navigasi keyboard pada halaman login, dashboard, kunjungan, pegawai, user, dan laporan.
- **Backup restore drill:** sekurang-kurangnya satu kali sebelum produksi untuk membuktikan backup database dan foto dapat dipulihkan.
- **UAT:** admin, petugas penerima, dan minimal tiga pegawai menjalankan skenario end-to-end; tidak ada defect Severity 1/2 yang terbuka saat go-live.

#### Definition of Done

- Acceptance criteria fitur terpenuhi dan memiliki bukti pengujian.
- Migration dapat dijalankan pada database kosong dan rollback aman telah diuji pada staging.
- OpenAPI dan petunjuk deployment diperbarui.
- Tidak ada secret di repository dan tidak ada kerentanan kritis/tinggi yang belum dimitigasi.
- Monitoring, backup, dan prosedur pemulihan tersedia.
- Product owner PTA Manado menyetujui hasil UAT.

---

### 5. Risks & Roadmap

#### Phased Rollout

##### MVP — 22 Juli–5 Agustus 2026

- Hari 1–2: finalisasi konfigurasi Fonnte, format nomor petugas, kontrak OpenAPI, skema token keputusan, dan wireframe halaman keputusan.
- Hari 3–7: implementasi master pegawai, input kunjungan/foto, tautan keputusan sekali pakai, verifikasi, login admin, dan queue WhatsApp.
- Hari 8–10: dashboard dasar, PDF dasar, halaman Tailwind inti, serta pengujian API dan policy.
- Hari 11–12: integrasi sandbox client/notifikasi dan perbaikan defect.
- Hari 13–14: deployment staging, UAT MVP, dokumentasi, dan demo pada 5 Agustus 2026.

##### v1.1 / Production Hardening — 6 Agustus–22 September 2026

- Menyelesaikan forgot password dan manajemen user melalui web.
- Menambahkan survei kepuasan sekali pakai, pengiriman tertunda tiga jam, rekap hasil survei beridentitas, filter evaluasi, dan ekspor PDF survei.
- Mematangkan laporan PDF, filter dashboard, audit log, retry/monitoring notifikasi, dan responsive/accessibility QA.
- Melakukan performance test, security test, backup restore drill, pelatihan admin, migrasi konfigurasi produksi, dan UAT final.
- Go-live bertahap: pilot internal 3–5 hari, evaluasi, lalu penggunaan kantor penuh paling lambat 22 September 2026.

##### v2.0 — Setelah evaluasi operasional

- Status sementara atau perubahan keputusan dengan histori status bila kebutuhan operasional membuktikannya perlu.
- Check-out tamu, QR code, appointment, atau badge bila dibuktikan perlu oleh data operasional.
- Kanal notifikasi cadangan/fallback dan dashboard delivery analytics.
- Paket deployment terdokumentasi untuk replikasi kantor lain tanpa fitur multi-tenant.

#### Technical and Product Risks

| Risiko | Probabilitas | Dampak | Mitigasi |
|---|---|---|---|
| Gangguan atau perubahan layanan Fonnte API | Sedang | Tinggi | Gunakan adapter terisolasi, timeout, queue retry, delivery log, dan prosedur fallback manual |
| Tautan keputusan diteruskan atau bocor | Sedang | Tinggi | Token acak berentropi tinggi, simpan hash, HTTPS, sekali pakai, redaksi log, dan invalidasi segera setelah keputusan |
| Survei terkirim terlalu cepat, terlambat, atau ganda | Sedang | Sedang | Gunakan delayed queue berdasarkan `decided_at`, unique job/token, idempotensi pengiriman, dan rekonsiliasi scheduler |
| Nomor WhatsApp tamu salah atau bukan milik tamu | Sedang | Sedang | Normalisasi dan validasi struktur, tampilkan konfirmasi nomor pada tablet, serta catat kegagalan delivery tanpa retry tanpa batas |
| Target MVP dua minggu sangat ketat | Tinggi | Tinggi | Batasi pada alur inti, satu kanal, dashboard/PDF dasar; pindahkan hardening ke v1.1 |
| Foto memperbesar storage dan backup | Sedang | Sedang | Batasi 5 MB, kompresi terkontrol, monitoring kapasitas, dan kebijakan retensi |
| Layanan notifikasi eksternal gagal atau lambat | Sedang | Sedang | Queue, retry bertingkat, failure log, alert, dan status delivery pada admin |
| Data pribadi terekspos melalui URL/log/PDF | Sedang | Tinggi | Storage privat, signed URL, redaksi log/payload, RBAC, audit ekspor, dan minimisasi PDF |
| Data dashboard dan PDF tidak konsisten | Sedang | Tinggi | Satu service/query filter bersama dan automated reconciliation tests |
| Identitas atau komentar survei terekspos melalui PDF/log | Sedang | Tinggi | RBAC admin aktif, minimisasi kolom PDF, audit ekspor tersanitasi, rate limit, dan larangan mencatat isi komentar/identitas pada log |
| Replikasi kantor menghasilkan konfigurasi tidak konsisten | Sedang | Sedang | Environment template, deployment checklist, seeder konfigurasi kantor, dan versioned migrations |
| Kehilangan data akibat kegagalan server | Rendah–sedang | Tinggi | Backup harian database+foto, restore drill, monitoring backup, dan prosedur insiden |

#### Dependencies

- Persetujuan product owner atas scope dan alur keputusan kunjungan.
- Data awal pegawai PTA Manado beserta jabatan, unit, dan kontak/perangkat notifikasi.
- Persetujuan teks pesan survei dan kebijakan penggunaan nomor WhatsApp tamu untuk tindak lanjut pelayanan.
- Akun dan API token Fonnte yang sah serta saldo/kuota pengiriman yang cukup.
- SMTP atau layanan email untuk forgot password.
- Server produksi dengan PHP/Laravel runtime, MySQL, queue worker, scheduler, HTTPS, storage, backup, dan monitoring.
- Tim/framework mobile menyepakati OpenAPI, autentikasi kiosk, format foto, serta format input dan konfirmasi nomor WhatsApp tamu.

#### Release Gates

**Gate MVP:** alur input → notifikasi antrean → daftar pegawai → keputusan berjalan di staging; dashboard dan PDF dasar benar; policy akses lulus; UAT MVP disetujui.

**Gate Produksi:** seluruh acceptance criteria operasional selesai; provider produksi aktif; backup/restore terbukti; observability aktif; security dan performance test lulus; kebijakan retensi disepakati; tidak ada defect Severity 1/2; UAT final ditandatangani.

#### Recommended Immediate Decisions

1. Tetapkan satu nomor WhatsApp petugas penerima tamu dan format arahan penerimaan yang akan dikonfigurasi pada server.
2. Sediakan credential Fonnte untuk lingkungan sandbox/staging dan pastikan format nomor pegawai telah dinormalisasi.
3. Serahkan data pegawai awal dan identitas visual kantor pada hari pertama.
4. Uji redaksi log dan replay protection pada tautan keputusan sebelum UAT.
5. Tetapkan retensi foto dan data kunjungan sebelum go-live produksi.
6. Tetapkan teks WhatsApp survei dan cara admin melihat ringkasan rating sebelum fitur survei dirilis.

---

### Appendix A — KPI Measurement

| KPI | Sumber data | Frekuensi | Cara hitung |
|---|---|---|---|
| Kunjungan terdokumentasi | `visits` dan audit request | Harian | Jumlah record valid dibanding transaksi sukses client |
| Distribusi status | `visits.status` | Harian/bulanan | Count per status dalam periode dan zona waktu yang sama |
| Kecepatan pemrosesan notifikasi | `notification_deliveries` + queue metrics | Harian | Selisih `visits.created_at` dengan waktu percobaan pertama; hitung p95 dan persentase <60 detik |
| Ketepatan jadwal survei | `survey_invitations`, delivery log, dan queue metrics | Harian | Persentase survei accepted yang dikirim pada jendela 3 jam ±5 menit dari `decided_at`; target ≥95% |
| Respons survei | `survey_invitations` + `survey_responses` | Mingguan/bulanan | Jumlah respons valid dibagi survei yang berhasil dikirim; laporkan tingkat respons, rata-rata, dan distribusi rating 1–5 tanpa mengubah data mentah |
| Latensi API | APM/structured log | Harian | p95 durasi endpoint, pisahkan upload dan non-upload |
| Konsistensi laporan | Test suite + rekonsiliasi | Setiap rilis | Total dashboard/PDF harus sama dengan query detail untuk filter identik |

### Appendix B — Severity Definition

- **Severity 1:** kehilangan/kebocoran data, sistem tidak dapat digunakan, atau kontrol autentikasi/otorisasi dapat dilewati.
- **Severity 2:** alur utama input, notifikasi, keputusan, dashboard, atau laporan gagal tanpa workaround yang layak.
- **Severity 3:** fungsi pendukung terganggu tetapi terdapat workaround.
- **Severity 4:** masalah kosmetik atau penyempurnaan minor yang tidak menghambat tugas.
