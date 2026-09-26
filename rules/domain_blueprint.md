# WLMS (Workload Management System) - Domain Blueprint

Dokumen ini merupakan penjabaran sistematis (breakdown) dari fitur-fitur WLMS (sejenis JIRA) hingga ke level teknis terkecil (Entities, Value Objects, Use Cases, dan Events). Dokumen ini menjadi acuan utama pengembangan untuk mematuhi arsitektur **Modular Monolith, Clean Architecture, dan Event-Driven**.

---

## 1. Modul `Identity`

**Fokus:** Autentikasi, otorisasi hierarkis (RBAC), manajemen pengguna, dan struktur organisasi (OrgUnit & Workspace).

### 1.1. Domain Layer

- **Entities:**
    - `User`: Data utama pengguna (nama, email, password, status aktif).
    - `Role`: Kumpulan hak akses (Permissions) di dalam sistem (misal: SystemAdmin, Manager, Member).
    - `OrgUnit`: Entitas hierarkis organisasi (Tree Structure) yang mewakili tingkatan organisasi. Memiliki relasi _parent-child_ dan referensi ke `OrgLevel`.
    - `OrgLevel`: Mendefinisikan tingkatan hierarki secara **dinamis** (nama, _depth_, `can_own_workspace`, `is_leaf`). Admin bisa menambah/mengubah level (misal: "Section", "Department") **tanpa mengubah kode atau schema database**.
    - `Workspace`: Ruang kerja terisolasi. Hanya dimiliki oleh `OrgUnit` yang `OrgLevel.can_own_workspace = true`.
    - `Menu`: Entitas navigasi yang dikonfigurasi secara dinamis dan di-mapping ke Role.
- **Value Objects:**
    - `UserId`, `RoleId`, `OrgUnitId`, `WorkspaceId`, `OrgLevelId`, `MenuId`.
    - `EmailAddress`.
    - `HashedPassword`.
    - `OrgLevelSlug` — String identifier stabil untuk referensi programatik (e.g., `'group'`, `'vp'`). Digunakan sebagai pengganti ENUM agar tidak hardcode nama level di kode.
- **Domain Events:**
    - `UserRegistered`.
    - `UserPlacedInOrgUnit`.
    - `WorkspaceAssignedToGroup`.

### 1.2. Application Layer (Use Cases)

- `RegisterUserUseCase`: Mendaftarkan pengguna baru.
- `AuthenticateUserUseCase`: Memvalidasi kredensial (mengeluarkan Token).
- `CreateOrgUnitUseCase`: Membangun struktur tree organisasi.
- `AssignUserToOrgUnitUseCase`: Menempatkan pengguna ke unit tertentu dengan peran tertentu.
- `CreateWorkspaceForGroupUseCase`: Menginisialisasi _workspace_ untuk OrgUnit yang `OrgLevel.can_own_workspace = true`.

### 1.3. Infrastructure & Presentation

- **Controllers:** `AuthController`, `OrgUnitController`, `WorkspaceController`.
- **Repositories:** `EloquentUserRepository`, `EloquentOrgUnitRepository` (Menggunakan pola _Closure Table_ untuk query rekursif hierarki), `EloquentWorkspaceRepository`.

---

## 1.5. Alur RBAC & Data Isolation (Role-Based Access Control)

Karena WLMS menerapkan struktur hierarki Enterprise yang ketat, sistem otorisasi dan isolasi datanya diatur melalui **Hierarchical RBAC Flow**:

### A. Konsep Dasar Visibilitas Workspace

1. **Aturan 1 (Kepemilikan):** Data _Workload_ (Project, Issue, Sprint) selalu tersimpan di dalam sebuah `Workspace`. `Workspace` HANYA berafiliasi dengan `OrgUnit` yang `OrgLevel.can_own_workspace = true` — **tidak hardcode nama level apapun**.
2. **Aturan 2 (Isolasi Horizontal):** _User_ yang berada di dalam `Group A` **hanya** memiliki akses ke `Workspace A`. Mereka terisolasi secara total dari `Workspace B` (milik Group B).
3. **Aturan 3 (Visibilitas Vertikal / Cascading):** _User_ yang berada di level _parent_ (misal VP) secara otomatis **mewarisi hak akses (Read/Analytic)** ke seluruh _Workspace_ yang dimiliki oleh _descendant_ (anak cucu) dari unitnya.

### B. Flow Validasi Izin Akses (RBAC Flow)

Ketika User X mencoba mengakses sebuah `Project` di dalam sebuah `Workspace`:

1. **Authentication Check:** Sistem memverifikasi Token User X valid.
2. **Permission Check (Role):** Sistem memeriksa apakah `Role` yang dimiliki User X memiliki _permission_ (misal: `project:view`).
    - _Jika TIDAK:_ Akses ditolak (403 Forbidden).
    - _Jika YA:_ Lanjut ke pengecekan visibilitas data (Langkah 3).
