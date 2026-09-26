# Database Architectural Decision Records (ADR)

**Versi:** 2.0 (Post-Audit)  
**Tanggal Update:** 2026-09-13

---

## ADR-001: Primary Key — UUIDv7

**Keputusan:** Wajib menggunakan **UUIDv7** untuk semua tabel entitas bisnis.

**Pengecualian yang diizinkan:**

- `user_access_logs` menggunakan `BigInt Auto-increment` karena tabel ini adalah log volume tinggi (bukan entitas bisnis) dan membutuhkan INSERT performa maksimal.
- Tabel pivot murni (e.g., `role_permissions`, `role_menus`) menggunakan Composite PK tanpa UUID.

**Alasan:**

- _BigInt_ berbahaya untuk sharding/distribusi.
- _UUIDv4_ menyebabkan B-Tree page splitting dan fragmentasi index.
- _UUIDv7_ bersifat _time-ordered_, menggabungkan keunggulan sequential insert (seperti BigInt) dengan keamanan distribusi (seperti UUID).

---

## ADR-002: Zero Cross-Module Foreign Keys

**Keputusan:** Dilarang menggunakan SQL Foreign Key Constraint yang menyilang antar bounded context (modul).

**Modul yang diakui:**

- `Identity`: `org_levels`, `org_units`, `org_unit_closures`, `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `menus`, `role_menus`
- `Workload`: `workspaces`, `projects`, `sprints`, `statuses`, `workflows`, `workflow_transitions`, `issue_types`, `priorities`, `issues`, `issue_links`, `issue_link_types`, `worklogs`, `issue_histories`, `sprint_metrics`
- `Collaboration`: `audit_logs`, `comments`
- `Security`: `user_access_logs`

**Aturan:** FK boleh ada hanya di dalam modul yang sama. Referensi lintas modul disimpan sebagai UUID biasa (Soft Reference) tanpa constraint.

---

## ADR-003: Hierarchical Data Model — Closure Table

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

## ADR-004: Dynamic OrgLevel — Anti-Hardcode Hierarchy

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

## ADR-005: Anti-ENUM — Semua Klasifikasi Bisnis Pakai Lookup Table

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

## ADR-006: Anti-EAV — Custom Fields via JSON/JSONB

**Keputusan:** Custom Fields pada `issues` disimpan sebagai kolom `json` (satu kolom), bukan tabel EAV.

**Alasan:** Tabel EAV memerlukan N JOIN untuk mengambil N atribut custom dari satu tiket. Di sistem dengan ribuan tiket dan puluhan custom field, ini = "Join of Death". `JSONB` di PostgreSQL dan `JSON` di MySQL 8+ mendukung indexing parsial untuk atribut spesifik.

---

## ADR-007: Time Tracking — Integer Seconds

**Keputusan:** Kolom `worklogs.time_spent_seconds` wajib bertipe `Integer` (bukan varchar, bukan time).

**Alasan:** Database harus mampu melakukan `SUM(time_spent_seconds)` secara instan untuk agregasi Burn-down Chart. Konversi ke format "2h 30m" dilakukan sepenuhnya di Frontend.

---

## ADR-008: Sprint Metrics — Snapshot Pattern

**Keputusan:** Setelah sprint ditutup (CLOSED), data performa disimpan ke tabel `sprint_metrics` sebagai snapshot immutable.

**Alasan:** Query `SUM(story_points) WHERE status=DONE AND sprint_id=X` adalah operasi berat yang dipanggil berulang kali untuk tampilan _Team Velocity Chart_. Snapshot menghilangkan query berulang dan memastikan data historis tidak terpengaruh oleh edisi issue di masa depan.

---

## ADR-009: Audit Trail — 3 Level

**Keputusan:** Sistem memiliki 3 level pencatatan yang terpisah dengan tujuan berbeda.

| Level | Tabel              | Trigger           | Tujuan                        |
| ----- | ------------------ | ----------------- | ----------------------------- |
| 1     | `user_access_logs` | HTTP Middleware   | Navigasi & akses halaman      |
| 2     | `audit_logs`       | Domain Events     | Aksi bisnis lintas modul      |
| 3     | `issue_histories`  | Application Layer | Field-level changes per tiket |
