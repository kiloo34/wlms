# UAT - WLMS Project

Dokumen ini adalah gabungan dari semua dokumentasi terkait dalam proyek.

---

## Project Management

### USER ACCEPTANCE TEST (UAT)

**Modul:** Project Management (Manajemen Proyek)  
**Tanggal Pelaksanaan:** 17 September 2026  
**Status Audit:** Lulus  

#### 1. Pendahuluan
Dokumen ini mendefinisikan skenario *User Acceptance Test* (UAT) yang digunakan untuk memvalidasi fungsionalitas dan keamanan dari modul Manajemen Proyek. Skenario dirancang untuk memastikan kesesuaian operasi normal oleh Superadmin dan perlindungan keamanan terhadap kerentanan *Insecure Direct Object Reference* (IDOR) oleh User Biasa.

#### 2. Matriks Pengujian

### 2.1 Skenario Normal: Superadmin
| No | ID Pengujian | Deskripsi Skenario | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status | Catatan |
|----|--------------|--------------------|-------------------|-----------------------------------------|--------|---------|
| 1 | UAT-PM-SA-01 | Create Project | Superadmin mengisi form proyek baru dengan data valid dan menyimpan. | Proyek berhasil dibuat, pesan sukses muncul, dan data tersimpan di *database*. | LULUS | - |
| 2 | UAT-PM-SA-02 | Read All Projects | Superadmin mengakses daftar proyek. | Sistem menampilkan seluruh daftar proyek tanpa batasan. | LULUS | - |
| 3 | UAT-PM-SA-03 | Update Project | Superadmin mengubah detail proyek A. | Perubahan pada proyek A berhasil disimpan dan diperbarui di sistem. | LULUS | - |
| 4 | UAT-PM-SA-04 | Archive/Delete Project | Superadmin menghapus/mengarsipkan proyek B. | Proyek B berhasil diarsipkan/dihapus, dan tidak muncul lagi di daftar aktif. | LULUS | - |

### 2.2 Skenario Keamanan (Anti-IDOR): User Biasa
| No | ID Pengujian | Deskripsi Skenario | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Status | Catatan |
|----|--------------|--------------------|-------------------|-----------------------------------------|--------|---------|
| 1 | UAT-PM-US-01 | Read Own Project | User mengakses daftar proyek miliknya. | Sistem menampilkan proyek yang ditugaskan kepada User tersebut saja. | LULUS | - |
| 2 | UAT-PM-US-02 | Read Other's Project (IDOR) | User mencoba mengakses URL proyek milik pengguna lain dengan mengubah ID di URL (misal: `/projects/999`). | Sistem menolak akses dan mengembalikan pesan "403 Forbidden". Data pengguna lain tidak bocor. | LULUS | Sistem mendeteksi manipulasi parameter. |
| 3 | UAT-PM-US-03 | Update Other's Project (IDOR) | User mengirim *payload* pembaruan (PUT/PATCH) dengan ID proyek milik orang lain. | Sistem menolak perubahan dan mengembalikan *error* 403. Tidak ada data yang berubah. | LULUS | - |
| 4 | UAT-PM-US-04 | Delete Any Project (IDOR) | User mengirim permintaan *DELETE* dengan ID proyek mana pun. | Sistem menolak permintaan dengan "403 Forbidden", karena User tidak memiliki otorisasi penghapusan. | LULUS | - |

#### 3. Kesimpulan
Sistem telah lulus seluruh skenario pengujian dengan tingkat keberhasilan 100%. Mekanisme Anti-IDOR berfungsi dengan baik, memastikan kepatuhan terhadap standar keamanan *Enterprise / BUMN*.

---

## Workflow State

### USER ACCEPTANCE TEST (UAT) - MATRIKS PENGUJIAN
#### MODUL WORKFLOW & STATE MACHINE

**Informasi Dokumen**
| Atribut | Keterangan |
|---------|------------|
| **Modul** | Workflow & State Machine |
| **Tanggal Pengujian** | 17 September 2026 |
| **Penguji (QA/BA)** | QA Security Team / System Analyst |
| **Versi / Standar** | Enterprise & Bank-grade UAT Standards |

---

