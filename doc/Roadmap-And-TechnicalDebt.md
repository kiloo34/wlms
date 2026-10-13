# 🗺️ WLMS Product Roadmap & Technical Debt Report

*(Terakhir diperbarui: 13 Oktober 2026)*

Dokumen ini merangkum peta jalan pengembangan fitur masa depan (**Product Roadmap**) serta evaluasi **Technical Debt** terkini pasca penyelesaian Sprint 1–8, fitur Import Spreadsheet, dan penyempurnaan UI/UX Workspace & Project Hub.

---

## Bagian 1: Product Roadmap (Pengembangan Kedepan)

### 1. [DONE] Real Executive & Personal Dashboard (`/dashboard`)
- **Status:** Sudah diselesaikan (`DashboardPageController`, `GetDashboardDataQuery`, dan `resources/js/pages/dashboard.tsx`).
- **Fitur yang Diimplementasikan:**
  - **My Assigned Tasks Widget:** Daftar tugas lintas *workspace* milik pengguna yang sedang login, dikelompokkan berdasarkan urgensi (*Overdue*, *Due Today*, *Upcoming*).
  - **Executive Summary Cards:** Metrik agregat meliputi total proyek aktif, *completion rate*, tugas terbuka saya (*my open tasks*), dan tugas terlambat (*overdue tasks*).
  - **Recent Activity & Audit Stream:** Linimasa aktivitas kolaborasi terbaru dari proyek-proyek yang diikuti pengguna.

### 2. [DONE] Global "My Tasks" Hub (`/issues`)
- **Status:** Sudah diselesaikan (`GlobalIssuesPageController` dan `resources/js/pages/Issues/Index.tsx`).
- **Fitur yang Diimplementasikan:**
  - Mengubah `/issues` menjadi agregator tugas lintas *workspace* (**Global Issue Explorer / My Tasks**).
  - Menyediakan filter terpadu berdasarkan *Workspace*, *Project*, *Status*, *Priority*, *Assignee*, dan rentang *Due Date* dengan dukungan *Bulk Actions* serta *Pagination* (10/20/50/100).

### 3. Export Data (Excel / CSV) & Observabilitas Riwayat Impor
- **Kondisi Saat Ini:** Sistem sudah memiliki fitur *asynchronous import* (`ImportProjectsJob` & `ImportIssuesJob`) di `/admin/import-workload`.
- **Target Pengembangan:**
  - **Export to Excel/CSV:** Menambahkan tombol ekspor pada tabel *All Tasks* (`WorkspaceTasksTable.tsx`) dan *Project Analytics* yang menghormati parameter filter aktif saat itu.
  - **Import History & Notification:** Mencatat status eksekusi *background job* impor ke tabel `import_logs` (jumlah baris sukses, jumlah baris di-*skip*, beserta alasan gagalnya) dan mengirimkan notifikasi *in-app* ketika proses impor selesai.

### 4. Kelengkapan CMS Dokumentasi (`/admin/docs`)
- **Kondisi Saat Ini:** `AdminDocPageController.php` baru mendukung pembacaan daftar (`index`) dan penyuntingan halaman (`edit`, `update`) dari data bawaan *seeder*.
- **Target Pengembangan:**
  - Menambahkan fitur **Create / Delete Category & Page**.
  - Menambahkan pengaturan urutan (*drag-and-drop reordering*) dan status *Draft / Published* langsung dari antarmuka Admin.

### 5. Lampiran File (Attachments) & `@Mention` Kolaborasi
- **Kondisi Saat Ini:** Diskusi tiket dan *audit trail* berjalan berbasis teks melalui modul `Collaboration`.
- **Target Pengembangan:**
  - Mendukung unggah lampiran file/dokumen/gambar pada deskripsi maupun komentar tiket.
  - Menambahkan *autocomplete* `@mention` di `CommentBox.tsx` yang terintegrasi langsung dengan modul `Notification`.

---

## Bagian 2: Evaluasi Technical Debt (Status & Temuan Baru)

