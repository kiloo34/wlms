# BERITA ACARA USER ACCEPTANCE TEST (UAT)

**Modul:** Issues / Ticketing Management
**Proyek:** Workload Management System (WLMS)
**Tanggal Pelaksanaan:** 17 September 2026
**Nomor Dokumen:** UAT/WLMS/2026/09-001

## 1. PERSETUJUAN (SIGN-OFF)
Dengan ditandatanganinya dokumen ini, maka modul Issues/Ticketing telah diuji coba, diverifikasi, dan dinyatakan **DITERIMA** untuk dipublikasikan ke lingkungan Produksi.

| Peran | Nama | Jabatan | Tanggal | Tanda Tangan |
| --- | --- | --- | --- | --- |
| Penguji (Tester) | Tim QA Security | QA Security Engineer | 17-Sep-2026 | *[Signed]* |
| Pemilik Produk (PO) | Tim Product | VP of Product | 17-Sep-2026 | *[Signed]* |
| Mengetahui | Board of Directors | CTO/Direktur IT | 17-Sep-2026 | *[Signed]* |

## 2. MATRIKS PENGUJIAN FUNGSIONAL

| ID Tes | Fitur/Modul | Skenario Pengujian | Kriteria Penerimaan (Acceptance Criteria) | Hasil (Pass/Fail) | Catatan |
| --- | --- | --- | --- | --- | --- |
| TC-F-01 | Create Issue | Pengguna membuat *Issue* baru pada *Project* A dengan mengisi *Title* dan *Description*. | Sistem menyimpan data, merespons kode 201, *Issue* muncul di list dengan status *Backlog*. | [ PASS ] | |
| TC-F-02 | Update Status | *Assignee* mengubah status *Issue* dari *To Do* menjadi *In Progress*. | Status berhasil diperbarui di database dan UI merespons tanpa perlu *reload* (Real-time/Optimistic). | [ PASS ] | |
| TC-F-03 | Assign User | *Project Manager* menetapkan *User* lain sebagai *Assignee*. | Data *assignee_id* ter-update, *User* menerima notifikasi penugasan. | [ PASS ] | |
| TC-F-04 | Filter Issues | Filter list *Issues* berdasarkan parameter `status` atau `assignee`. | API mengembalikan list data sesuai kriteria pencarian secara akurat dan paginasi berfungsi. | [ PASS ] | |
| TC-F-05 | Add Comment | Pengguna menambahkan komentar pada sebuah *Issue*. | Sistem menyimpan komentar, merespons kode 201, komentar muncul di bawah *Issue* beserta waktu dan pengirim. | [ PASS ] | |
| TC-F-06 | View Comments | Pengguna membuka detail *Issue* yang sudah memiliki komentar. | Semua komentar dimuat dengan benar secara kronologis. | [ PASS ] | |

## 3. SKENARIO KEAMANAN & ANTI-IDOR (INSECURE DIRECT OBJECT REFERENCE)

*Bagian ini wajib diperiksa secara ketat sesuai standar zero data breach.*

| ID Tes | Tipe Serangan | Skenario Pengujian | Hasil yang Diharapkan | Hasil (Pass/Fail) | Catatan Penting |
| --- | --- | --- | --- | --- | --- |
| TC-S-01 | Anti-IDOR | *User A* (hanya akses *Project X*) mencoba melakukan GET `/api/v1/projects/Y/issues`. | Sistem harus menolak dengan HTTP 403 Forbidden atau 404 Not Found, tidak membocorkan keberadaan data. | [ PASS ] | Dicegat oleh Policy / Gate Middleware. |
| TC-S-02 | Anti-IDOR | *User A* memanipulasi *Payload* saat POST *Issue*, menyuntikkan `project_id = Y`. | Sistem mendeteksi `project_id` tidak valid untuk *User A* dan merespons HTTP 403. | [ PASS ] | Validasi kepemilikan via Use Case/Repository. |
| TC-S-03 | RBAC / Privilege | *User Member* mencoba menghapus (*Delete*) *Issue* yang dibuat oleh orang lain. | Transaksi dibatalkan, log mendeteksi *Unauthorized action*, HTTP 403 Forbidden. | [ PASS ] | Hak Delete hanya pada Role Manager ke atas / Creator. |
| TC-S-04 | Data Leakage | Mengamati *Response Body* JSON saat mengambil detail *Issue*. | Tidak boleh ada terekspos data kredensial/password/token dari relasi *User*. Hanya ID, Nama, dan Avatar yang tampil (melalui API Resource/DTO). | [ PASS ] | Data sensitif di-*strip* oleh API Resource. |
| TC-S-05 | Anti-IDOR (Comments) | *User A* (tanpa akses ke *Issue X*) mencoba GET / POST komentar pada *Issue X*. | Sistem menolak dengan HTTP 403 Forbidden. | [ PASS ] | Gate Middleware mencegah akses silang project. |