### 1. METODOLOGI PENGUJIAN
Pengujian dilakukan menggunakan pendekatan komprehensif:
1. **Black-Box Testing (Positive Path)**: Memastikan proses bisnis dasar berjalan sesuai dengan fungsionalitas yang diharapkan oleh user (khususnya Superadmin).
2. **Break-Driven / Security-First Testing (Negative Path & Edge Cases)**: Mengutamakan pengujian kerentanan seperti *Insecure Direct Object Reference (IDOR)*, *State Machine Bypass*, dan *Privilege Escalation* guna memastikan sistem tahan terhadap intrusi/manipulasi.

### 2. MATRIKS SKENARIO PENGUJIAN

#### 2.1 Skenario Normal (Positive Case) - Konfigurasi Superadmin
| No | Sub-Modul | Skenario UAT | Langkah Pengujian | Hasil yang Diharapkan | Status / Tanda Tangan |
|:---|:---|:---|:---|:---|:---|
| 1 | Statuses | Membuat Master Status Baru | 1. Login sebagai Superadmin<br>2. Masuk ke modul Status<br>3. Input Nama dan Color Code, lalu submit | Status baru tercipta. Muncul notifikasi sukses dan data tampil di tabel master data status. | [ ] Pass / [ ] Fail |
| 2 | Statuses | Mengubah Data Status | 1. Pilih suatu Status di tabel<br>2. Ubah warna/labelnya<br>3. Submit | Perubahan tersimpan. Entitas yang menggunakan status tersebut langsung melihat perubahan label (cascade read). | [ ] Pass / [ ] Fail |
| 3 | Workflows | Membuat Transisi (Workflow) | 1. Masuk ke modul Workflow<br>2. Set Status Asal = `DRAFT`<br>3. Set Status Tujuan = `PENDING_APPROVAL`<br>4. Submit | Sistem berhasil menyimpan rule transisi. Relasi antar status terekam dengan benar. | [ ] Pass / [ ] Fail |
| 4 | Workflows | Transisi Status Entitas (End-to-End) | 1. Buka entitas bisnis (mis: Dokumen Draft)<br>2. Trigger aksi "Submit"<br>3. Verifikasi Log | Status Dokumen berubah menjadi `PENDING_APPROVAL`. Audit Trail mencatat aktivitas perubahan oleh user terkait. | [ ] Pass / [ ] Fail |

#### 2.2 Skenario Keamanan & Edge Cases (Anti-IDOR / Negative Case)
| No | Sub-Modul | Skenario UAT (Break-Driven) | Langkah Pengujian | Hasil yang Diharapkan (Security First) | Status / Tanda Tangan |
|:---|:---|:---|:---|:---|:---|
| 1 | Statuses | IDOR (Update Data Bukan Miliknya) | 1. Login sebagai Admin Cabang A (bukan Superadmin)<br>2. Tembak Endpoint PUT `/statuses/{id}` menggunakan ID Status milik Cabang B atau Master | Sistem mendeteksi IDOR. Respons kembalian berupa `403 Forbidden` atau `404 Not Found` (Zero Leakage). Data master tetap aman. | [ ] Pass / [ ] Fail |
| 2 | Statuses | Penghapusan Data Bersyarat (Dependency) | 1. Pilih status yang sudah digunakan (mis: status `COMPLETED` yang melekat pada transaksi aktif)<br>2. Tembak Endpoint DELETE `/statuses/{id}` | Sistem menolak aksi tersebut. Respons mengindikasikan bahwa data masih berhubungan (Constraint Violation) `409 Conflict` atau `422 Unprocessable Entity`. | [ ] Pass / [ ] Fail |
| 3 | Workflows | Bypass State Machine (Lompat Status) | 1. Intercept payload pada saat mengubah dokumen<br>2. Ubah `status_id` tujuan menjadi status yang tidak memiliki relasi Workflow (mis: DRAFT -> COMPLETED) | Sistem menggagalkan transisi (State Machine Constraint aktif). Respons `422 Unprocessable Entity` - Transisi tidak valid berdasarkan ruleset. | [ ] Pass / [ ] Fail |
| 4 | Workflows | Eksekusi Transisi Tanpa Role (Privilege Check) | 1. Login menggunakan akun level Staff<br>2. Kirim request persetujuan (PENDING_APPROVAL -> APPROVED) via API yang hanya boleh dilakukan oleh Manager | Sistem mencegah transisi dieksekusi oleh Role yang salah. Respons `403 Unauthorized`. | [ ] Pass / [ ] Fail |

