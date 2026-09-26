# Dokumentasi Workspaces: Antarmuka & Alur Pengguna (User Flow)

Dokumen ini mendeskripsikan secara komprehensif rancangan antarmuka (UI/UX) dan pengalaman pengguna untuk modul **Workspace** pada platform WLMS (Workload Management System).

## 1. Tampilan (View) Halaman Workspace
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

## 2. Cakupan Tampilan (Feature Coverage)
Berdasarkan *Entity Relationship Diagram (ERD)*, **Workspace** merupakan struktur "Wadah Tertinggi" (*Top-Level Container*). Cakupan fungsional dari hierarki ini meliputi:

*   **Manajemen Portofolio Project:** Satu Workspace bertindak sebagai payung untuk memanajemen banyak *Projects*. Halaman detail akan memuat daftar seluruh proyek yang dinaungi departemen tersebut.
*   **Makro-Metrik (Overview):** Menampilkan ringkasan beban kerja (*workload*) secara keseluruhan, seperti jumlah *Projects* aktif, total *Sprints* berjalan, hingga rasio pergerakan/penyelesaian *Issues*.
*   **Manajemen Otorisasi (Access Control):** Merepresentasikan batasan siapa saja (*User/Group*) yang berhak mengakses dan melakukan modifikasi data di dalam ranah tersebut.

## 3. Aktivitas Pengguna (User Activities) & Activity Diagram
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

## 4. Matriks Otorisasi & Role-Based Access Control (RBAC) Granular

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

## 5. Status Data RBAC & Simulasi Pengguna (Seeder)

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
