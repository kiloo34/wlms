# Architecture - WLMS Project

Dokumen ini adalah gabungan dari semua dokumentasi terkait dalam proyek.

---

## 01-architecture-decisions

### Database Architectural Decision Records (ADR)

**Versi:** 2.0 (Post-Audit)  
**Tanggal Update:** 2026-09-13

---

#### ADR-001: Primary Key — UUIDv7

**Keputusan:** Wajib menggunakan **UUIDv7** untuk semua tabel entitas bisnis.

**Pengecualian yang diizinkan:**

- `user_access_logs` menggunakan `BigInt Auto-increment` karena tabel ini adalah log volume tinggi (bukan entitas bisnis) dan membutuhkan INSERT performa maksimal.
- Tabel pivot murni (e.g., `role_permissions`, `role_menus`) menggunakan Composite PK tanpa UUID.

**Alasan:**

- _BigInt_ berbahaya untuk sharding/distribusi.
- _UUIDv4_ menyebabkan B-Tree page splitting dan fragmentasi index.
- _UUIDv7_ bersifat _time-ordered_, menggabungkan keunggulan sequential insert (seperti BigInt) dengan keamanan distribusi (seperti UUID).

---

#### ADR-002: Zero Cross-Module Foreign Keys

**Keputusan:** Dilarang menggunakan SQL Foreign Key Constraint yang menyilang antar bounded context (modul).

**Modul yang diakui:**

- `Identity`: `org_levels`, `org_units`, `org_unit_closures`, `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `menus`, `role_menus`
- `Workload`: `workspaces`, `projects`, `sprints`, `statuses`, `workflows`, `workflow_transitions`, `issue_types`, `priorities`, `issues`, `issue_links`, `issue_link_types`, `worklogs`, `issue_histories`, `sprint_metrics`
- `Collaboration`: `audit_logs`, `comments`
- `Security`: `user_access_logs`

**Aturan:** FK boleh ada hanya di dalam modul yang sama. Referensi lintas modul disimpan sebagai UUID biasa (Soft Reference) tanpa constraint.

---

#### ADR-003: Hierarchical Data Model — Closure Table

**Keputusan:** Menggunakan pola **Closure Table** (`org_unit_closures`) untuk menyimpan hierarki organisasi.

**Perbandingan pattern:**

| Pattern           | Read Performance  | Write Performance | Rekursi         |
| ----------------- | ----------------- | ----------------- | --------------- |
| Adjacency List    | O(N) CTE rekursif | O(1)              | Perlu CTE       |
| Nested Sets       | O(1)              | O(N) reorder      | Tidak perlu     |
| **Closure Table** | **O(1) JOIN**     | O(depth) insert   | **Tidak perlu** |
| Materialized Path | O(1) LIKE         | O(1)              | Tidak perlu     |

**Alasan memilih Closure Table:** Struktur organisasi jarang berubah (low write), namun sangat sering dibaca untuk validasi otorisasi setiap request (high read). Closure Table mengoptimalkan sisi yang paling sering dipakai.

---

#### ADR-004: Dynamic OrgLevel — Anti-Hardcode Hierarchy

**Keputusan:** Level hierarki organisasi disimpan di tabel `org_levels` yang dinamis, bukan di kolom ENUM.

**Kolom kunci di `org_levels`:**

- `depth` (integer) — Urutan level. 1=tertinggi, N=terendah.
- `is_leaf` (boolean) — Menandai level terendah.
- `can_own_workspace` (boolean) — Menggantikan hardcode `WHERE type = 'GROUP'`.
- `slug` (varchar) — Stable key untuk referensi programatik di kode PHP.

**Contoh penggunaan yang benar:**

```php
// ❌ DILARANG
OrgUnit::where('type', 'GROUP')->get();