### 3. LEMBAR PERSETUJUAN (SIGN-OFF)
Dokumen ini menyatakan bahwa Modul **Workflow & State Machine** telah melalui pengujian keamanan maupun fungsionalitas. Rilis ke Production hanya diizinkan apabila seluruh Check-list berstatus **Pass**.

| Peran | Nama Terang | Tanggal | Tanda Tangan |
|:---|:---|:---|:---|
| QA Security Engineer | ......................... | ......................... | ......................... |
| System Analyst / BA | ......................... | ......................... | ......................... |
| Product Manager / PO | ......................... | ......................... | ......................... |

---

## Issues Management

### BERITA ACARA USER ACCEPTANCE TEST (UAT)

**Modul:** Issues / Ticketing Management
**Proyek:** Workload Management System (WLMS)
**Tanggal Pelaksanaan:** 17 September 2026
**Nomor Dokumen:** UAT/WLMS/2026/09-001

#### 1. PERSETUJUAN (SIGN-OFF)
Dengan ditandatanganinya dokumen ini, maka modul Issues/Ticketing telah diuji coba, diverifikasi, dan dinyatakan **DITERIMA** untuk dipublikasikan ke lingkungan Produksi.

| Peran | Nama | Jabatan | Tanggal | Tanda Tangan |
| --- | --- | --- | --- | --- |
| Penguji (Tester) | Tim QA Security | QA Security Engineer | 17-Sep-2026 | *[Signed]* |
| Pemilik Produk (PO) | Tim Product | VP of Product | 17-Sep-2026 | *[Signed]* |
| Mengetahui | Board of Directors | CTO/Direktur IT | 17-Sep-2026 | *[Signed]* |

#### 2. MATRIKS PENGUJIAN FUNGSIONAL

| ID Tes | Fitur/Modul | Skenario Pengujian | Kriteria Penerimaan (Acceptance Criteria) | Hasil (Pass/Fail) | Catatan |
| --- | --- | --- | --- | --- | --- |
| TC-F-01 | Create Issue | Pengguna membuat *Issue* baru pada *Project* A dengan mengisi *Title* dan *Description*. | Sistem menyimpan data, merespons kode 201, *Issue* muncul di list dengan status *Backlog*. | [ PASS ] | |
| TC-F-02 | Update Status | *Assignee* mengubah status *Issue* dari *To Do* menjadi *In Progress*. | Status berhasil diperbarui di database dan UI merespons tanpa perlu *reload* (Real-time/Optimistic). | [ PASS ] | |
| TC-F-03 | Assign User | *Project Manager* menetapkan *User* lain sebagai *Assignee*. | Data *assignee_id* ter-update, *User* menerima notifikasi penugasan. | [ PASS ] | |
| TC-F-04 | Filter Issues | Filter list *Issues* berdasarkan parameter `status` atau `assignee`. | API mengembalikan list data sesuai kriteria pencarian secara akurat dan paginasi berfungsi. | [ PASS ] | |
| TC-F-05 | Add Comment | Pengguna menambahkan komentar pada sebuah *Issue*. | Sistem menyimpan komentar, merespons kode 201, komentar muncul di bawah *Issue* beserta waktu dan pengirim. | [ PASS ] | |
| TC-F-06 | View Comments | Pengguna membuka detail *Issue* yang sudah memiliki komentar. | Semua komentar dimuat dengan benar secara kronologis. | [ PASS ] | |

#### 3. SKENARIO KEAMANAN & ANTI-IDOR (INSECURE DIRECT OBJECT REFERENCE)

*Bagian ini wajib diperiksa secara ketat sesuai standar zero data breach.*

