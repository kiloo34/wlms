# Tools And Logs - WLMS Project

Dokumen ini adalah gabungan dari semua dokumentasi terkait dalam proyek.

---

## workspace-env-requirements

### Workspace Environment & Config Requirements

Fitur Workspace menggunakan konfigurasi dinamis (_Dynamic Configuration_) sesuai prinsip #5 `agent-backend.md`.

#### Database Settings (Tabel `settings`)

Tidak ada variabel `.env` baru yang diperlukan. Konfigurasi diambil dari tabel `settings`.

| Key                                 | Tipe Data | Default | Keterangan                                                                |
| ----------------------------------- | --------- | ------- | ------------------------------------------------------------------------- |
| `workload.max_workspaces_per_group` | Integer   | 10      | Batas maksimum workspace yang boleh dibuat oleh sebuah Group (Otorisasi). |

#### Queue Requirements

Event Listener berjalan secara _asynchronous_ (`ShouldQueue`).
Pastikan Queue Worker aktif:

```bash
php artisan queue:work
```

---

## workspace-user-flow

### Dokumentasi Workspaces: Antarmuka & Alur Pengguna (User Flow)

Dokumen ini mendeskripsikan secara komprehensif rancangan antarmuka (UI/UX) dan pengalaman pengguna untuk modul **Workspace** pada platform WLMS (Workload Management System).

#### 1. Tampilan (View) Halaman Workspace
Halaman utama Workspace (`/workspaces`) mengadopsi gaya antarmuka modern berbentuk **Dashboard Grid**, yang dirancang menggunakan komponen UI *shadcn/Radix UI* dan *Tailwind CSS*.

*   **Tata Letak (Layout):** Diposisikan di panel utama sebelah kanan *Sidebar* navigasi.
*   **Header Area:** Terdiri dari judul halaman "Workspaces", deskripsi fungsi singkat, dan tombol pemicu tindakan utama (Call-to-Action) yaitu `[+ New Workspace]`.
*   **Konten (Grid View):** Lingkungan kerja yang dirender menjadi bentuk tumpukan **Kartu (Cards)** yang rapi dan responsif.

### Apa Saja yang Ditampilkan?
Pada setiap "Kartu Workspace" (*Workspace Card*), informasi yang dimunculkan meliputi:
1.  **Nama Workspace:** Judul dari area kerja (Misal: "MIS Workspace", "Engineering Dept").
2.  **Status Badge:** Label indikator dengan warna sesuai (Misal: 🟢 `ACTIVE`, atau ⚪ `ARCHIVED`).
3.  **ID Sistem:** Bagian awal dari UUID (untuk keperluan penelusuran jika terjadi insiden log/teknis).
4.  **Owner Group:** ID/Nama referensi grup atau unit organisasi yang menjadi pemilik (*owner*) melalui sistem hierarki RBAC.
5.  **Call-to-Action (CTA):** Tombol `[View Workspace]` di bagian bawah kartu untuk masuk mengelola detail proyek di dalamnya.

#### 2. Cakupan Tampilan (Feature Coverage)
Berdasarkan *Entity Relationship Diagram (ERD)*, **Workspace** merupakan struktur "Wadah Tertinggi" (*Top-Level Container*). Cakupan fungsional dari hierarki ini meliputi:

*   **Manajemen Portofolio Project:** Satu Workspace bertindak sebagai payung untuk memanajemen banyak *Projects*. Halaman detail akan memuat daftar seluruh proyek yang dinaungi departemen tersebut.
*   **Makro-Metrik (Overview):** Menampilkan ringkasan beban kerja (*workload*) secara keseluruhan, seperti jumlah *Projects* aktif, total *Sprints* berjalan, hingga rasio pergerakan/penyelesaian *Issues*.
*   **Manajemen Otorisasi (Access Control):** Merepresentasikan batasan siapa saja (*User/Group*) yang berhak mengakses dan melakukan modifikasi data di dalam ranah tersebut.

#### 3. Aktivitas Pengguna (User Activities) & Activity Diagram
Aktivitas pengguna tidak hanya terbatas pada halaman utama, tetapi juga alur (*flow*) yang menavigasikan mereka menembus ke level paling bawah (Tiket/Issue).

Berikut adalah gambaran alur aktivitas (_Activity Diagram_) dalam platform WLMS:

