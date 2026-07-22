# Product Requirements Document (PRD)

## Aplikasi Buku Tamu PTA Manado

| Atribut | Nilai |
|---|---|
| Status dokumen | Draft v1.0 |
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

Membangun aplikasi buku tamu single-tenant untuk satu kantor, terdiri dari REST API Laravel bagi client mobile dan web admin responsif. Sistem mencatat kunjungan, mengirim notifikasi kepada pegawai tujuan melalui push notification atau WhatsApp, memfasilitasi verifikasi kunjungan, mengelola admin dan pegawai, menampilkan dashboard, serta menghasilkan laporan PDF.

Aplikasi harus dapat direplikasi untuk kantor lain melalui deployment dan konfigurasi terpisah; satu instalasi hanya melayani satu kantor dan tidak memerlukan fitur multi-tenant.

#### Success Criteria

- 100% kunjungan yang berhasil dikirim melalui client tersimpan dengan nama, alamat, pegawai tujuan, maksud kedatangan, foto, waktu kunjungan, dan status awal.
- Dashboard dan laporan menampilkan jumlah total, diterima, ditolak, dan menunggu dengan hasil yang sama dengan data kunjungan pada database.
- Sekurang-kurangnya 95% pekerjaan pengiriman notifikasi diproses oleh antrean dalam waktu 60 detik setelah kunjungan tersimpan, diukur dari log aplikasi; keberhasilan sampai ke perangkat bergantung pada penyedia eksternal.
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
| Tamu/petugas penerima tamu | Mencatat kunjungan secara cepat dan benar | Melihat daftar pegawai aktif dan membuat kunjungan melalui client mobile |
| Pegawai tujuan | Mengetahui kedatangan dan memberi keputusan | Melihat kunjungan yang ditujukan kepadanya serta memilih menerima, menolak, atau menunggu |
| Admin | Mengawasi kunjungan dan mengelola data master | Dashboard, data kunjungan, laporan, user admin, dan pegawai |

#### User Flow

1. Client mengambil daftar pegawai aktif dari API.
2. Tamu atau petugas mengisi nama, alamat, pegawai tujuan, maksud kedatangan, dan foto.
3. API memvalidasi input, menyimpan foto secara privat, dan menyimpan kunjungan dengan status `pending`.
4. API segera memberi respons sukses dan memasukkan pekerjaan notifikasi ke antrean.
5. Sistem mengirim push notification atau WhatsApp kepada pegawai tujuan dan mencatat hasil percobaan.
6. Pegawai membuka daftar kunjungan yang ditujukan kepadanya.
7. Pegawai memilih `accepted`, `rejected`, atau `waiting`. Alasan wajib diisi untuk status `rejected` dan `waiting`; alasan untuk `accepted` bersifat opsional.
8. Sistem mencatat keputusan, alasan, identitas pemutus, dan waktu keputusan.
9. Admin memantau agregat di dashboard, menelusuri data, dan mengekspor laporan PDF.

#### User Stories & Acceptance Criteria

##### US-01 — Mencatat kunjungan

**User Story:** Sebagai tamu atau petugas penerima tamu, saya ingin mencatat identitas dan tujuan kunjungan agar kedatangan terdokumentasi.

**Acceptance Criteria:**

- Form/API menerima `guest_name`, `address`, `employee_id`, `visit_purpose`, dan `photo`.
- Nama terdiri dari 2–150 karakter; alamat 5–500 karakter; maksud kedatangan 3–1.000 karakter.
- Pegawai tujuan harus ada dan berstatus aktif.
- Foto wajib berformat JPEG, PNG, atau WebP dan berukuran maksimum 5 MB.
- Data valid disimpan dengan ID unik, nomor kunjungan unik, waktu server, dan status `pending`.
- API merespons HTTP `201` beserta data ringkas kunjungan, tanpa menunggu pengiriman notifikasi selesai.
- Input tidak valid merespons HTTP `422` dengan pesan kesalahan per field dan tidak membuat kunjungan parsial.
- Foto tidak tersedia melalui URL publik permanen tanpa otorisasi.