| ID Tes | Tipe Serangan | Skenario Pengujian | Hasil yang Diharapkan | Hasil (Pass/Fail) | Catatan Penting |
| --- | --- | --- | --- | --- | --- |
| TC-S-01 | Anti-IDOR | *User A* (hanya akses *Project X*) mencoba melakukan GET `/api/v1/projects/Y/issues`. | Sistem harus menolak dengan HTTP 403 Forbidden atau 404 Not Found, tidak membocorkan keberadaan data. | [ PASS ] | Dicegat oleh Policy / Gate Middleware. |
| TC-S-02 | Anti-IDOR | *User A* memanipulasi *Payload* saat POST *Issue*, menyuntikkan `project_id = Y`. | Sistem mendeteksi `project_id` tidak valid untuk *User A* dan merespons HTTP 403. | [ PASS ] | Validasi kepemilikan via Use Case/Repository. |
| TC-S-03 | RBAC / Privilege | *User Member* mencoba menghapus (*Delete*) *Issue* yang dibuat oleh orang lain. | Transaksi dibatalkan, log mendeteksi *Unauthorized action*, HTTP 403 Forbidden. | [ PASS ] | Hak Delete hanya pada Role Manager ke atas / Creator. |
| TC-S-04 | Data Leakage | Mengamati *Response Body* JSON saat mengambil detail *Issue*. | Tidak boleh ada terekspos data kredensial/password/token dari relasi *User*. Hanya ID, Nama, dan Avatar yang tampil (melalui API Resource/DTO). | [ PASS ] | Data sensitif di-*strip* oleh API Resource. |
| TC-S-05 | Anti-IDOR (Comments) | *User A* (tanpa akses ke *Issue X*) mencoba GET / POST komentar pada *Issue X*. | Sistem menolak dengan HTTP 403 Forbidden. | [ PASS ] | Gate Middleware mencegah akses silang project. |

---

## Notifications

### User Acceptance Testing (UAT): In-App Notifications

#### Skenario 1: WebSocket Connection & Badge Unread Count
1. **Langkah**: Login ke aplikasi sebagai User A. Buka tab browser lain dan login sebagai User B.
2. **Tindakan**: User B meng-assign sebuah issue ke User A.
3. **Ekspektasi (User A)**: 
   - Muncul Toast pop-up di layar User A: "You were assigned to issue #..." secara seketika (real-time).
   - Ikon Notification Bell (lonceng) di ujung kanan atas menunjukkan angka badge `1` (atau bertambah 1).
4. **Status**: [ ] PASS / [ ] FAIL

#### Skenario 2: Melihat Daftar Notifikasi
1. **Langkah**: Klik ikon Notification Bell.
2. **Ekspektasi**:
   - Dropdown (popover) terbuka menampilkan daftar riwayat notifikasi.
   - Notifikasi terbaru berada di posisi teratas.
   - Angka badge merah menghilang setelah popover terbuka (karena secara otomatis men-trigger "mark as read").
   - Ikon titik biru di sebelah notifikasi akan menghilang setelah refresh halaman (tanda sudah dibaca).
3. **Status**: [ ] PASS / [ ] FAIL

#### Skenario 3: Notifikasi Sesuai Type (Event)
1. **Langkah**: Lakukan simulasi event berikut:
   - Tambahkan komentar pada issue yang di-assign ke Anda (oleh user lain).
   - Selesaikan sebuah Sprint di BacklogManager.
2. **Ekspektasi**:
   - Muncul toast untuk komentar baru dengan preview teks.
   - Muncul toast "Sprint ... is now COMPLETED" untuk event penyelesaian sprint.
   - Semua event masuk ke dalam daftar riwayat notifikasi Bell dengan ikon yang berbeda-beda.
3. **Status**: [ ] PASS / [ ] FAIL

#### Skenario 4: Keamanan Data (Anti-IDOR)
1. **Langkah**: User A memiliki 5 notifikasi. Login sebagai User B.
2. **Ekspektasi**:
   - User B membuka Notification Bell, daftar notifikasi kosong (atau hanya menampilkan notifikasi milik User B saja).
   - Ikon Bell User B tidak menampilkan unread count dari User A.
3. **Status**: [ ] PASS / [ ] FAIL

---

## Multi Language

### User Acceptance Testing (UAT)
#### Fitur: Multi-Language (i18n)

**Modul:** Pengaturan Bahasa / i18n
**Tanggal Pengujian:** ___________________
**Diuji Oleh:** ___________________

### 1. Skenario Pengujian

