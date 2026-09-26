# WLMS — Schema & ERD (Complete)

**Versi:** 2.0 (Post-Audit)  
**Tanggal Update:** 2026-09-13

---

## ERD Visual

![WLMS Complete ERD](/Users/robileksono/Sites/wlms/doc/database/erd-complete.jpg)

**Legend:**

- **Garis solid** → Physical Foreign Key (ada constraint di DB)
- **Garis putus-putus** → Soft Reference (UUID tanpa constraint, cross-module)

---

## ERD Lengkap (Semua Modul)

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

## Daftar Seluruh Tabel (18 Tabel)

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