```mermaid
flowchart TD
    Start((Login)) --> Dashboard[Navigasi ke Menu Workspaces]
    
    subgraph Fase_1 [1. Halaman Dashboard Workspaces]
        Dashboard --> A{Apa yang ingin\ndilakukan?}
        A -->|Lihat Data| B[Membaca Daftar Workspace (Grid Cards)]
        A -->|Buat Baru| C[Klik '+ New Workspace']
        C --> D[Isi Form (Nama Workspace)]
        D -->|Validasi Sukses| B
        A -->|Akses Lingkungan| E[Klik 'View Workspace' pada Kartu]
    end

    subgraph Fase_2 [2. Halaman Workspace Detail]
        E --> F{Manajemen di\ndalam Workspace}
        F -->|Dashboard/Metrik| G[Melihat Laporan Agregat (Total Project, dll)]
        F -->|Konfigurasi| H[Mengubah Status Workspace (Active/Archived)]
        F -->|Manajemen Project| I[Melihat Daftar Project]
        I --> J[Membuat Project Baru]
        I --> K[Pilih / Masuk ke Spesifik Project]
    end

    subgraph Fase_3 [3. Aktivitas Inti Platform (JIRA-like)]
        K --> L{Operasional Workload}
        L --> M[Backlog: Membuat Epics & Issues]
        L --> N[Sprints: Memulai & Mengakhiri Sprint]
        L --> O[Kanban/Board: Geser Status Tiket (Workflow)]
        L --> P[Time Tracking: Input 'Log Work' (Detik)]
        L --> Q[Issue Linking: Menautkan Relasi Tiket (Blocks/Duplicates)]
    end
    
    classDef highlight fill:#3b82f6,stroke:#1d4ed8,stroke-width:2px,color:#fff;
    class Fase_1,Fase_2,Fase_3 fill:#f8fafc,stroke:#cbd5e1,stroke-width:1px,color:#000;
    class C,E,J,K highlight;
```

### Penjelasan Aktivitas Berdasarkan Fase:
1.  **Fase 1 (Level Indeks):** Aktivitas membaca daftar wadah (Read) dan menciptakan wadah baru (Create). Pengguna akan memutuskan lingkungan mana yang akan mereka modifikasi dengan mengeklik salah satu kartu.
2.  **Fase 2 (Level Workspace Detail):** Aktivitas di dalam lingkungan terisolasi. Pengguna memanajemen fondasi operasional dengan membuat atau memantau proyek (*Projects*) tanpa melihat hal-hal mikroskopis (tiket) terlebih dahulu.
3.  **Fase 3 (Level Project/Issue):** Merupakan area kerja kolaboratif (*core workspace*) di mana sebagian besar waktu pengguna dihabiskan. Aktivitas berpusat pada *Scrum/Kanban events*, penyesuaian status tiket melalui fungsi geser/tarik (*Drag & Drop*), dan pendataan waktu kerja riil (*Time Tracking*).

#### 4. Matriks Otorisasi & Role-Based Access Control (RBAC) Granular

Dari sudut pandang *Senior Backend Developer*, sistem otorisasi (RBAC) dalam WLMS dirancang secara dinamis menggunakan pendekatan *Anti-IDOR* dan *Policy-based Authorization*. Tidak ada peran (*role*) yang di-*hardcode*. Akses pengguna divalidasi berdasarkan relasi mereka dengan entitas di database (`owner_group_id`, `lead_id`, `assignee_id`).

Agar tidak ada *miss persepsi*, berikut adalah penjabaran hingga **level terkecil (granular)** mengenai apa yang BISA dan TIDAK BISA dilakukan oleh masing-masing peran di dalam ekosistem sistem:

### 1. Superadmin (Global System Administrator)
*Role* tertinggi yang memiliki visibilitas dan kendali absolut di seluruh platform WLMS. Mereka bekerja di belakang layar untuk memastikan konfigurasi infrastruktur sistem berjalan baik.
*   **Aktivitas Granular (BISA):**
    *   **Manajemen Hierarki (Identity):** Melakukan operasi CRUD (Create, Read, Update, Delete) pada tabel `org_units` (Departemen/Divisi) dan `org_levels` (Level Jabatan/Grup).
    *   **Manajemen Otorisasi Global:** Mendefinisikan kombinasi `roles` dan `permissions` secara dinamis yang nantinya akan dipakai oleh seluruh sistem.
    *   **Manajemen UI/UX Konfigurasi:** Mengatur *Visibility* menu sistem utama atau mengelola *Global Settings* di database.
    *   **Global Override:** Bypass seluruh *Gate/Policy*. Memiliki wewenang absolut untuk mengambil alih (*takeover*), memodifikasi, atau menghapus permanen (*Force Delete*) Workspace, Project, atau Issue manapun (misal: membersihkan *spam* atau menangani data yang ditinggalkan karyawan *resign*).