##### US-02 — Mengambil daftar pegawai tujuan

**User Story:** Sebagai pengguna client mobile, saya ingin memilih pegawai dari daftar resmi agar tujuan kunjungan valid.

**Acceptance Criteria:**

- API hanya menampilkan pegawai aktif.
- Data minimal berisi ID, nama, jabatan, dan unit kerja.
- Daftar mendukung pencarian nama, pagination, dan pengurutan alfabetis.
- Pegawai nonaktif tidak dapat dipakai untuk kunjungan baru, tetapi tetap tampil pada riwayat lama.

##### US-03 — Melihat tamu berdasarkan pegawai tujuan

**User Story:** Sebagai pegawai, saya ingin melihat kunjungan yang ditujukan kepada saya agar dapat menindaklanjutinya.

**Acceptance Criteria:**

- Pegawai terautentikasi hanya dapat melihat kunjungan dengan `employee_id` miliknya; admin dapat memfilter pegawai mana pun.
- Daftar mendukung filter status, rentang tanggal, pencarian nama/nomor kunjungan, pagination, dan urutan terbaru.
- Respons memuat identitas tamu, maksud kedatangan, waktu kunjungan, status, alasan keputusan, dan URL foto berumur terbatas.
- Percobaan mengakses kunjungan pegawai lain merespons HTTP `403`.

##### US-04 — Mengirim notifikasi kedatangan

**User Story:** Sebagai pegawai tujuan, saya ingin menerima notifikasi ketika ada tamu agar dapat segera memberikan keputusan.

**Acceptance Criteria:**

- Setelah transaksi kunjungan berhasil, sistem membuat pekerjaan notifikasi asynchronous.
- Kanal MVP ditetapkan melalui konfigurasi tanpa perubahan kode utama. Pilihan final antara push notification dan WhatsApp berstatus `TBD`.
- Payload tidak memuat alamat lengkap atau foto tamu; payload minimal berisi nomor kunjungan, nama tamu, maksud ringkas, dan waktu kedatangan.
- Setiap percobaan mencatat kanal, status, waktu, jumlah percobaan, respons penyedia yang telah disanitasi, dan pesan gagal.
- Pengiriman gagal dicoba ulang sekurang-kurangnya 3 kali dengan jeda bertingkat dan tidak membatalkan data kunjungan.
- Admin dapat melihat status notifikasi pada detail kunjungan.

##### US-05 — Memverifikasi kunjungan

**User Story:** Sebagai pegawai tujuan, saya ingin menerima, menolak, atau meminta tamu menunggu agar petugas mengetahui tindak lanjut kunjungan.

**Acceptance Criteria:**

- Pilihan status hanya `accepted`, `rejected`, atau `waiting`.
- Alasan sepanjang 3–500 karakter wajib untuk `rejected` dan `waiting`.
- Keputusan menyimpan `decided_by`, `decided_at`, status, dan alasan secara atomik.
- Pegawai hanya dapat memutuskan kunjungan yang ditujukan kepadanya; admin tidak mengambil keputusan atas nama pegawai pada MVP.
- Perubahan keputusan setelah keputusan pertama ditolak dengan HTTP `409`; mekanisme koreksi oleh admin berada di luar MVP dan harus meninggalkan audit trail bila dibuat pada fase berikutnya.
- API mengembalikan status terbaru setelah keputusan berhasil.

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

- Dashboard menampilkan jumlah total kunjungan, diterima, ditolak, dan menunggu/pending.
- Data default menggunakan tanggal hari berjalan pada zona waktu `Asia/Makassar`.
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