3. **Hierarchical Isolation Check (OrgUnit):**
    - Sistem melihat ID dari `Workspace` target.
    - Sistem mencari `OrgUnit` mana yang memiliki `Workspace` tersebut (via `org_units.id` → `workspaces.owner_group_id`).
    - Sistem memvalidasi posisi User X di pohon organisasi:
        - _Kondisi 1:_ Apakah User X adalah anggota langsung dari OrgUnit tersebut? → **Diizinkan**.
        - _Kondisi 2:_ Apakah `OrgUnit` tempat User X berada adalah _Ancestor_ dari OrgUnit tersebut secara hierarkis? (query via `org_unit_closures`) → **Diizinkan**.
        - _Kondisi 3:_ User X berada di cabang lain → **Ditolak (403)**.

_Catatan Implementasi:_ Di level database, pengecekan ini dilakukan dengan _JOIN_ yang sangat efisien menggunakan _Closure Table_ dari `OrgUnit` untuk memastikan respons query dalam hitungan milidetik meskipun melibatkan ratusan workspace.

---

## 2. Modul `Workload` (Core Domain)

**Fokus:** Manajemen proyek, task (issue), sprint, dan workflow pengerjaan.

### 2.1. Domain Layer

- **Entities:**
    - `Project`: Proyek yang menaungi banyak issues (memiliki prefix/key, misal: "WLMS-123").
    - `Issue` (Task/Bug/Story/Epic): Unit kerja utama. Tipe dan prioritasnya merujuk ke lookup table dinamis.
    - `Sprint`: Time-box untuk kerangka kerja Scrum.
    - `Board`: Tampilan visual (Kanban/Scrum) dari status issue.
    - `IssueType`: (**Lookup Table dinamis**) Mendefinisikan tipe tiket — e.g., Task, Bug, Story, Epic. Admin dapat menambah tipe baru tanpa deploy.
    - `Priority`: (**Lookup Table dinamis**) Mendefinisikan tingkat prioritas — e.g., Low, Medium, High, Critical. Admin dapat mengubah urutan atau menambah level baru tanpa deploy.
    - `Status`: (**Lookup Table dinamis**) Dikontrol oleh State Machine Workflow. Lihat `workflows` dan `workflow_transitions`.
- **Value Objects:**
    - `ProjectId`, `IssueId`, `SprintId`.
    - `IssueKey` (Contoh: "WLMS-12") — Dibentuk dari kombinasi `Project.key` + `Issue.number`.
    - `IssueTypeId` — Referensi ke lookup table `issue_types` (bukan string ENUM).
    - `PriorityId` — Referensi ke lookup table `priorities` (bukan string ENUM).
    - `StatusId` — Referensi ke lookup table `statuses` (dikontrol Workflow State Machine).
    - `StoryPoint` (Integer/Fibonacci untuk estimasi beban).
- **Domain Events:**
    - `ProjectCreated`.
    - `IssueCreated` (Payload: IssueId, ProjectId, CreatorId).
    - `IssueAssigned` (Payload: IssueId, AssigneeId).
    - `IssueStatusTransitioned` (Payload: IssueId, OldStatusId, NewStatusId).
    - `SprintStarted` / `SprintCompleted`.

### 2.2. Application Layer (Use Cases)

- `CreateProjectUseCase`: Inisialisasi proyek dan setting board.
- `CreateIssueUseCase`: Membuat task baru di backlog. Validasi `IssueTypeId` dan `PriorityId` terhadap lookup table.
- `AssignIssueUseCase`: Menetapkan task ke seorang user (Identity).
- `TransitionIssueStatusUseCase`: Mengubah status task dengan validasi terhadap `workflow_transitions` yang berlaku di proyek tersebut.
- `PlanSprintUseCase` & `StartSprintUseCase`: Manajemen agile sprint.

### 2.3. Infrastructure & Presentation

- **Controllers:** `IssueController`, `ProjectController`, `SprintController`.
- **Repositories:** `EloquentIssueRepository`, `EloquentProjectRepository`.

---

## 3. Modul `Collaboration`

**Fokus:** Interaksi antar pengguna, jejak audit (log), dan komentar.

### 3.1. Domain Layer

- **Entities:**
    - `Comment`: Komentar pengguna pada sebuah Issue. Mendukung threading (nested reply) via `parent_id`.
    - `AuditLog`: Catatan historis perubahan entitas (Audit Trail). Append-only. Mencakup seluruh modul.
- **Value Objects:**
    - `CommentId`, `AuditLogId`.
    - `TargetEntity` (Value object yang menyimpan tipe entitas dan ID-nya, misal tipe "Issue" ID "WLMS-42").
    - `CommentBody` (Teks komentar, mendukung markdown/mentions).
- **Domain Events:**
    - `CommentAdded` (Payload: CommentId, IssueId, AuthorId).
    - `MentionTriggered` (Payload: MentionedUserId, ContextUrl).

### 3.2. Application Layer (Use Cases)