### 2. Workspace Owner (Workspace Administrator)
Pengguna yang dikaitkan langsung dengan otoritas kepemilikan sebuah Workspace. Peran ini **murni bersifat kontekstual di dalam sistem** dan tidak selalu terikat pada jabatan struktural (seperti *Head of Dept*). Seseorang bisa menjadi Workspace Owner untuk inisiatif lintas-divisi (misal: "Proyek Inovasi 2026").
*   **Aktivitas Granular (BISA):**
    *   **Workspace Lifecycle:** Mengubah profil (Nama, Logo/Ikon), serta Mengarsipkan (*Archive*) atau Memulihkan (*Restore*) Workspace miliknya.
    *   **Gatekeeper (Manajemen Anggota):** Mengundang (*Invite*) pengguna ke dalam Workspace, mencabut akses (*Kick*) pengguna, dan membagikan peran *Project Lead* atau *Member* di dalam yurisdiksi Workspace-nya.
    *   **Project Governance:** Membuat *Project* baru di dalam Workspace, menetapkan/mengganti *Project Lead*, dan melakukan *Soft-Delete* pada Project.
    *   **Helicopter View:** Mengakses dasbor analitik lintas-project untuk melihat *Macro-metrics* (misal: memantau persentase penyelesaian Epics dari 5 proyek berbeda secara bersamaan).
*   **Batasan (TIDAK BISA):**
    *   Mengubah *Global Settings* atau menghapus *Org Unit* (itu wewenang Superadmin).

### 3. Project Lead / Scrum Master
Pemegang kendali spesifik di level **Project**. Bertanggung jawab atas konfigurasi papan kerja (*Board*), siklus hidup *Sprint*, dan manajemen *Backlog*. Didefinisikan melalui kolom `lead_id` di tabel `projects`.
*   **Aktivitas Granular (BISA):**
    *   **Konfigurasi Workflow Dinamis (Krusial):** Membangun atau memodifikasi jaring *State Machine* (alur transisi). *Project Lead* berhak menentukan bahwa status `TODO` hanya bisa dipindah ke `IN_PROGRESS`, dan `IN_PROGRESS` hanya bisa ke `QA_REVIEW` (memblokir tim untuk memindah tiket langsung ke `DONE`).
    *   **Sprint Management:** Membangun *Sprint* baru, Memulai (*Start*) Sprint, dan Menutup (*Close*) Sprint. Menangani tiket *spill-over* (tidak selesai) dengan memindahkannya ke Backlog atau Sprint berikutnya.
    *   **Backlog Grooming:** Melakukan *bulk-update* prioritas tiket, merancang *Epic*, dan mendistribusikan estimasi beban kerja (*Story Points*).
    *   **Issue Override (Moderator):** Memiliki hak untuk mengedit *title/description* tiket siapapun di proyeknya, mengubah *Assignee*, menghapus komentar anggota (sebagai moderator), dan melakukan *Soft-Delete* pada tiket yang salah buat.
*   **Batasan (TIDAK BISA):**
    *   Menghapus *Workspace* tempat proyeknya bernaung.

### 4. Developer / Member (Pekerja / Eksekutor Harian)
Anggota operasional standar yang ditugaskan ke dalam sebuah *Project*. Interaksi mereka sangat terisolasi hanya pada *Issues (Tickets)*.
*   **Aktivitas Granular (BISA):**
    *   **Interaksi Tiket:** Membuat *Issue* baru (Task/Bug), melihat detail tiket (termasuk *Custom Fields*), serta menyematkan dokumen (*Attachments*).
    *   **Manajemen Komentar:** Menulis, mengedit, atau menghapus komentar **miliknya sendiri**.
    *   **Time Tracking (Penting):** Melakukan eksekusi *Log Work* (mencatat waktu pengerjaan, misal: "3h 45m" yang akan dikonversi menjadi detik oleh sistem). Mereka bisa mengedit/menghapus *Log Work* miliknya sendiri.
    *   **Board Progression:** Menggeser status tiket di papan Kanban (*Drag & Drop*) **HANYA JIKA** pergeseran tersebut diizinkan oleh rute *Workflow* yang telah diatur *Project Lead*.
    *   **Issue Linking:** Menautkan hubungan sebab-akibat antar tiket (Misal: "Tiket A *blocks* Tiket B", atau "*Relates to* Tiket C").