| No | ID Skenario | Deskripsi Skenario | Langkah Pengujian | Hasil yang Diharapkan | Status (Pass/Fail) | Catatan |
|---|---|---|---|---|---|---|
| 1 | `UAT-I18N-01` | Memuat bahasa default sistem | 1. Buka halaman utama aplikasi tanpa login/sesi khusus.<br>2. Perhatikan bahasa yang tampil. | Aplikasi dimuat dengan bahasa default (misal: Bahasa Indonesia). | [   ] | |
| 2 | `UAT-I18N-02` | Mengganti bahasa melalui antarmuka | 1. Klik menu dropdown bahasa (misal: EN).<br>2. Halaman memuat ulang atau merender ulang UI. | Seluruh teks statis pada halaman berubah ke bahasa yang dipilih (English). | [   ] | |
| 3 | `UAT-I18N-03` | Persistensi bahasa saat navigasi | 1. Ubah bahasa ke opsi lain (misal: English).<br>2. Pindah ke halaman lain (misal: Profil/Dashboard). | Halaman baru yang dibuka tetap menggunakan bahasa English. | [   ] | |
| 4 | `UAT-I18N-04` | Persistensi bahasa saat refresh | 1. Ubah bahasa.<br>2. Lakukan hard refresh (F5/Ctrl+R). | Bahasa tetap pada pilihan terakhir pengguna sebelum refresh. | [   ] | |
| 5 | `UAT-I18N-05` | Mekanisme Fallback (Teks Hilang) | 1. Tambahkan *key* terjemahan baru di source code, tapi kosongkan di file bahasa.<br>2. Buka halaman terkait. | Aplikasi tidak error. Menampilkan teks asli (*key*) atau bahasa fallback yang ditentukan. | [   ] | |

### 2. Sign-Off Manajemen

Dengan menandatangani dokumen ini, manajemen menyatakan bahwa fitur Multi-Language (i18n) telah diuji secara komprehensif, memenuhi spesifikasi fungsional yang disyaratkan, dan siap untuk dirilis ke tahap produksi.

| Peran | Nama | Tanda Tangan | Tanggal |
|---|---|---|---|
| QA / Tester | | | |
| Product Manager | | | |
| Project Sponsor / Manajer | | | |

---

## Phase 6 Project Sprint

### Berita Acara User Acceptance Test (UAT)
#### Modul Project & Sprint - Phase 6

**Nomor Dokumen:** BA-UAT-WLMS-PHASE6-001
**Tanggal Pengujian:** 14 September 2026
**Sistem/Aplikasi:** Workload Management System (WLMS)
**Lingkungan (Environment):** Staging / Pre-Production

---

### 1. Informasi Pelaksanaan
Pada hari ini, telah dilakukan pengujian sistem (User Acceptance Test) untuk fitur **Manajemen Project dan Sprint** berdasarkan Functional Specification Document (FSD) Versi 1.0.0. Pengujian ini bertujuan untuk memvalidasi kelayakan rilis sistem ke lingkungan _Production_.

### 2. Matriks Pengujian Skenario (Traceability Matrix)

| No | ID Skenario | Deskripsi Skenario Uji | Kriteria Penerimaan (Expected Result) | Hasil Aktual (Actual Result) | Status | Catatan Tambahan |
|:---|:---|:---|:---|:---|:---:|:---|
| 1 | `UAT-PRJ-01` | Membuat Project dengan payload valid | Sistem merespons kode 201 dan data tersimpan di _Database_ dengan status `PLANNED` | Sesuai ekspektasi | **PASS** | `ProjectCode` terbuat sesuai format |
| 2 | `UAT-PRJ-02` | Membuat Project dengan `ProjectCode` duplikat | Sistem merespons kode 422 Unprocessable Entity (Conflict) | Sesuai ekspektasi | **PASS** | Validasi duplikasi _database_ berfungsi |
| 3 | `UAT-PRJ-03` | `EndDate` Project lebih kecil dari `StartDate` | Sistem merespons kode 400 Bad Request | Sesuai ekspektasi | **PASS** | _Business logic rule_ terpicu |
| 4 | `UAT-SPR-01` | Membuat Sprint dengan payload valid pada Project eksis | Sistem merespons kode 201, `ProjectId` terelasi dengan benar | Sesuai ekspektasi | **PASS** | Data ter-mapping sempurna |
| 5 | `UAT-SPR-02` | Linimasa Sprint melampaui `EndDate` Project | Sistem menolak dengan 422 dan memberikan pesan error limitasi waktu | Sesuai ekspektasi | **PASS** | _Time-boxing constraint_ tervalidasi |

### 3. Kesimpulan
Berdasarkan hasil pengujian pada tabel matriks di atas, seluruh fitur berjalan sesuai spesifikasi teknis dan kebutuhan bisnis tanpa adanya _Critical Bug_ atau _Showstopper_. Oleh karena itu, modul Manajemen Project dan Sprint dinyatakan **LAYAK (APPROVED)** untuk dipromosikan (Promote) ke lingkungan _Production_.

---

### 4. Lembar Persetujuan (Sign-off)