- `AddCommentToIssueUseCase`.
- `EditCommentUseCase`.
- `LogAuditEventUseCase` — Dipanggil secara otomatis oleh Event Listener. Bukan dipanggil langsung di Controller.
- `GetIssueTimelineUseCase` (Menggabungkan komentar dan log aktivitas untuk UI).

### 3.3. Infrastructure & Presentation

- **Listeners:** `IssueEventSubscriber` (Mendengarkan `IssueStatusTransitioned` dari modul Workload untuk otomatis membuat `AuditLog`).

---

## 4. Modul `Notification`

**Fokus:** Pengiriman pemberitahuan ke pengguna secara real-time maupun asinkron.

### 4.1. Domain Layer

- **Entities:**
    - `InAppNotification`: Pemberitahuan yang muncul di lonceng aplikasi.
    - `NotificationPreference`: Pengaturan user berdasarkan `NotificationChannel` yang aktif.
    - `NotificationChannel`: (**Lookup Table dinamis**) Mendefinisikan kanal pengiriman — e.g., Email, InApp, Push, Slack. Admin dapat menambah kanal baru (misal: Teams, WhatsApp) tanpa mengubah kode.
- **Value Objects:**
    - `NotificationId`.
    - `NotificationChannelId` — Referensi ke lookup table `notification_channels` (bukan string ENUM).
- **Domain Events:**
    - `NotificationSent`.
    - `NotificationRead`.

### 4.2. Application Layer (Use Cases)

- `DispatchNotificationUseCase` — Resolusi kanal berdasarkan data di `notification_channels` dan preferensi user, bukan switch-case ENUM.
- `MarkNotificationAsReadUseCase`.
- `UpdateNotificationPreferencesUseCase`.

### 4.3. Infrastructure & Presentation

- **Listeners:** Mendengarkan `IssueAssigned`, `CommentAdded`, dan `MentionTriggered` untuk memicu pengiriman via adapter yang dipilih berdasarkan `NotificationChannelId` (Strategy Pattern).

---

## 5. Modul `Analytic` (Reporting)

**Fokus:** Metrik kinerja tim, pencatatan waktu (time-tracking), dan laporan.

### 5.1. Domain Layer

- **Entities:**
    - `Worklog`: Catatan waktu dalam **satuan detik** (seconds) yang dihabiskan user untuk sebuah issue.
    - `SprintMetrics`: Snapshot penyelesaian story points per sprint.
- **Value Objects:**
    - `WorklogId`.
    - `TimeSpentSeconds` (Integer murni. Konversi ke "2h 30m" dilakukan di Frontend, bukan DB).
    - `LogDate` (Kapan pekerjaan dilakukan).
- **Domain Events:**
    - `WorklogAdded`.

### 5.2. Application Layer (Use Cases)

- `LogTimeOnIssueUseCase`: Mencatat _timesheet_.
- `GenerateBurndownChartUseCase`: Menghitung sisa poin vs waktu dalam sprint menggunakan data dari `issue_histories`.
- `CalculateTeamVelocityUseCase`: Menghitung kecepatan tim menyelesaikan poin per sprint.

### 5.3. Infrastructure & Presentation

- **Controllers:** `WorklogController`, `ReportController`.
- **Repositories:** Menggunakan Query Builder langsung (Bypass CQRS) untuk reporting yang kompleks dan agregasi berat.

---

## Aturan Emas Integrasi (Sesuai `architecture.md`)

1. Modul **Workload** tidak boleh memanggil `UserRepository` untuk mendapatkan nama user. Workload hanya menyimpan `AssigneeId`. Untuk menampilkan nama, gunakan API/Service internal dari modul **Identity** di layer Application, atau satukan di layer Presentation saat mapping ke UI.
2. Saat `IssueAssigned` terjadi di modul Workload, sebuah _Event_ di-_dispatch_. Modul **Collaboration** akan menangkapnya untuk menulis log, dan modul **Notification** menangkapnya untuk mengirim pemberitahuan.

## Aturan Anti-ENUM (Prinsip Database Architect)

> **DILARANG KERAS** menggunakan tipe data `ENUM` di schema database maupun konstanta string hardcoded untuk klasifikasi data yang bersifat konfigurasi bisnis.
>
> Semua nilai yang berpotensi berubah, bertambah, atau memiliki metadata tambahan **WAJIB** disimpan sebagai lookup table terpisah dengan Primary Key UUID dan kolom `slug` yang stabil untuk referensi programatik.
>
> | ❌ Anti-Pattern                 | ✅ Pattern Enterprise                                |
> | ------------------------------- | ---------------------------------------------------- |
> | `ENUM('Bug','Task','Story')`    | Tabel `issue_types` dengan FK                        |
> | `ENUM('Low','High','Critical')` | Tabel `priorities` dengan kolom `level` integer      |
> | `ENUM('Email','Push','InApp')`  | Tabel `notification_channels` dengan adapter pattern |
> | `WHERE type = 'GROUP'`          | `WHERE org_level.can_own_workspace = true`           |
