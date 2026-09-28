# Indexing, Query Performance & Concurrency Guidelines

**Versi:** 2.0 (Post-Audit)  
**Tanggal Update:** 2026-09-13

---

## 1. Strategi Indexing Per Tabel

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

## 2. Concurrency Control — JIRA Issue Numbering

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

## 3. Cross-Module Query Strategy (2-Step Pattern)

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

## 4. Sprint Metrics — Snapshot vs Live Query

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

## 5. High-Volume Table — Retention Strategy

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

## 6. Query Caching Recommendations

| Query                                   | Cache TTL   | Alasan                         |
| --------------------------------------- | ----------- | ------------------------------ |
| RBAC Permission check per user          | 5 menit     | Dipanggil setiap request       |
| Closure Table descendants               | 10 menit    | Struktur org jarang berubah    |
| Lookup tables (issue_types, priorities) | 1 jam       | Data sangat jarang berubah     |
| sprint_metrics                          | Permanent   | Immutable setelah sprint close |
| user_access_logs writes                 | Queue async | Jangan block HTTP response     |