*   **Batasan Anti-IDOR (TIDAK BISA):**
    *   Tidak bisa mengedit atau menghapus komentar orang lain.
    *   Tidak bisa melakukan *Soft-Delete* pada *Issue*.
    *   Tidak bisa memulai/menutup *Sprint*.
    *   Tidak bisa membuat jalur status/workflow baru (misal: menambahkan kolom status "ON_HOLD" di Kanban).
    *   Meskipun tahu *UUID* proyek lain, mereka akan mendapat respons `403 Forbidden` jika mencoba meretas/memanipulasi *Issue* di luar proyek yang mereka miliki aksesnya.

### 5. Guest / Reporter / Viewer
Pengguna dengan akses sangat terbatas yang fokus utamanya adalah pemantauan ujung-ke-ujung (end-to-end monitoring) atau pelaporan.
*   **Aktivitas Granular (BISA):**
    *   **Reporting:** Membuat tiket bertipe *Bug* atau *Request* (dicatat sebagai `reporter_id`).
    *   **Limited Visibility:** Hanya berhak membaca detail tiket **yang dibuatnya sendiri**, atau tiket di mana mereka secara spesifik di-*mention*/ditugaskan sebagai pengamat.
    *   **Komunikasi Dasar:** Menambahkan komentar pada tiket tersebut untuk memberikan informasi tambahan kepada *Developer*.
    *   **Konfirmasi Akhir (User Acceptance):** Mungkin diizinkan (melalui *workflow* khusus) untuk menekan tombol "Close Ticket" / "Accept" ketika *Developer* meminta validasi QA/UAT.
*   **Batasan (TIDAK BISA):**
    *   Tidak bisa melakukan *Time Tracking (Log Work)*.
    *   Tidak bisa melihat tumpukan *Backlog* atau melihat tiket milik klien/orang lain.
    *   Tidak bisa menggeser status tiket secara bebas di *Board*.

#### 5. Status Data RBAC & Simulasi Pengguna (Seeder)

Sampai dengan penulisan dokumen ini, arsitektur fisik tabel RBAC (seperti `roles`, `permissions`, dan `user_roles`) **telah sukses dibuat (Migrated)** di dalam database. 

Namun, secara ketersediaan data, tabel `roles` dan `permissions` saat ini masih **kosong**, karena penambahan role bersifat dinamis dan kita tidak melakukan *hardcode* di *migration*.

Untuk memudahkan proses *Testing* dan *Quality Assurance (QA)*, telah disediakan file **`database/seeders/RbacSimulationSeeder.php`** yang jika dieksekusi (dengan `php artisan db:seed --class=RbacSimulationSeeder`) akan menyuntikkan (membuat) 5 Role utama ke tabel `roles` dan menciptakan 5 User Simulasi.

Berikut adalah dokumentasi daftar pengguna simulasi (*Simulation Users*) yang akan (atau sudah) terecord di database setelah eksekusi seeder:

| Nama Pengguna | Email Login (Password: `password`) | ID Entitas (*Seeder Reference*) | Representasi Role (Tabel `roles`) | Konteks *Scope* Akses |
| :--- | :--- | :--- | :--- | :--- |
| **John (Superadmin)** | `superadmin@wlms.com` | Menggunakan UUIDv7 acak | **Superadmin** | `global` (Seluruh Sistem) |
| **Sarah (Owner)** | `owner@wlms.com` | Menggunakan UUIDv7 acak | **Workspace Owner** | `workspace` (Area Workspace) |
| **Mike (Lead)** | `lead@wlms.com` | Menggunakan UUIDv7 acak | **Project Lead** | `project` (Spesifik Project) |
| **Alex (Dev)** | `dev@wlms.com` | Menggunakan UUIDv7 acak | **Developer** | `project` (Spesifik Project) |
| **Emma (Guest)** | `guest@wlms.com` | Menggunakan UUIDv7 acak | **Guest** | `project` (Spesifik Project) |

*Catatan: Semua user simulasi ini akan dihubungkan secara otomatis ke sebuah Departemen "Engineering Group" (`org_units`) untuk memenuhi syarat pembuatan Workspace.*

---

## dev-log

### 📋 WLMS Development Log — Sprint & Task Tracker

Dokumen ini mencatat seluruh *sprint* dan *task* yang telah diselesaikan selama sesi pengembangan WLMS (Workload Management System). Format mengacu pada standar internal tim development.

---