// ✅ WAJIB
OrgUnit::whereHas('level', fn($q) => $q->where('can_own_workspace', true))->get();
```

---

#### ADR-005: Anti-ENUM — Semua Klasifikasi Bisnis Pakai Lookup Table

**Keputusan:** DILARANG KERAS menggunakan tipe data `ENUM` untuk klasifikasi data yang bersifat konfigurasi bisnis.

**Alasan teknis:**

- `ALTER TABLE ... MODIFY COLUMN ENUM(...)` = **TABLE LOCK** di MySQL → Downtime produksi.
- ENUM tidak portabel antar RDBMS (MySQL vs PostgreSQL memiliki perilaku berbeda).
- ENUM tidak bisa menyimpan metadata tambahan (icon, color, sort_order, dll).

**Daftar penggantian:**

| ❌ ENUM Lama                                   | ✅ Lookup Table Baru                 |
| ---------------------------------------------- | ------------------------------------ |
| `ENUM('DIREKSI','SEVP','VP','SUBDIV','GROUP')` | Tabel `org_levels`                   |
| `ENUM('EPIC','STORY','TASK','BUG')`            | Tabel `issue_types`                  |
| `ENUM('LOW','MEDIUM','HIGH','CRITICAL')`       | Tabel `priorities`                   |
| `ENUM('BLOCKS','IS_BLOCKED_BY',...)`           | Tabel `issue_link_types`             |
| `ENUM('EMAIL','INAPP','PUSH')`                 | Tabel `notification_channels`        |
| `ENUM('GLOBAL','WORKSPACE','PROJECT')`         | Kolom `roles.scope` → `string`       |
| `ENUM('ACTIVE','ARCHIVED')`                    | Kolom `status` → `string`            |
| `ENUM('TODO','IN_PROGRESS','DONE')`            | Kolom `statuses.category` → `string` |

**Pengecualian yang diizinkan:** Nilai Boolean, tipe boolean-like yang benar-benar biner dan tidak akan pernah bertambah (gunakan kolom `boolean` atau `tinyint`).

---

#### ADR-006: Anti-EAV — Custom Fields via JSON/JSONB

**Keputusan:** Custom Fields pada `issues` disimpan sebagai kolom `json` (satu kolom), bukan tabel EAV.

**Alasan:** Tabel EAV memerlukan N JOIN untuk mengambil N atribut custom dari satu tiket. Di sistem dengan ribuan tiket dan puluhan custom field, ini = "Join of Death". `JSONB` di PostgreSQL dan `JSON` di MySQL 8+ mendukung indexing parsial untuk atribut spesifik.

---

#### ADR-007: Time Tracking — Integer Seconds

**Keputusan:** Kolom `worklogs.time_spent_seconds` wajib bertipe `Integer` (bukan varchar, bukan time).

**Alasan:** Database harus mampu melakukan `SUM(time_spent_seconds)` secara instan untuk agregasi Burn-down Chart. Konversi ke format "2h 30m" dilakukan sepenuhnya di Frontend.

---

#### ADR-008: Sprint Metrics — Snapshot Pattern

**Keputusan:** Setelah sprint ditutup (CLOSED), data performa disimpan ke tabel `sprint_metrics` sebagai snapshot immutable.

**Alasan:** Query `SUM(story_points) WHERE status=DONE AND sprint_id=X` adalah operasi berat yang dipanggil berulang kali untuk tampilan _Team Velocity Chart_. Snapshot menghilangkan query berulang dan memastikan data historis tidak terpengaruh oleh edisi issue di masa depan.

---

#### ADR-009: Audit Trail — 3 Level

**Keputusan:** Sistem memiliki 3 level pencatatan yang terpisah dengan tujuan berbeda.

| Level | Tabel              | Trigger           | Tujuan                        |
| ----- | ------------------ | ----------------- | ----------------------------- |
| 1     | `user_access_logs` | HTTP Middleware   | Navigasi & akses halaman      |
| 2     | `audit_logs`       | Domain Events     | Aksi bisnis lintas modul      |
| 3     | `issue_histories`  | Application Layer | Field-level changes per tiket |

---

## 02-schema-and-erd

### WLMS — Schema & ERD (Complete)

**Versi:** 2.0 (Post-Audit)  
**Tanggal Update:** 2026-09-13

---

#### ERD Visual

![WLMS Complete ERD](/Users/robileksono/Sites/wlms/doc/database/erd-complete.jpg)

**Legend:**

- **Garis solid** → Physical Foreign Key (ada constraint di DB)
- **Garis putus-putus** → Soft Reference (UUID tanpa constraint, cross-module)

---

#### ERD Lengkap (Semua Modul)

```mermaid
erDiagram
    %% IDENTITY MODULE
    ORG_LEVEL {
        uuidv7 id PK
        varchar name
        varchar slug UK
        tinyint depth
        boolean is_leaf
        boolean can_own_workspace
        boolean is_active
    }

    ORG_UNIT {
        uuidv7 id PK
        uuidv7 parent_id FK
        uuidv7 org_level_id FK
        varchar name
        varchar code UK
        boolean is_active
    }

    ORG_UNIT_CLOSURE {
        uuidv7 ancestor_id PK
        uuidv7 descendant_id PK
        smallint depth
    }

    USER {
        bigint id PK
        uuidv7 uuid UK
        uuidv7 org_unit_id "Soft Ref"
        varchar name
        varchar email UK
        varchar status
        varchar employee_id UK
    }

    ROLE {
        uuidv7 id PK
        varchar name
        varchar scope "GLOBAL|WORKSPACE|PROJECT"
    }

    PERMISSION {
        uuidv7 id PK
        varchar name UK "e.g. 'issues:create'"
    }

    ROLE_PERMISSION {
        uuidv7 role_id PK
        uuidv7 permission_id PK
    }

    USER_ROLE {
        uuidv7 id PK
        bigint user_id FK
        uuidv7 role_id FK
        varchar context_type "Nullable"
        uuidv7 context_id "Soft Ref (Nullable)"
    }

    MENU {
        uuidv7 id PK
        uuidv7 parent_id FK
        varchar label
        varchar key UK
        varchar route
        smallint sort_order
        boolean is_active
    }

    ROLE_MENU {
        uuidv7 role_id PK
        uuidv7 menu_id PK
    }

    %% WORKLOAD MODULE — Lookup Tables
    ISSUE_TYPE {
        uuidv7 id PK
        varchar name
        varchar slug UK
        varchar icon
        varchar color
        smallint sort_order
    }

    PRIORITY {
        uuidv7 id PK
        varchar name
        varchar slug UK
        tinyint level UK
        varchar color
    }

    STATUS {
        uuidv7 id PK
        varchar name
        varchar slug UK
        varchar category "TODO|IN_PROGRESS|DONE"
        varchar color
    }

    ISSUE_LINK_TYPE {
        uuidv7 id PK
        varchar name
        varchar slug UK
        varchar inbound_name
        boolean is_active
    }

    %% WORKLOAD MODULE — Core
    WORKSPACE {
        uuidv7 id PK
        uuidv7 owner_group_id "Soft Ref → ORG_UNIT"
        varchar name
        varchar status "ACTIVE|ARCHIVED"
    }

    WORKFLOW {
        uuidv7 id PK
        varchar name
        boolean is_default
    }

    WORKFLOW_TRANSITION {
        uuidv7 id PK
        uuidv7 workflow_id FK
        uuidv7 from_status_id FK "Nullable"
        uuidv7 to_status_id FK
        varchar name
    }

    PROJECT {
        uuidv7 id PK
        uuidv7 workspace_id FK
        uuidv7 workflow_id FK
        varchar key UK
        varchar name
        varchar status
        uuidv7 lead_id "Soft Ref"
    }

    SPRINT {
        uuidv7 id PK
        uuidv7 project_id FK
        varchar name
        varchar state "PENDING|ACTIVE|CLOSED"
        int committed_points
        int completed_points
    }

    SPRINT_METRICS {
        uuidv7 id PK
        uuidv7 sprint_id UK FK
        int planned_points
        int completed_points
        smallint total_issues
        smallint completed_issues
        decimal completion_rate
        timestamp snapshot_at
    }

    ISSUE {
        uuidv7 id PK
        uuidv7 project_id FK
        uuidv7 sprint_id FK "Nullable"
        uuidv7 status_id FK
        uuidv7 issue_type_id FK
        uuidv7 priority_id FK
        bigint number "UK per project"
        varchar title
        smallint story_points "Nullable"
        json custom_fields
        uuidv7 reporter_id "Soft Ref"
        uuidv7 assignee_id "Soft Ref, Nullable"
    }

    ISSUE_LINK {
        uuidv7 id PK
        uuidv7 source_issue_id FK
        uuidv7 target_issue_id FK
        uuidv7 link_type_id FK
        uuidv7 created_by "Soft Ref"
    }

    WORKLOG {
        uuidv7 id PK
        uuidv7 issue_id FK
        uuidv7 author_id "Soft Ref"
        int time_spent_seconds
        datetime started_at
    }

    ISSUE_HISTORY {
        uuidv7 id PK
        uuidv7 issue_id FK
        uuidv7 actor_id "Soft Ref"
        varchar field_changed
        text old_value
        text new_value
        timestamp created_at
    }

    %% COLLABORATION MODULE
    AUDIT_LOG {
        uuidv7 id PK
        uuidv7 actor_id "Soft Ref, Nullable"
        varchar auditable_type
        uuidv7 auditable_id "Soft Ref"
        varchar event
        json old_values
        json new_values
        varchar ip_address
        timestamp created_at
    }

    COMMENT {
        uuidv7 id PK
        uuidv7 issue_id FK
        uuidv7 parent_id FK "Nullable"
        uuidv7 author_id "Soft Ref"
        longtext body
        boolean is_edited
    }

    %% SECURITY MODULE
    USER_ACCESS_LOG {
        bigint id PK
        uuidv7 user_id "Soft Ref"
        varchar method
        varchar url
        varchar route_name
        smallint response_code
        int duration_ms
        varchar ip_address
        timestamp accessed_at
    }

    %% IDENTITY RELATIONSHIPS
    ORG_UNIT }o--|| ORG_LEVEL : "has level"
    ORG_UNIT ||--o{ ORG_UNIT : "parent-child"
    ORG_UNIT ||--o{ ORG_UNIT_CLOSURE : "ancestors"
    ROLE ||--o{ ROLE_PERMISSION : "grants"
    PERMISSION ||--o{ ROLE_PERMISSION : "in"
    USER ||--o{ USER_ROLE : "has"
    ROLE ||--o{ USER_ROLE : "assigned via"
    ROLE ||--o{ ROLE_MENU : "has access to"
    MENU ||--o{ ROLE_MENU : "accessible by"
    MENU ||--o{ MENU : "parent-child"

    %% WORKLOAD RELATIONSHIPS
    WORKSPACE ||--o{ PROJECT : "contains"
    PROJECT ||--o{ SPRINT : "has"
    PROJECT ||--o{ ISSUE : "owns"
    SPRINT ||--o{ ISSUE : "contains"
    SPRINT ||--|| SPRINT_METRICS : "snapshot"
    WORKFLOW ||--o{ WORKFLOW_TRANSITION : "defines"
    STATUS ||--o{ WORKFLOW_TRANSITION : "from/to"
    ISSUE }o--|| STATUS : "current"
    ISSUE }o--|| ISSUE_TYPE : "typed by"
    ISSUE }o--|| PRIORITY : "prioritized by"
    ISSUE ||--o{ ISSUE_HISTORY : "audit trail"
    ISSUE ||--o{ WORKLOG : "time tracking"
    ISSUE ||--o{ ISSUE_LINK : "linked"
    ISSUE_LINK }o--|| ISSUE_LINK_TYPE : "type"
    ISSUE ||--o{ COMMENT : "comments"

    %% CROSS-MODULE SOFT REFS (dashed = no physical FK)
    WORKSPACE }o..|| ORG_UNIT : "owner_group_id (soft)"
    USER_ROLE }o..|| WORKSPACE : "context_id (soft)"
    USER_ROLE }o..|| PROJECT : "context_id (soft)"
```

---

#### Daftar Seluruh Tabel (18 Tabel)

### Modul: Identity

| Tabel               | Deskripsi                                      |
| ------------------- | ---------------------------------------------- |
| `org_levels`        | Dynamic level hierarki (menggantikan ENUM)     |
| `org_units`         | Node organisasi (Direksi, VP, Group, dll)      |
| `org_unit_closures` | Closure Table — engine query hierarki          |
| `roles`             | Peran sistem (scope: GLOBAL/WORKSPACE/PROJECT) |
| `permissions`       | Hak akses atomik (`resource:action`)           |
| `role_permissions`  | Pivot Role ↔ Permission                        |
| `user_roles`        | Context-Aware RBAC: User + Role + Context      |
| `menus`             | Navigasi dinamis hierarkis                     |
| `role_menus`        | Pivot Role ↔ Menu                              |

### Modul: Workload

| Tabel                  | Deskripsi                                   |
| ---------------------- | ------------------------------------------- |
| `workspaces`           | Container kerja — owned by Group            |
| `statuses`             | Status tiket (dikontrol Workflow)           |
| `workflows`            | Konfigurasi alur status                     |
| `workflow_transitions` | Aturan perpindahan status                   |
| `issue_types`          | Tipe tiket dinamis (Epic, Story, Task, Bug) |
| `priorities`           | Prioritas dinamis dengan `level` integer    |
| `projects`             | Proyek berisi Issues & Sprints              |
| `sprints`              | Time-boxed iteration Agile                  |
| `sprint_metrics`       | Snapshot performa sprint (immutable)        |
| `issues`               | Tiket / unit pekerjaan utama                |
| `issue_links`          | Dependensi antar tiket                      |
| `issue_link_types`     | Tipe relasi tiket (Blocks, Duplicates, dll) |
| `worklogs`             | Time tracking dalam satuan detik            |
| `issue_histories`      | Field-level audit trail per tiket           |

### Modul: Collaboration

| Tabel        | Deskripsi                           |
| ------------ | ----------------------------------- |
| `audit_logs` | Global audit trail (append-only)    |
| `comments`   | Komentar per tiket dengan threading |

### Modul: Security

| Tabel              | Deskripsi                                    |
| ------------------ | -------------------------------------------- |
| `user_access_logs` | Navigasi user (HTTP Middleware, high-volume) |

---

## 03-indexing-and-performance

### Indexing, Query Performance & Concurrency Guidelines

**Versi:** 2.0 (Post-Audit)  
**Tanggal Update:** 2026-09-13

---

#### 1. Strategi Indexing Per Tabel

### Identity Module

**`org_unit_closures`** — _Covering Index_ via Composite PK

```sql
PRIMARY KEY (ancestor_id, descendant_id)
INDEX (descendant_id, depth)  -- Reverse lookup: "siapa atasan node ini?"
```

**`user_roles`** — Context-Aware RBAC lookups

```sql
INDEX user_context_lookup_idx (user_id, context_type, context_id)
UNIQUE user_role_context_unique (user_id, role_id, context_type, context_id)
```

### Workload Module

**`issues`** — Tabel paling banyak di-query, paling kritis

```sql
UNIQUE issue_project_number_unique (project_id, number)  -- JIRA numbering
INDEX issue_board_view_idx (project_id, sprint_id, status_id)  -- Kanban board
INDEX issue_type_lookup_idx (issue_type_id)   -- FK lookup (FIX #5)
INDEX issue_priority_lookup_idx (priority_id)  -- FK lookup (FIX #5)
INDEX (assignee_id)  -- "All tickets assigned to User X"
INDEX (reporter_id)  -- "All tickets reported by User X"
```

**`issue_histories`** — Audit trail queries

```sql
INDEX issue_history_timeline_idx (issue_id, created_at)  -- Activity timeline
INDEX issue_history_field_idx (issue_id, field_changed)  -- Filter by field
```

**`worklogs`** — Burn-down chart aggregation

```sql
INDEX worklog_issue_author_idx (issue_id, author_id)  -- SUM(time_spent_seconds)
```

### Security Module

**`user_access_logs`** — High-volume navigation log

```sql
INDEX access_user_time_idx (user_id, accessed_at)   -- User activity timeline
INDEX access_response_time_idx (response_code, accessed_at)  -- Security: 403 alerts
INDEX access_route_idx (route_name)                 -- Traffic analysis
INDEX access_ip_idx (ip_address)                    -- Incident investigation
```

### Collaboration Module

**`audit_logs`** — Compliance queries

```sql
INDEX audit_actor_idx (actor_id)
INDEX audit_morphs_idx (auditable_type, auditable_id)
INDEX audit_event_time_idx (event, created_at)
```

---

#### 2. Concurrency Control — JIRA Issue Numbering

Tiket JIRA (e.g., `WLMS-123`) dibentuk dari `Project.key` + `Issue.number`. Kolom `number` harus naik urut per project tanpa duplikasi meskipun ada request bersamaan.

**ATURAN BACKEND — WAJIB menggunakan Pessimistic Locking:**

```php
// ❌ DILARANG — Race condition saat 2 user create tiket bersamaan
$newNumber = Issue::where('project_id', $projectId)->max('number') + 1;

// ✅ WAJIB — Pessimistic Lock mencegah race condition
DB::transaction(function () use ($projectId, $data) {
    // Lock baris project agar tidak ada thread lain yang bisa membaca max(number)
    // di saat yang sama sebelum INSERT selesai
    $project = DB::table('projects')
        ->where('id', $projectId)
        ->lockForUpdate()
        ->first();

    $lastNumber = DB::table('issues')
        ->where('project_id', $projectId)
        ->max('number') ?? 0;

    DB::table('issues')->insert(array_merge($data, [
        'project_id' => $projectId,
        'number'     => $lastNumber + 1,
    ]));
});
// Database UNIQUE constraint (project_id, number) sebagai safety net terakhir
```

---

#### 3. Cross-Module Query Strategy (2-Step Pattern)

Karena tidak ada Physical FK antar modul, query lintas modul menggunakan pola **2-Step Query** untuk mempertahankan batas bounded context:

**Contoh: VP ingin melihat semua Workspace di bawah hierarkinya**

```sql
-- STEP 1: Query ke Identity Module
-- Ambil semua OrgUnit yang bisa punya Workspace, di bawah VP ini
SELECT c.descendant_id AS group_id
FROM org_unit_closures c
INNER JOIN org_units u ON c.descendant_id = u.id
INNER JOIN org_levels l ON u.org_level_id = l.id
WHERE c.ancestor_id = :vp_org_unit_id
  AND l.can_own_workspace = true;
-- Hasil: ['uuid-group-1', 'uuid-group-2', ...]

-- STEP 2: Query ke Workload Module
-- Gunakan hasil array dari Step 1
SELECT id, name, status
FROM workspaces
WHERE owner_group_id IN (:group_ids)
  AND status = 'ACTIVE';
```

**Contoh: Cek permission user sebelum aksi bisnis**

```php
// Di Application Layer UseCase
public function execute(CreateIssueInput $input): void
{
    // Step 1 — Identity check (bisa di-cache Redis 5 menit)
    $hasPermission = $this->rbacService->userHasPermission(
        userId: $input->actorId,
        permission: 'issues:create',
        contextType: 'PROJECT',
        contextId: $input->projectId
    );

    if (!$hasPermission) {
        throw new AuthorizationException();
    }

    // Step 2 — Workload mutation
    // ...
}
```

---

#### 4. Sprint Metrics — Snapshot vs Live Query

**JANGAN** menghitung velocity secara live dari tabel `issues` setiap kali laporan dibuka:

```sql
-- ❌ JANGAN — Query berat yang dipanggil berulang di setiap request laporan
SELECT SUM(story_points)
FROM issues
WHERE sprint_id = :sprint_id
  AND status_id IN (SELECT id FROM statuses WHERE category = 'DONE');
```

**GUNAKAN** tabel `sprint_metrics` (snapshot immutable yang dibuat saat sprint di-close):

```sql
-- ✅ GUNAKAN — O(1) lookup dari pre-calculated snapshot
SELECT completed_points, planned_points, completion_rate
FROM sprint_metrics
WHERE sprint_id = :sprint_id;
```

`SprintMetrics` di-generate oleh `CloseSprint UseCase` saat admin menutup sprint. Data ini bersifat **immutable** — tidak berubah meskipun issues diedit setelah sprint ditutup.

---

#### 5. High-Volume Table — Retention Strategy

Tabel `user_access_logs` berpotensi sangat besar. Terapkan strategi retensi data:

```sql
-- Hapus log akses yang lebih dari 90 hari (via Artisan Command terjadwal)
DELETE FROM user_access_logs
WHERE accessed_at < NOW() - INTERVAL 90 DAY
LIMIT 10000;  -- Batasi per eksekusi agar tidak lock tabel terlalu lama
```

**Di Laravel Scheduler:**

```php
// app/Console/Kernel.php
$schedule->command('logs:purge-access --days=90')->daily()->at('02:00');
```

Pertimbangkan partisi tabel per bulan di skala produksi > 100 juta baris.

---

#### 6. Query Caching Recommendations

| Query                                   | Cache TTL   | Alasan                         |
| --------------------------------------- | ----------- | ------------------------------ |
| RBAC Permission check per user          | 5 menit     | Dipanggil setiap request       |
| Closure Table descendants               | 10 menit    | Struktur org jarang berubah    |
| Lookup tables (issue_types, priorities) | 1 jam       | Data sangat jarang berubah     |
| sprint_metrics                          | Permanent   | Immutable setelah sprint close |
| user_access_logs writes                 | Queue async | Jangan block HTTP response     |

---

## workspace-event-catalog

### Workspace Event Catalog

Domain Events yang dipicu oleh modul Workload (Workspace Feature).
Semua event ini bersifat _asynchronous_ dan ditangani via Queue.

| Event Class        | Publisher             | Payload Properties                                                      | Listener(s)                      | Tujuan                                                  |
| ------------------ | --------------------- | ----------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------- |
| `WorkspaceCreated` | `Workspace::create()` | `workspaceId`, `ownerGroupId`, `actorId`, `workspaceName`, `occurredAt` | `WriteWorkspaceAuditLogListener` | Mencatat histori ke `audit_logs` table (Zero Data Loss) |

---