### ✅ Technical Debt yang Telah Diselesaikan (Resolved)
1. **Database Migration Mismatch (`org_units`, `org_levels`, `users`):** Sudah diselesaikan. `php artisan migrate:fresh` dan pengujian berbasis SQLite `:memory:` maupun PostgreSQL kini berjalan 100% sinkron.
2. **Security & RBAC Test Matrix:** Sudah diselesaikan. Pengujian *Cross-Division Access*, *Anti-IDOR*, dan *Soft-Deleted Entities* telah ditambahkan di modul `Workload`, `Identity`, `Collaboration`, dan `KnowledgeBase` (133 Pest tests lulus).
3. **Optimasi N+1 Query di Background Import & Hierarki:** Sudah diselesaikan melalui *bulk pre-fetching* (`whereIn`) dan *chunk insert* pada Job Impor serta optimasi *eager loading*.
4. **Atomic Batch Endpoint untuk Bulk Actions (`POST /api/issues/bulk-update`):** Sudah diselesaikan melalui `BulkUpdateIssuesController` (dibungkus `DB::transaction` menggunakan `AssignIssueUseCase` dan `TransitionIssueStatusUseCase`) dan integrasi di `useBulkUpdateIssues` (`useIssues.ts`), menggantikan $N$ request paralel di sisi client.
5. **Filter Auto-Archive Kanban (>30 Hari) di Sisi Backend:** Sudah diselesaikan pada `GetBoardIssuesQuery`, `GetBoardController` (`?include_archived=1`), dan `IssueController@index` (`?exclude_archived_done=1`).
6. **Kebersihan Root Repository & Rute Debug:** Sudah diselesaikan. File *scratch* di *root* dan rute `/api/test-session` di `routes/api.php` telah dihapus, serta `.agents/` telah diabaikan di `.gitignore`.
7. **Konsistensi Clean Architecture (`KnowledgeBase` & `Shared`):** Sudah diselesaikan. `AdminDocPageController` kini memanggil `DocPageAdminService` di layer `Application`, serta `LocaleController` dan `SetLocale` telah dipindahkan ke `App\Shared\Presentation\Http\`.
8. **Lokalisasi Halaman Sekunder (`useTranslate`):** Sudah diselesaikan pada `Issues/Index.tsx`, `settings/workflows.tsx`, dan halaman `auth/*`.
9. **Fix Silent 15-Item Truncation on Kanban & Workspace Issues (`per_page=500` / `limit=500`):** Sudah diselesaikan pada endpoint daftar tiket proyek/Kanban dan *workspace issues* sehingga tampilan multi-view tidak lagi terpotong secara diam-diam pada batas 15 item.
10. **Enforce Workflow Transition & Domain Events on `IssueController::update`:** Sudah diselesaikan dengan menegakkan validasi transisi *workflow* dan pengiriman *domain events* saat status atau *assignee* tiket diperbarui melalui `IssueController::update`.
11. **Expand `AssignIssueController` & `BulkUpdateIssuesController` Authorization:** Sudah diselesaikan dengan memperluas otorisasi agar mencakup peran `Workspace Admin` dan izin `issues:assign`.
12. **Purge Dead `IssueComment*` Classes from `Workload` Module:** Sudah diselesaikan dengan menghapus kelas-kelas `IssueComment*` yang sudah tidak terpakai dari modul `Workload` (komentar kini terpusat di modul `Collaboration`).
13. **Register `tests/Arch` in `phpunit.xml` with `512M` Memory Limit:** Sudah diselesaikan dengan mendaftarkan *test suite* `Arch` (`tests/Arch`) di `phpunit.xml` beserta batas memori `512M` dan memastikan 100% *architecture assertions* lulus.
14. **Fix `assignee_id` Ignored on Issue Creation (`CreateIssueHttpRequest`, `CreateIssueInput`, `CreateIssueUseCase`):** Sudah diselesaikan sehingga pembuatan tiket baru langsung menyimpan *assignee* dan memicu `IssueAssigned` event jika `assignee_id` disertakan.
15. **Enforce Context-Aware RBAC (Anti-IDOR) on `GetWorkspaceIssuesQuery` & `ShowProjectPageController`:** Sudah diselesaikan sehingga pengguna eksternal tanpa relasi ke *workspace*/*project* tidak dapat membaca daftar tugas maupun membuka halaman detail proyek divisi lain.
16. **Serialize Issue Number Generation (`EloquentIssueRepository::nextNumberForProject`):** Sudah diselesaikan dengan mengunci baris proyek (`lockForUpdate()`) di dalam transaksi pembuatan tiket untuk mencegah tabrakan nomor urut tiket (`PROJ-N`).
17. **Auto-Delete Temporary Uploaded Import Files (`ImportProjectsJob` & `ImportIssuesJob`):** Sudah diselesaikan dengan membungkus `Excel::import()` di dalam blok `try / finally` yang menghapus file sementara di `Storage::disk('local')`.
18. **Deduplicate Issue Creation Controller (`CreateIssueController`):** Sudah diselesaikan dengan mendelegasikan `CreateIssueController::__invoke` langsung ke `IssueController::store`.
19. **Prioritize Exact PIC Name Match on Issue Import (`ImportIssuesUseCase`):** Sudah diselesaikan dengan memprioritaskan kecocokan nama lengkap (`strcasecmp === 0`) sebelum *fallback* ke kecocokan parsial (`stripos`).
20. **Fix `WorkspacePolicy::isSuperadmin()` Hardcoded Email Check:** Sudah diselesaikan dengan memeriksa `$user->hasRole('Superadmin')` selain *fallback* email `superadmin@wlms.com`.
21. **Centralize Context-Aware RBAC in `WorkloadAccessChecker`:** Sudah diselesaikan dengan memusatkan pengecekan otorisasi lintas-*workspace*/*project* di `WorkloadAccessChecker` dan menegakkannya pada endpoint Board, Backlog, dan Activity.
22. **Purge Dead `UpdateIssueUseCase`:** Sudah diselesaikan dengan menghapus kelas `UpdateIssueUseCase` yang tidak terpakai beserta komentar dependensi konstruktor di `IssueController`.
23. **Fix CQRS Query Namespace & UseCase Eloquent Coupling (`WorkloadArchitectureTest`):** Sudah diselesaikan dengan memindahkan 5 kelas `*Query` ke `Application\Queries` serta melepaskan ketergantungan langsung `AddWorkspaceMemberUseCase`, `RemoveWorkspaceMemberUseCase`, `CompleteSprintUseCase`, dan `LogWorkUseCase` dari model Eloquent.
24. **Track Mailer Stub with `shortcut:` & Redirect `/users` Placeholder Route:** Sudah diselesaikan dengan mengganti `TODO` di `NotifyAssigneeMailListener` menjadi penanda `shortcut:` terukur serta mengalihkan rute `/users` ke `/settings/rbac`.
25. **Enforce Context-Aware `hasRole` & `hasPermission` in `UserModel`:** Sudah diselesaikan dengan mendukung parameter opsional `$contextType` dan `$contextId` pada `UserModel::hasRole()` dan `UserModel::hasPermission()` sehingga peran ber-cakupan `PROJECT` atau `WORKSPACE` tidak bocor menjadi izin global.
26. **Normalize `context_type` Case & Expose `user_roles` in RBAC User API:** Sudah diselesaikan dengan menormalisasi `context_type` menjadi huruf kapital (`GLOBAL`, `WORKSPACE`, `PROJECT`) pada `UserRoleController` serta menyertakan rincian `user_roles` pada `UserController::index`.
27. **Protect `Superadmin` System Role from Deletion or Renaming (`RoleController`):** Sudah diselesaikan dengan memblokir penghapusan maupun perubahan nama peran sistem `Superadmin` pada `RoleController::update` dan `RoleController::destroy` (`HTTP 422`).
28. **Expand Sprint Lifecycle Endpoints to Dynamic Permissions (`sprints:manage`, `projects:manage`, `Workspace Admin`):** Sudah diselesaikan pada `CreateSprintHttpRequest`, `UpdateSprintController`, `StartSprintController`, dan `CompleteSprintController`.
29. **Add User Creation & Context-Scoped Role Assignment UI in `/settings/rbac` (`UsersTab.tsx`):** Sudah diselesaikan dengan menambahkan dialog pembuatan pengguna baru serta pemilihan *scope* (`GLOBAL`, `WORKSPACE`, `PROJECT`) saat menetapkan peran pengguna.
30. **Add Role Scope Selection & Edit Action in `/settings/rbac` (`RolesTab.tsx`):** Sudah diselesaikan dengan mengganti `scope: 'GLOBAL'` yang di-*hardcode* menjadi pilihan cakupan dinamis serta menambahkan fitur sunting (*Edit*) peran dan proteksi tombol hapus pada `Superadmin`.