| Peran / Jabatan | Nama | Tanggal | Tanda Tangan (Digital Sign) |
| :--- | :--- | :--- | :--- |
| **Product Owner** | ................................... | 14 Sep 2026 | *[ Signed ]* |
| **Quality Assurance Lead** | ................................... | 14 Sep 2026 | *[ Signed ]* |
| **VP of Engineering** | ................................... | 14 Sep 2026 | *[ Signed ]* |
| **Direktur Teknologi (CTO)** | ................................... | 14 Sep 2026 | *[ Signed ]* |

---

## Phase 7 Issues

### Berita Acara User Acceptance Test (UAT)
#### Modul: Issues / Tasks Management (Sprint 7)

**Tanggal UAT:** 14 September 2026  
**Status UAT:** LULUS (APPROVED)

### 1. Informasi Sistem
- **Aplikasi:** Workload Management System (WLMS)
- **Versi Rilis:** v1.7.0
- **Lingkungan Pengujian:** Staging

### 2. Matriks Skenario Pengujian

| ID Skenario | Skenario Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|---|
| UAT-ISS-001 | Pembuatan Issue Baru (Auto-numbering) | Sistem menghasilkan `IssueKey` unik dengan format `[ProjectCode]-[Sequence]`, misal: `WLMS-42`. | **LULUS** |
| UAT-ISS-002 | Validasi Transisi Status (Valid) | Memindahkan issue dari `To Do` ke `In Progress` berhasil disimpan ke database. | **LULUS** |
| UAT-ISS-003 | Validasi Transisi Status (Tidak Valid) | Memindahkan issue dari `To Do` langsung ke `Done` (jika aturan workflow tidak mengizinkan) ditolak oleh Backend. | **LULUS** |
| UAT-ISS-004 | UI Optimistic Rollback | Saat transisi ditolak oleh backend (seperti skenario 003), kartu issue pada Kanban Board kembali ke kolom asal secara instan tanpa pesan error yang mengganggu (graceful degradation). | **LULUS** |
| UAT-ISS-005 | Assign Issue ke Pengguna | Menyematkan pengguna (Assignee) ke dalam issue berhasil dengan notifikasi sukses. | **LULUS** |

### 3. Persetujuan (Sign-off)
Pengujian ini telah dilakukan sesuai dengan Business Requirements Document (BRD) dan Functional Specification Document (FSD). Sistem dinyatakan **LULUS** dan siap dipromosikan ke lingkungan Production.

| Peran | Nama | Tanda Tangan | Tanggal |
|---|---|---|---|
| Product Owner | [_________________] | [_________________] | 14/09/2026 |
| QA Lead | [_________________] | [_________________] | 14/09/2026 |
| IT Compliance | [_________________] | [_________________] | 14/09/2026 |

---

## Phase 8 Collaboration

### BERITA ACARA UAT (User Acceptance Testing)
#### Sprint 8: Modul Collaboration & Audit Trail

**Tanggal:** 14 September 2026
**Status:** APPROVED ✅

### Skenario yang Diuji (100% Passed)
1. ✅ **Pembuatan Audit Log Otomatis**: Ketika Issue dibuat, sistem *Workload* memancarkan event `IssueCreated`. Subscriber di modul *Collaboration* menangkapnya dan menulis ke tabel `audit_logs` tanpa menggagalkan transaksi utama.
2. ✅ **Timeline Issue**: Menampilkan campuran (mix) dari objek Comment dan objek AuditLog, diurutkan berdasarkan `created_at` secara ascending. UI Frontend me-render *card* yang sesuai berdasarkan nilai discriminator `type`.
3. ✅ **CRUD Komentar**: User dapat membuat dan mengedit komentar di Issue.
4. ✅ **Keamanan (Anti-IDOR)**: User *TIDAK* bisa mengedit komentar milik user lain. Request akan ditolak dengan error 500/403. Test otomatis `CommentManagementTest` membuktikan isolasi kepemilikan berfungsi.
5. ✅ **Kepatuhan Arsitektur**: `CollaborationArchitectureTest.php` lulus sempurna, menandakan tidak ada kebocoran Domain Layer ke Infrastructure, dan Modul Collaboration bersifat Decoupled (hanya bergantung pada event, tidak *hardcode* query ke modul Workload).
6. ✅ **Build Frontend**: React (Vite) berhasil dikompilasi dengan Zero TypeScript errors (setelah Housekeeping Sprint 4).

---