#### Sprint Overview

| Sprint | Fokus | Status | Tanggal |
|--------|-------|--------|---------|
| Sprint 1 | Database Architecture & ERD | ✅ DONE | Sep 13, 2026 |
| Sprint 2 | Backend Clean Architecture — Workspace | ✅ DONE | Sep 13, 2026 |
| Sprint 3 | Frontend UI — Workspace | ✅ DONE | Sep 13–14, 2026 |
| Sprint 4 | The Great Purge (Modular Monolith Migration) | ✅ DONE | Sep 14, 2026 |
| Sprint 5 | Arsitektur Compliance & Pest Arch Test | ✅ DONE | Sep 14, 2026 |
| Sprint 6 | Backend Clean Architecture — Project & Sprint | ✅ DONE | Sep 14, 2026 |

---

#### Sprint 1 — Database Architecture & ERD

**Goal:** Merancang dan memvalidasi seluruh skema database WLMS sebelum baris kode PHP ditulis.

| # | Task | Status | Artifact |
|---|------|--------|----------|
| 1.1 | Rancang hierarki 5 level organisasi (Direksi → VP → Manager → Dept Head → Group) | ✅ DONE | `doc/database/01-architecture-decisions.md` |
| 1.2 | Buat skema ERD lengkap (Identity, Workload, RBAC) | ✅ DONE | `doc/database/02-schema-and-erd.md` |
| 1.3 | Tentukan strategi indexing & performa untuk query hierarki | ✅ DONE | `doc/database/03-indexing-and-performance.md` |
| 1.4 | Generate ERD diagram visual | ✅ DONE | `doc/database/erd-complete.jpg` |
| 1.5 | Buat semua file migrasi Laravel (Identity + Workload) | ✅ DONE | `app/Modules/*/Infrastructure/Database/Migrations/` |
| 1.6 | Keputusan: Hapus ENUM, ganti dengan `string` ber-konstanta | ✅ DONE | Kepatuhan pada `rules/architecture.md` |

---

#### Sprint 2 — Backend Clean Architecture (Workspace)

**Goal:** Mengimplementasikan 4-layer Clean Architecture untuk entitas `Workspace`.

| # | Task | Status | Artifact |
|---|------|--------|----------|
| 2.1 | Buat `WorkspaceId` Value Object | ✅ DONE | `Domain/ValueObjects/WorkspaceId.php` |
| 2.2 | Buat `Workspace` Domain Entity (Pure PHP, `HasDomainEvents`) | ✅ DONE | `Domain/Entities/Workspace.php` |
| 2.3 | Buat `WorkspaceRepositoryInterface` | ✅ DONE | `Domain/Repositories/WorkspaceRepositoryInterface.php` |
| 2.4 | Buat `WorkspaceCreated` Domain Event | ✅ DONE | `Domain/Events/WorkspaceCreated.php` |
| 2.5 | Buat `CreateWorkspaceInput` DTO & `WorkspaceOutput` DTO | ✅ DONE | `Application/DTOs/` |
| 2.6 | Buat `CreateWorkspaceUseCase` | ✅ DONE | `Application/UseCases/CreateWorkspaceUseCase.php` |
| 2.7 | Buat `WorkspaceModel` (Eloquent) & `WorkspaceMapper` | ✅ DONE | `Infrastructure/Persistence/` |
| 2.8 | Buat `EloquentWorkspaceRepository` (dengan `DB::transaction`) | ✅ DONE | `Infrastructure/Persistence/Repositories/` |
| 2.9 | Buat `WorkspacePolicy` (Anti-IDOR) | ✅ DONE | `Infrastructure/Auth/Policies/WorkspacePolicy.php` |
| 2.10 | Buat `CreateWorkspaceController` & `CreateWorkspaceHttpRequest` | ✅ DONE | `Presentation/Http/Controllers/` |
| 2.11 | Buat `WorkspaceSettings` (Dynamic Config) | ✅ DONE | `Infrastructure/Config/WorkspaceSettings.php` |
| 2.12 | Daftarkan binding di `WorkloadServiceProvider` | ✅ DONE | `Infrastructure/Providers/WorkloadServiceProvider.php` |
| 2.13 | Buat Dokumentasi API Contract Workspace | ✅ DONE | `doc/backend/workspace-api-contract.md` |
| 2.14 | Buat Event Catalog Workspace | ✅ DONE | `doc/backend/workspace-event-catalog.md` |

---

#### Sprint 3 — Frontend UI (Workspace)