- Admin dapat membuat dan mengubah pegawai dengan nama, NIP/identifier opsional, jabatan, unit kerja, nomor WhatsApp opsional, identitas perangkat/token push opsional, dan status aktif.
- Sistem menolak NIP/identifier duplikat jika field tersebut diisi.
- Pegawai yang sudah memiliki riwayat kunjungan tidak dapat dihapus permanen; admin hanya dapat menonaktifkannya.
- Daftar mendukung pencarian, filter status, pengurutan, dan pagination.
- Data kontak notifikasi hanya dapat dilihat atau diubah oleh admin yang berwenang.

#### Functional Scope by Release

| Kemampuan | MVP — 5 Agustus 2026 | Operasional — 22 September 2026 |
|---|---:|---:|
| Master pegawai aktif | Ya | Ya |
| Input kunjungan dan unggah foto | Ya | Ya |
| Daftar kunjungan per pegawai | Ya | Ya |
| Verifikasi diterima/ditolak/menunggu | Ya | Ya |
| Satu kanal notifikasi terpilih | Ya | Ya, dengan retry dan monitoring matang |
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
- Registrasi mandiri tamu atau pegawai melalui aplikasi.
- Integrasi biometrik, OCR KTP, pengenalan wajah, QR check-in, atau pencetakan badge.
- Penjadwalan janji temu sebelum tamu datang.
- Pelacakan lokasi tamu atau proses check-out pada MVP.
- Analitik prediktif atau fitur kecerdasan buatan.
- Integrasi ke sistem kepegawaian eksternal.
- Pengiriman notifikasi melalui push dan WhatsApp sekaligus pada MVP.
- Koreksi keputusan kunjungan tanpa audit trail.

#### Assumptions and Open Decisions

| Item | Status/Asumsi | Pemilik keputusan | Batas keputusan |
|---|---|---|---|
| Kanal notifikasi MVP | `TBD`: push notification atau WhatsApp | Product owner + tim teknis | Sebelum implementasi notifikasi MVP |
| Penyedia WhatsApp | `TBD`: WhatsApp Business Platform resmi/BSP | Tim teknis | Sebelum memilih WhatsApp |
| Penyedia push | `TBD`: disarankan Firebase Cloud Messaging jika sesuai client mobile | Tim mobile + backend | Sebelum memilih push |
| Autentikasi pegawai pada client | `TBD`: akun pegawai/token perangkat/SSO | Product owner | Minggu pertama MVP |
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
Client Mobile ───────┐
                    ├── HTTPS ── Laravel Application/API ── Eloquent ── MySQL
Web Admin + Tailwind ┘                    │
                                         ├── Private Object/File Storage (foto)
                                         ├── Queue Worker ── Push/WhatsApp Provider
                                         └── Mail Provider (reset password)
```

- Laravel menyediakan REST API versioned dan halaman admin server-rendered atau komponen web yang tetap berada dalam satu aplikasi.
- Eloquent menjadi satu-satunya lapisan akses data utama ke MySQL; query agregasi boleh menggunakan query builder Laravel untuk efisiensi.
- Pengiriman notifikasi dan pekerjaan berat memakai queue agar request input kunjungan tidak tertahan oleh layanan eksternal.
- Scheduler Laravel menangani pembersihan token kedaluwarsa, retry terjadwal, dan pekerjaan pemeliharaan.
- Foto disimpan pada disk privat lokal atau object storage kompatibel S3 berdasarkan konfigurasi deployment.
- Konfigurasi yang berbeda memungkinkan codebase yang sama direplikasi per kantor tanpa menggabungkan datanya.

#### Proposed Domain Model

| Entitas | Field utama | Catatan |
|---|---|---|
| `users` | id, name, email, password, role, is_active, timestamps | Admin dan, bergantung keputusan autentikasi, akun pegawai |
| `employees` | id, user_id nullable, employee_no nullable, name, position, unit, whatsapp_number encrypted nullable, is_active, timestamps | Master pegawai tujuan; soft delete atau nonaktif |
| `employee_devices` | id, employee_id, provider, device_token encrypted, last_seen_at, revoked_at | Diperlukan bila kanal push digunakan |
| `visits` | id, visit_number, guest_name, address, employee_id, purpose, photo_path, status, reason nullable, decided_by nullable, decided_at nullable, created_at, updated_at | Status: pending, waiting, accepted, rejected |
| `notification_deliveries` | id, visit_id, employee_id, channel, provider_message_id nullable, status, attempts, last_attempt_at, error_message nullable, timestamps | Audit teknis pengiriman |
| `audit_logs` | id, actor_id nullable, action, auditable_type, auditable_id, metadata JSON, ip_address, created_at | Tidak menyimpan password/token/isi foto |
| `password_reset_tokens` | email, token, created_at | Mengikuti mekanisme Laravel |

Indeks minimum: `visits(employee_id, created_at)`, `visits(status, created_at)`, unique `visits.visit_number`, unique `users.email`, serta unique nullable `employees.employee_no` sesuai dukungan MySQL.

#### State Model

```text
pending ──► accepted
   │
   ├──────► rejected
   │
   └──────► waiting