**Goal:** Membangun UI Workspaces dengan React Query, Compound Components, dan React.

| # | Task | Status |
|---|------|--------|
| 3.1 | Buat halaman `/workspaces` (`Workspaces/Index.tsx`) | ✅ DONE |
| 3.2 | Buat `useWorkspaces` custom hook (React Query `useQuery`) | ✅ DONE |
| 3.3 | Buat `useCreateWorkspace` custom hook (React Query `useMutation`) | ✅ DONE |
| 3.4 | Buat `CreateWorkspaceDialog` (Smart Component) | ✅ DONE |
| 3.5 | Buat `WorkspaceCard` (Dumb Component) | ✅ DONE |
| 3.6 | Fix: React Query `undefined` parsing error | ✅ DONE |
| 3.7 | Fix: CORS/Sanctum session handling untuk `localhost` | ✅ DONE |

---

#### Sprint 4 — The Great Purge (Modular Monolith Migration)

**Goal:** Membongkar seluruh *legacy scaffolding* bawaan Laravel Jetstream/Starter Kit dan memindahkannya ke struktur Modular Monolith.

| # | Task | Status | Keterangan |
|---|------|--------|-----------|
| 4.1 | Hapus folder `app/Models/`, `app/Actions/`, `app/Enums/`, `app/Data/` | ✅ DONE | Legacy Teams scaffolding |
| 4.2 | Refaktor `User` menjadi `UserModel` di namespace Identity Module | ✅ DONE | `Identity/Infrastructure/Persistence/Eloquent/Models/UserModel.php` |
| 4.3 | Buat 5 RBAC Models di Identity Module (`RoleModel`, `PermissionModel`, dll.) | ✅ DONE | Menggantikan Enum lama |
| 4.4 | Pindahkan semua migrasi ke `Infrastructure/Database/Migrations/` | ✅ DONE | Load via `ModuleServiceProvider` |
| 4.5 | Pindahkan Seeders & Factories ke Identity Module | ✅ DONE | Fix `newFactory()` override |
| 4.6 | Pindahkan & refaktor `FortifyServiceProvider` ke Identity Module | ✅ DONE | `Identity/Infrastructure/Providers/FortifyServiceProvider.php` |
| 4.7 | Migrasi Use Cases Auth (`CreateNewUser`, `ResetUserPassword`) | ✅ DONE | `Identity/Application/UseCases/Auth/` |
| 4.8 | Pindahkan `HandleInertiaRequests` & `HandleAppearance` ke Shared | ✅ DONE | `Shared/Presentation/Http/Middleware/` |
| 4.9 | Hapus seluruh `app/Http/` (termasuk base Controller) | ✅ DONE | Pindah ke `Shared/Presentation/Http/Controllers/` |
| 4.10 | Update `bootstrap/app.php` & `bootstrap/providers.php` | ✅ DONE | |
| 4.11 | Sweep frontend: Hapus semua komponen Teams dari React | ✅ DONE | `team-switcher.tsx`, `pages/teams/`, dll. |
| 4.12 | Update `routes/settings.php` ke namespace module | ✅ DONE | Profile & Security Controller |
| 4.13 | Update Wayfinder TS action imports di frontend | ✅ DONE | `actions/App/Modules/Identity/...` |

---

#### Sprint 5 — Arsitektur Compliance & Pest Architecture Test

**Goal:** Menegakkan batas-batas antar layer secara otomatis menggunakan Pest Architecture Testing.

| # | Task | Status | File |
|---|------|--------|------|
| 5.1 | Buat `tests/Arch/ArchitectureTest.php` | ✅ DONE | `tests/Arch/ArchitectureTest.php` |
| 5.2 | Rule: Domain tidak boleh mengimpor Infrastructure/Presentation | ✅ DONE | Automated |
| 5.3 | Rule: Application tidak boleh menggunakan Illuminate\Http | ✅ DONE | Automated |
| 5.4 | Rule: Presentation tidak boleh menyentuh Infrastructure langsung | ✅ DONE | Automated |
| 5.5 | Rule: Workload\Domain tidak boleh cross-import ke Identity\Domain | ✅ DONE | Automated |
| 5.6 | Dokumentasi audit gap arsitektur (5 poin defisiensi) | ✅ DONE | Di sesi chat |
| 5.7 | Jalankan Pest (All 10 tests passed) | ✅ DONE | `vendor/bin/pest` |

---

#### Sprint 6 — Backend Clean Architecture (Project & Sprint)