```

Pada MVP, setiap kunjungan menerima satu keputusan final. `waiting` diperlakukan sebagai keputusan operasional yang tampil terpisah pada daftar, sedangkan kartu “menunggu” di dashboard menggabungkan `pending + waiting` dan harus memiliki tooltip/label penjelas. Perubahan dari `waiting` ke `accepted/rejected` menjadi kandidat v1.1 setelah alur bisnis dikonfirmasi.

#### API Contract (Proposed)

Base path: `/api/v1`. Format respons: JSON. Semua tanggal menggunakan ISO 8601 dengan offset zona waktu; penyimpanan database dianjurkan UTC dan penyajian default `Asia/Makassar`.

| Method | Endpoint | Kegunaan | Akses |
|---|---|---|---|
| `POST` | `/visits` | Membuat kunjungan multipart/form-data | Client terotorisasi/rate-limited |
| `GET` | `/employees` | Daftar pegawai aktif | Client terotorisasi |
| `GET` | `/employees/{employee}/visits` | Daftar kunjungan per pegawai | Pegawai terkait/admin |
| `GET` | `/visits/{visit}` | Detail kunjungan | Pegawai terkait/admin |
| `POST` | `/visits/{visit}/decision` | Menerima, menolak, atau menunggu | Pegawai terkait |
| `POST` | `/auth/login` | Login pengguna API | Publik, rate-limited |
| `POST` | `/auth/logout` | Membatalkan token | Terautentikasi |
| `POST` | `/auth/forgot-password` | Memulai reset password | Publik, rate-limited |
| `POST` | `/auth/reset-password` | Menetapkan password baru | Token reset valid |
| `GET` | `/admin/dashboard` | Agregat dashboard | Admin |
| `GET` | `/admin/reports/visits.pdf` | Laporan PDF | Admin |
| CRUD | `/admin/users` | Manajemen admin | Admin |
| CRUD | `/admin/employees` | Manajemen pegawai | Admin |

Ketentuan kontrak:

- Gunakan autentikasi Laravel Sanctum untuk token mobile dan session/cookie aman untuk web admin, kecuali hasil spike teknis menetapkan solusi lain.
- Gunakan Laravel API Resources agar bentuk respons konsisten.
- Pagination default 20, maksimum 100 item per halaman.
- Error menggunakan struktur konsisten: `message`, `errors`, dan `request_id`.
- Terapkan idempotency key pada `POST /visits` untuk mencegah data ganda ketika client mengulang request akibat koneksi terputus.
- Dokumentasikan API dengan OpenAPI 3.1 dan sediakan koleksi pengujian sebelum integrasi client mobile.

#### Integration Points

- **MySQL:** penyimpanan transaksi, master data, status, dan audit.
- **Private storage:** penyimpanan foto tamu; akses melalui signed URL berumur maksimum 10 menit atau streaming setelah otorisasi.
- **Push provider:** `TBD`, kandidat Firebase Cloud Messaging; membutuhkan token perangkat dan penanganan token tidak valid.
- **WhatsApp provider:** `TBD`, wajib memakai penyedia resmi dan template pesan yang disetujui bila WhatsApp dipilih.
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
- Terapkan role-based access dan pemeriksaan kepemilikan kunjungan pada setiap endpoint pegawai.
- Nomor WhatsApp dan token perangkat dienkripsi at rest menggunakan mekanisme aplikasi; secret provider hanya berada di environment/secret manager.
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
- **Feature/API test:** seluruh endpoint sukses/gagal, autentikasi, otorisasi antarpegawai, pagination, filter, idempotency, dan upload file.
- **Integration test:** sandbox penyedia notifikasi, queue retry/failure, storage privat, email reset, dan database migration.
- **Security test:** CSRF, IDOR, brute-force rate limit, file upload berbahaya, akses signed URL kedaluwarsa, serta kebocoran field sensitif.
- **Performance test:** 50 pengguna bersamaan, dataset 100.000 kunjungan, dengan hasil p95 dibandingkan target NFR.
- **PDF test:** verifikasi filter dan total secara otomatis, lalu pemeriksaan visual sampel laporan panjang, karakter Indonesia, page break, header, dan footer.
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

- Hari 1–2: finalisasi kanal notifikasi, autentikasi pegawai, kontrak OpenAPI, skema data, dan wireframe halaman inti.
- Hari 3–7: implementasi master pegawai, input kunjungan/foto, daftar per pegawai, verifikasi, login admin, dan queue notifikasi.
- Hari 8–10: dashboard dasar, PDF dasar, halaman Tailwind inti, serta pengujian API dan policy.
- Hari 11–12: integrasi sandbox client/notifikasi dan perbaikan defect.
- Hari 13–14: deployment staging, UAT MVP, dokumentasi, dan demo pada 5 Agustus 2026.

##### v1.1 / Production Hardening — 6 Agustus–22 September 2026

- Menyelesaikan forgot password dan manajemen user melalui web.
- Mematangkan laporan PDF, filter dashboard, audit log, retry/monitoring notifikasi, dan responsive/accessibility QA.
- Melakukan performance test, security test, backup restore drill, pelatihan admin, migrasi konfigurasi produksi, dan UAT final.
- Go-live bertahap: pilot internal 3–5 hari, evaluasi, lalu penggunaan kantor penuh paling lambat 22 September 2026.

##### v2.0 — Setelah evaluasi operasional

- Dukungan perubahan status dari `waiting` ke keputusan akhir dengan histori status.
- Check-out tamu, QR code, appointment, atau badge bila dibuktikan perlu oleh data operasional.
- Kanal notifikasi cadangan/fallback dan dashboard delivery analytics.
- Paket deployment terdokumentasi untuk replikasi kantor lain tanpa fitur multi-tenant.

#### Technical and Product Risks

| Risiko | Probabilitas | Dampak | Mitigasi |
|---|---|---|---|
| Kanal notifikasi belum dipilih | Tinggi | Tinggi terhadap target 2 minggu | Putuskan pada hari pertama; buat abstraction interface dan satu adapter MVP |
| WhatsApp memerlukan akun, template, dan persetujuan provider | Sedang–tinggi | Tinggi | Gunakan provider resmi; mulai proses persetujuan segera; pilih push untuk MVP bila lead time tidak cukup |
| Autentikasi pegawai belum didefinisikan | Tinggi | Tinggi/risiko akses data | Putuskan model akun/token pada minggu pertama dan uji authorization policy secara eksplisit |
| Target MVP dua minggu sangat ketat | Tinggi | Tinggi | Batasi pada alur inti, satu kanal, dashboard/PDF dasar; pindahkan hardening ke v1.1 |
| Foto memperbesar storage dan backup | Sedang | Sedang | Batasi 5 MB, kompresi terkontrol, monitoring kapasitas, dan kebijakan retensi |
| Layanan notifikasi eksternal gagal atau lambat | Sedang | Sedang | Queue, retry bertingkat, failure log, alert, dan status delivery pada admin |
| Data pribadi terekspos melalui URL/log/PDF | Sedang | Tinggi | Storage privat, signed URL, redaksi log/payload, RBAC, audit ekspor, dan minimisasi PDF |
| Data dashboard dan PDF tidak konsisten | Sedang | Tinggi | Satu service/query filter bersama dan automated reconciliation tests |
| Replikasi kantor menghasilkan konfigurasi tidak konsisten | Sedang | Sedang | Environment template, deployment checklist, seeder konfigurasi kantor, dan versioned migrations |
| Kehilangan data akibat kegagalan server | Rendah–sedang | Tinggi | Backup harian database+foto, restore drill, monitoring backup, dan prosedur insiden |

#### Dependencies

- Persetujuan product owner atas scope dan alur keputusan kunjungan.
- Data awal pegawai PTA Manado beserta jabatan, unit, dan kontak/perangkat notifikasi.
- Akun serta credential penyedia push/WhatsApp yang sah.
- SMTP atau layanan email untuk forgot password.
- Server produksi dengan PHP/Laravel runtime, MySQL, queue worker, scheduler, HTTPS, storage, backup, dan monitoring.
- Tim/framework mobile menyepakati OpenAPI, autentikasi, format foto, dan mekanisme registrasi token perangkat.

#### Release Gates

**Gate MVP:** alur input → notifikasi antrean → daftar pegawai → keputusan berjalan di staging; dashboard dan PDF dasar benar; policy akses lulus; UAT MVP disetujui.

**Gate Produksi:** seluruh acceptance criteria operasional selesai; provider produksi aktif; backup/restore terbukti; observability aktif; security dan performance test lulus; kebijakan retensi disepakati; tidak ada defect Severity 1/2; UAT final ditandatangani.

#### Recommended Immediate Decisions

1. Pilih kanal MVP. Push notification lebih mungkin memenuhi jadwal dua minggu bila client mobile dan FCM siap; WhatsApp dipilih hanya jika akun bisnis, nomor, template, dan provider resmi sudah tersedia.
2. Tetapkan cara pegawai login dan hubungan `users` dengan `employees` sebelum endpoint daftar/verifikasi dibangun.
3. Serahkan data pegawai awal dan identitas visual kantor pada hari pertama.
4. Sepakati definisi `waiting`: keputusan akhir pada MVP atau status sementara yang boleh berubah. PRD ini menganggapnya keputusan akhir untuk menjaga scope dua minggu.
5. Tetapkan retensi foto dan data kunjungan sebelum go-live produksi.

---

### Appendix A — KPI Measurement

| KPI | Sumber data | Frekuensi | Cara hitung |
|---|---|---|---|
| Kunjungan terdokumentasi | `visits` dan audit request | Harian | Jumlah record valid dibanding transaksi sukses client |
| Distribusi status | `visits.status` | Harian/bulanan | Count per status dalam periode dan zona waktu yang sama |
| Kecepatan pemrosesan notifikasi | `notification_deliveries` + queue metrics | Harian | Selisih `visits.created_at` dengan waktu percobaan pertama; hitung p95 dan persentase <60 detik |
| Latensi API | APM/structured log | Harian | p95 durasi endpoint, pisahkan upload dan non-upload |
| Konsistensi laporan | Test suite + rekonsiliasi | Setiap rilis | Total dashboard/PDF harus sama dengan query detail untuk filter identik |

### Appendix B — Severity Definition

- **Severity 1:** kehilangan/kebocoran data, sistem tidak dapat digunakan, atau kontrol autentikasi/otorisasi dapat dilewati.
- **Severity 2:** alur utama input, notifikasi, keputusan, dashboard, atau laporan gagal tanpa workaround yang layak.
- **Severity 3:** fungsi pendukung terganggu tetapi terdapat workaround.
- **Severity 4:** masalah kosmetik atau penyempurnaan minor yang tidak menghambat tugas.