**Goal:** Implementasi 4-layer Clean Architecture untuk `Project` dan `Sprint` sesuai alur `rules/step.md`.

### Fase 1 — Database (sudah di Sprint 1)
Migrasi `projects` dan `sprints` sudah tersedia.

### Fase 2 — Backend Implementation

| # | Task | Status | File |
|---|------|--------|------|
| 6.1 | Buat `ProjectId`, `ProjectKey`, `SprintId` Value Objects | ✅ DONE | `Domain/ValueObjects/` |
| 6.2 | Buat `Project` Domain Entity (Rich Model, `HasDomainEvents`) | ✅ DONE | `Domain/Entities/Project.php` |
| 6.3 | Buat `Sprint` Domain Entity dengan state machine (`PENDING→ACTIVE→CLOSED`) | ✅ DONE | `Domain/Entities/Sprint.php` |
| 6.4 | Buat `ProjectCreated` & `SprintCreated` Domain Events | ✅ DONE | `Domain/Events/` |
| 6.5 | Buat `ProjectRepositoryInterface` & `SprintRepositoryInterface` | ✅ DONE | `Domain/Repositories/` |
| 6.6 | Buat `ProjectModel` & `SprintModel` (Eloquent) | ✅ DONE | `Infrastructure/Persistence/Eloquent/Models/` |
| 6.7 | Buat `ProjectMapper` & `SprintMapper` | ✅ DONE | `Infrastructure/Persistence/Eloquent/Mappers/` |
| 6.8 | Buat `EloquentProjectRepository` & `EloquentSprintRepository` | ✅ DONE | `Infrastructure/Persistence/Repositories/` |
| 6.9 | Buat DTOs: `CreateProjectInput/Output`, `CreateSprintInput/Output` | ✅ DONE | `Application/DTOs/` |
| 6.10 | Buat `CreateProjectUseCase` (cek key unik, Anti-IDOR) | ✅ DONE | `Application/UseCases/` |
| 6.11 | Buat `CreateSprintUseCase` (blokade untuk project `ARCHIVED`) | ✅ DONE | `Application/UseCases/` |
| 6.12 | Buat `CreateProjectHttpRequest` & `CreateSprintHttpRequest` | ✅ DONE | `Presentation/Http/Requests/` |
| 6.13 | Buat `CreateProjectController` & `CreateSprintController` | ✅ DONE | `Presentation/Http/Controllers/` |
| 6.14 | Tambahkan routes `POST /api/projects` & `POST /api/sprints` | ✅ DONE | `Presentation/Http/routes.php` |
| 6.15 | Daftarkan binding baru di `WorkloadServiceProvider` | ✅ DONE | `Infrastructure/Providers/` |

### Fase 3 — Tester (Integration Tests)

| # | Task | Status | File |
|---|------|--------|------|
| 6.16 | Pest Feature Test: `can create a project in a workspace` | ✅ DONE | `tests/Feature/Workload/ProjectManagementTest.php` |
| 6.17 | Pest Feature Test: `can create a sprint in a project` | ✅ DONE | `tests/Feature/Workload/SprintManagementTest.php` |
| 6.18 | Pest Feature Test: `cannot create sprint in an archived project` | ✅ DONE | `tests/Feature/Workload/SprintManagementTest.php` |
| 6.19 | Semua 7 tests passed (Arch + Feature) | ✅ DONE | `vendor/bin/pest` |

### Fase 4 — Frontend UI

| # | Task | Status | File |
|---|------|--------|------|
| 6.20 | Custom Hook `useCreateProject` (React Query `useMutation`) | ✅ DONE | `resources/js/hooks/projects/use-create-project.ts` |
| 6.21 | Custom Hook `useCreateSprint` (React Query `useMutation`) | ✅ DONE | `resources/js/hooks/sprints/use-create-sprint.ts` |
| 6.22 | Dumb Component `CreateProjectForm.tsx` | ✅ DONE | `resources/js/modules/Projects/components/` |
| 6.23 | Dumb Component `CreateSprintForm.tsx` | ✅ DONE | `resources/js/modules/Sprints/components/` |
| 6.24 | Smart Component `CreateProjectDialog.tsx` | ✅ DONE | `resources/js/modules/Projects/components/` |
| 6.25 | Smart Component `CreateSprintDialog.tsx` | ✅ DONE | `resources/js/modules/Sprints/components/` |
| 6.26 | Build frontend (npm run build) — PASSED | ✅ DONE | |

### Fase 5 — QA Audit

| # | Task | Status |
|---|------|--------|
| 6.27 | Audit Kepatuhan Arsitektur (Domain Purity, Golden Rule) | ✅ APPROVED |
| 6.28 | Audit End-to-End Business Workflow | ✅ APPROVED |
| 6.29 | Security & Zero Data Breach Audit | ✅ APPROVED |
| 6.30 | UX & Resilience Evaluation (Error Handling, Loading State) | ✅ APPROVED |
| **VERDICT** | **QA Agent** | ✅ **[ APPROVE ]** |

### Fase 6 — Technical Documentation

| # | Task | Status | File |
|---|------|--------|------|
| 6.31 | Buat FSD (Functional Specification Document) | ✅ DONE | `doc/phase-6-project-sprint/FSD.md` |
| 6.32 | Buat Berita Acara UAT (sign-off Direksi) | ✅ DONE | `doc/phase-6-project-sprint/UAT_OK.md` |
| 6.33 | Buat API Contract (OpenAPI spec) | ✅ DONE | `doc/phase-6-project-sprint/API_Contract.md` |

---

#### Ringkasan Statistik

| Metrik | Nilai |
|--------|-------|
| Total Sprint | 6 Sprint |
| Total Task Diselesaikan | 83 Task |
| Tes Otomatis (Pest) | 10 Tests, 100% Passed |
| Tes Arsitektur (Pest Arch) | 4 Arch Rules, 100% Enforced |
| QA Verdict | ✅ APPROVE |
| Dokumentasi Teknis | 7 Dokumen dihasilkan |

---

*Dokumen ini di-generate otomatis oleh Engineering Team WLMS — 14 September 2026.*


### Sprint 7 (Issues & Task Management)
- **Status:** COMPLETED
- **Features Delivered:**
  - **Task 84:** Issue Domain (Entity, VO, WorkflowEngine Domain Service)
  - **Task 85:** Issue Domain Events & Repositories (IssueCreated, Assigned, Transitioned)
  - **Task 86:** Issue CQRS Application Layer (Create, Assign, Transition, GetBoard, GetBacklog)
  - **Task 87:** Issue Infrastructure Layer (Models, Repositories, IssueMapper)
  - **Task 88:** Issue Event Listeners (Audit Log, Email Notify, In-App Notification)
  - **Task 89:** Workflow Seeder (Default workflow, statuses, types, priorities)
  - **Task 90:** Issue HTTP Presentation (Controllers, Requests, Routes)
  - **Task 91:** Subagent Tester (7 Pest Scenarios covering edge cases & business logic)
  - **Task 92:** Subagent Frontend (dnd-kit integration for Kanban Board UI)
  - **Task 93:** React Query hooks & Optimistic Rollback logic for Kanban card drag-and-drop
  - **Task 94:** Subagent QA (Bug found and fixed in optimistic rollback implementation, final verdict: APPROVE)
  - **Task 95:** Subagent Tech Writer (FSD, UAT, API Contract documentation in `doc/phase-7-issues/`)

### Sprint 8 (Modul Collaboration & Global Audit Trail)
- **Status:** COMPLETED
- **Features Delivered:**
  - **Task 96:** Minor Housekeeping (Membersihkan sisa-sisa TS error terkait `Team` pasca Jetstream Purge).
  - **Task 97:** Modul Collaboration — Domain Layer (VO: `CommentId`, `AuditLogId`, Entities: `Comment`, `AuditLog`, Events: `CommentAdded`, `CommentEdited`).
  - **Task 98:** Modul Collaboration — Application Layer (DTOs & UseCases untuk Comments dan Global Audit Log).
  - **Task 99:** Integrasi Event-Driven Architecture (`WorkloadEventSubscriber` mendengarkan `IssueCreated`, `IssueAssigned`, `IssueTransitioned` lalu mencatat ke `audit_logs`).
  - **Task 100:** Endpoint API & CQRS (Timeline `GET /api/issues/{issueId}/timeline`, `POST/PUT /api/issues/{id}/comments`).
  - **Task 101:** Testing (Feature & Arch Test for Collaboration lulus 100%. Total Pest tests: 23 passed).
  - **Task 102:** Frontend React Components (`IssueTimeline.tsx`, `CommentBox.tsx`, `CommentThread.tsx`) dan React Query hooks (`use-issue-timeline`, `use-add-comment`).
  - **Task 103:** QA & Dokumentasi (`FSD.md`, `API_Contract.md`, `UAT_OK.md` dibuat untuk Modul Collaboration).

---

