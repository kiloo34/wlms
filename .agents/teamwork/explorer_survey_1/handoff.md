# Handoff Report: Backend Architecture & Domain Aggregations Survey

**Author**: Explorer Survey 1 (Backend Architecture & Domain Aggregations)  
**Date**: 2026-10-08  
**Scope**: Exploration of Laravel backend architecture, domain models, database schema, queries, Clean Architecture patterns, and RBAC authorization for Reporting & Analytics Module (Requirements R1, R2, R3).

---

## 1. Observation

### 1.1 Architecture & Module Structure
- **Modular Monolith with Clean Architecture**: Located in `app/Modules/`.
  - Modules: `Workload`, `Identity`, `Collaboration`, `Notification`, `KnowledgeBase`.
  - Service provider registration: `app/Providers/ModuleServiceProvider.php` (lines 21–51) dynamically registers each `<Module>ServiceProvider` and loads migrations from `<Module>/Infrastructure/Database/Migrations`.
  - `App\Modules\Workload` is divided strictly into 4 layers:
    - `Domain`: Entities (`Workspace`, `Project`, `Issue`, `Sprint`, `Status`, `Workflow`), Value Objects, Domain Events, Repositories (interfaces).
    - `Application`: DTOs (`*Input`, `*Output`), Use Cases (commands), Queries (read models/CQRS), Jobs.
    - `Infrastructure`: Eloquent models, repository implementations, Auth policies, Event listeners, Migrations.
    - `Presentation`: Controllers (Single-Action `__invoke` and resource controllers), Requests (FormRequests with `authorize()` and `rules()`), Resources (`JsonResource`).
- **Pragmatic CQRS / Read-Model Bypass**:
  - In `app/Modules/Workload/Application/UseCases/GetProjectsUseCase.php` (line 18):
    > *"Pragmatic CQRS/Performance: Query Builder langsung untuk performa (tanpa hydration yang berat)"*
  - In `rules/domain_blueprint.md` (lines 201–203):
    > *"Repositories: Menggunakan Query Builder langsung (Bypass CQRS) untuk reporting yang kompleks dan agregasi berat."*
  - Read queries for dashboards bypass write-model domain entity hydration, executing optimized `DB::table(...)` SQL aggregations directly to return typed DTOs/arrays.

### 1.2 Routes & Entry Points
- `routes/api.php` imports module route files:
  - Line 11: `require base_path('app/Modules/Workload/Presentation/Http/routes.php');`
- `app/Modules/Workload/Presentation/Http/routes.php`:
  - Line 35: `Route::middleware(['auth:sanctum'])->prefix('workspaces')->group(...)` contains routes such as `GET /{workspaceId}/projects`, `GET /{workspaceId}/issues`, `GET /{workspace_id}/members`.
  - Line 50: `Route::middleware(['auth:sanctum'])->prefix('projects')->group(...)` contains `GET /{id}/board`, `GET /{id}/backlog`, `GET /{project_id}/issues`, `GET /{id}/sprints`.
  - Current status: Endpoints `GET /api/workspaces/{id}/analytics` and `GET /api/projects/{id}/analytics` do **not** exist yet (Greenfield implementation).

### 1.3 Database Schema, Models, and Columns
From inspecting migrations in `app/Modules/Workload/Infrastructure/Database/Migrations/` and `database/migrations/`:
- **`workspaces`** (`WorkspaceModel.php`):
  - Primary Key: UUID (`id`)
  - Columns: `id`, `owner_group_id` (UUID), `name`, `description`, `status` (`ACTIVE|ARCHIVED`), `settings` (JSON), `created_at`, `updated_at`, `deleted_at` (soft deletes).
  - Relationships: `members()` belongs-to-many `UserModel` via pivot table `workspace_members`.
- **`workspace_members`**:
  - Columns: `id`, `workspace_id` (UUID FK), `user_id` (BigInteger FK to `users`), `role` (`viewer|member|admin`), `daily_capacity_hours` (integer, default 8), timestamps.
- **`projects`** (`ProjectModel.php`):
  - Primary Key: UUID (`id`)
  - Columns: `id`, `workspace_id` (UUID FK), `workflow_id` (UUID FK nullable), `priority_id` (UUID FK), `key` (string 10 unique, e.g. 'PROJ'), `name`, `description`, `status` (`ACTIVE|ARCHIVED`), `lead_id` (BigInteger nullable), `start_date` (date nullable), `end_date` (date nullable), `created_at`, `updated_at`, `deleted_at`.
  - Relationships: `issues()` has-many `IssueModel`, `priority()` belongs-to `PriorityModel`, `workspace()` belongs-to `WorkspaceModel`.
- **`issues`** (`IssueModel.php`):
  - Primary Key: UUID (`id`)
  - Columns:
    - `id`, `project_id` (UUID FK), `sprint_id` (UUID FK nullable), `status_id` (UUID FK)
    - `number` (BigInteger, e.g., 1, 2)
    - `title` (string 255), `description` (longText nullable)
    - `issue_type_id` (UUID FK to `issue_types`)
    - `priority_id` (UUID FK to `priorities`)
    - `custom_fields` (JSON nullable)
    - `story_points` (SmallInteger nullable)
    - `original_estimate_seconds` (Integer nullable)
    - `remaining_estimate_seconds` (Integer nullable)
    - `reporter_id` (BigInteger), `assignee_id` (BigInteger nullable)
    - `start_date` (date nullable), `due_date` (date nullable)
    - `created_at`, `updated_at`, `deleted_at`.
- **`statuses`** (`StatusModel.php`):
  - Columns: `id` (UUID), `name`, `slug`, `category` (`TODO|IN_PROGRESS|DONE`), `color`, timestamps.
  - Category is strictly uppercase: `'TODO'`, `'IN_PROGRESS'`, `'DONE'` (verified in `WorkflowSeeder.php` line 25 and `UpdateStatusRequest.php` line 27).
- **`issue_types`** (`IssueTypeModel.php`):
  - Columns: `id` (UUID), `name`, `slug`, `icon`, `color`, `sort_order`, `is_active`, timestamps.
- **`priorities`** (`PriorityModel.php`):
  - Columns: `id` (UUID), `name`, `slug`, `level` (tinyInteger 1=Low, 2=Medium, 3=High, 4=Critical, 5=Blocker), `icon`, `color`, `is_active`, timestamps.
- **`sprints`** (`SprintModel.php`):
  - Columns: `id` (UUID), `project_id` (UUID FK), `name`, `goal`, `state` (`PENDING|ACTIVE|COMPLETED`), `start_date` (dateTime), `end_date` (dateTime), `committed_points` (unsignedInt), `completed_points` (unsignedInt), timestamps.
- **`sprint_metrics`** (`2026_09_13_100010_fix_issue_link_types_and_sprint_metrics.php` line 52):
  - Columns: `id` (UUID), `sprint_id` (UUID unique), `planned_points`, `completed_points`, `total_issues`, `completed_issues`, `completion_rate` (decimal 5,2), `snapshot_at`.
- **`worklogs`** (`WorklogModel.php`):
  - Columns: `id` (UUID), `issue_id` (UUID FK), `author_id` (BigInteger FK to `users`), `time_spent_seconds` (unsignedInt), `description`, `started_at` (dateTime), timestamps.
- **`issue_histories`** (`IssueHistoryModel.php`):
  - Columns: `id` (UUID), `issue_id` (UUID FK), `actor_id` (BigInteger), `field_changed` (string 50), `old_value` (text nullable), `new_value` (text nullable), `created_at` (timestamp).
  - In `WriteIssueAuditLogListener.php` (lines 16–25): every status transition records `field_changed = 'status_id'`, `old_value = $event->fromStatusId`, `new_value = $event->toStatusId`, `created_at = $event->occurredAt`.

### 1.4 RBAC & Authorization Rules
- `UserModel.php`:
  - `hasRole(string $roleName)`: checks if role matches (case-insensitive).
  - `hasPermission(string $permissionName)`: returns `true` if role is `'Superadmin'` or if any role permission matches `$permissionName`.
  - Roles & Permissions seeded in `MenuAndPermissionSeeder.php`:
    - Permissions: `workspaces:view`, `workspaces:manage`, `projects:view`, `projects:manage`, `issues:view`, `issues:manage`.
- `WorkspacePolicy.php`:
  - `view(UserModel $user, WorkspaceModel $workspace)`:
    Returns `true` if:
    1. User is Superadmin (`$user->email === 'superadmin@wlms.com'` or `hasRole('Superadmin')`), OR
    2. User's `org_unit_id === $workspace->owner_group_id` (Internal Member / Workspace Owner Group), OR
    3. User is in `workspace_members` (`$workspace->members()->where('user_id', $user->id)->exists()`).
- Project Authorization Pattern (from `GetProjectsUseCase.php` lines 37–82 and `ProjectManagementApiSecurityTest.php`):
  - Superadmin has access.
  - Workspace internal members (`$user->org_unit_id === $workspace->owner_group_id`) and Workspace Admins (`workspace_members.role = 'admin'`) have access.
  - Project Leads (`projects.lead_id === $user->id`) have access.
  - Users with context role on project in `user_roles` (`context_id = $project->id, context_type = 'PROJECT'`) have access.
  - Users assigned to any issue in the project (`issues.assignee_id === $user->id`) have access.
  - Workspace members with `projects:view` have access.
  - Normal unauthorized users receive HTTP 403 Forbidden.

### 1.5 Test Environment & PHPStan Setup
- `phpunit.xml`:
  - Lines 26–27: `<env name="DB_CONNECTION" value="sqlite"/>`, `<env name="DB_DATABASE" value=":memory:"/>`.
  - **Crucial**: Automated tests run on SQLite in-memory. Queries must NOT use MySQL-specific functions (such as `DATE_FORMAT()`, `WEEK()`, etc.).
- `phpstan.neon`:
  - Level 7 enabled on `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`.
  - Current baseline: 0 errors (`./vendor/bin/phpstan analyse --no-progress` passes cleanly).

---

## 2. Logic Chain

1. **Schema Sufficiency**:
   - Examination of the schema shows that all fields needed for R1 and R2 already exist:
     - `issues.original_estimate_seconds`, `issues.remaining_estimate_seconds`, `worklogs.time_spent_seconds` -> Member workload hours.
     - `statuses.category = 'DONE'` and `issue_histories.field_changed = 'status_id'` -> Throughput, lead time, cycle time.
     - `sprints.committed_points`, `completed_points`, `sprint_metrics` -> Sprint velocity & burndown.
     - `priorities.level`, `priorities.color`, `issue_types.slug`, `issue_types.color` -> Distribution breakdowns.
   - Conclusion: **Zero new database migrations are required**.

2. **Clean Architecture Compliance**:
   - Following the existing `Workload` module CQRS architecture:
     - Application Layer should have typed Query classes:
       - `App\Modules\Workload\Application\Queries\GetWorkspaceAnalyticsQuery`
       - `App\Modules\Workload\Application\Queries\GetProjectAnalyticsQuery`
     - Application Layer DTOs:
       - `App\Modules\Workload\Application\DTOs\WorkspaceAnalyticsInput`
       - `App\Modules\Workload\Application\DTOs\WorkspaceAnalyticsOutput`
       - `App\Modules\Workload\Application\DTOs\ProjectAnalyticsInput`
       - `App\Modules\Workload\Application\DTOs\ProjectAnalyticsOutput`
     - Presentation Layer:
       - `App\Modules\Workload\Presentation\Http\Requests\GetWorkspaceAnalyticsHttpRequest`
       - `App\Modules\Workload\Presentation\Http\Requests\GetProjectAnalyticsHttpRequest`
       - `App\Modules\Workload\Presentation\Http\Controllers\GetWorkspaceAnalyticsController`
       - `App\Modules\Workload\Presentation\Http\Controllers\GetProjectAnalyticsController`

3. **Performance & SQL Optimization (Zero N+1, < 200ms)**:
   - To achieve sub-200ms execution and zero N+1 overhead:
     - **For Workspace Macro Analytics**:
       - Query 1: Single aggregation on `projects` LEFT JOIN `issues` and `statuses` grouped by `project.id` to compute project progress, open/completed counts, and overdue status.
       - Query 2: Single aggregation on completed issues in the workspace grouped by completion date to compute throughput.
       - Query 3: Workload aggregation joining `issues` grouped by `assignee_id` to get total tasks, completed tasks, estimated seconds, and remaining seconds.
       - Query 4: Worklog aggregation from `worklogs` grouped by `author_id` within the date range to get logged seconds.
       - Query 5: Fetch workspace members from `workspace_members` JOIN `users` to get member metadata and `daily_capacity_hours`.
       - Combine results in memory (O(M + K) where M = projects count, K = members count). Total query count: 5 queries regardless of dataset size (0 N+1).
     - **For Project Deep Dive Analytics**:
       - Query 1: Completed issues for project with `created_at` and completion timestamp from `issue_histories` (fallback `updated_at`). Lead time & cycle time percentiles (p50, p85) and distribution buckets calculated in PHP.
       - Query 2: CFD status timeline: query status transitions from `issue_histories` for all project issues up to the end date, compute day-by-day counts across `TODO`, `IN_PROGRESS`, `DONE`.
       - Query 3: Issue type breakdown (`COUNT(*) GROUP BY issue_type_id`).
       - Query 4: Priority breakdown (`COUNT(*) GROUP BY priority_id`).
       - Query 5: Sprint burndown and velocity: lookup active sprint issues / daily remaining points, and query `sprint_metrics` or completed sprints for velocity history. If no sprints, compute continuous flow throughput.
       - Total query count: 5 queries. Constant time overhead, well below 50ms.

4. **Cross-Database Compatibility (SQLite Testing vs MySQL Production)**:
   - Running tests in SQLite `:memory:` requires avoiding vendor-specific functions:
     - Do not use `DATE_FORMAT(col, '%Y-%u')` or `TIMESTAMPDIFF()`.
     - Standard SQL: use `DATE(datetime_col)` or fetch timestamps and group weeks/months in PHP using `CarbonImmutable`.
     - In PHP, `Carbon::parse($date)->format('Y-\WW')` and `Carbon::parse($date)->format('M Y')` reliably format weeks and months with zero database coupling.

5. **RBAC Enforcement**:
   - `GetWorkspaceAnalyticsHttpRequest`:
     - Must verify user has `workspaces:view` or passes `WorkspacePolicy::view` (superadmin, owner group, or workspace member).
     - If unauthorized, FormRequest `authorize()` returns `false` -> Laravel automatically throws 403 HTTP response.
   - `GetProjectAnalyticsHttpRequest`:
     - Must verify user has access to project or its parent workspace.
     - If user is not member of workspace, not project lead, not assigned, and not superadmin -> returns `false` -> 403 Forbidden.

---

## 3. Caveats

1. **Historical Status Transitions**:
   - For issues created before history logging or in simple test seeders where `issue_histories` records are not manually seeded, lead time and throughput queries should gracefully fallback to `issues.updated_at` (or `issues.created_at`) when no history records exist.
2. **Worklogs Optionality**:
   - Worklogs may be empty if team members don't log timesheets. Member workload must display `estimated_hours` alongside `logged_hours` (using `0` when no worklogs exist).
3. **Empty States**:
   - Brand new workspaces with 0 projects or projects with 0 issues must return structured responses with zeroes and empty arrays (`[]`), without division-by-zero errors or null pointer exceptions.
4. **Project Health Definition**:
   - Health is not a database column; it is a calculated status:
     - `'completed'`: 100% completed.
     - `'at_risk'`: overdue open issues exist or project `end_date` has elapsed with open issues.
     - `'on_track'`: active work progressing within schedule.

---

## 4. Conclusion

The WLMS backend codebase is well-structured and strictly adheres to Clean Architecture. All data structures required for the Reporting & Analytics module already exist. No schema changes or new migrations are necessary.

### Implementation Blueprint for Implementation Agents:

#### 1. DTOs (`app/Modules/Workload/Application/DTOs/`):
- `WorkspaceAnalyticsInput`: `workspaceId`, `dateRange` (`'7d'|'30d'|'quarter'|'custom'`), `startDate` (`?CarbonImmutable`), `endDate` (`?CarbonImmutable`), `actorUserId`.
- `WorkspaceAnalyticsOutput`: Structured array containing:
  - `summary`: total projects, active projects, total issues, completed issues, overall progress percentage.
  - `projects`: array of project cards (`id`, `key`, `name`, `status`, `total_issues`, `completed_issues`, `progress_percent`, `health_status`, `start_date`, `end_date`).
  - `throughput`: time-series array of completed issues per week/month within date range.
  - `member_workload`: array of assignee workload (`user_id`, `name`, `email`, `task_count`, `completed_task_count`, `estimated_hours`, `logged_hours`, `capacity_hours`, `utilization_rate`).
  - `filters`: active date range and resolved start/end dates.
- `ProjectAnalyticsInput`: `projectId`, `dateRange`, `startDate`, `endDate`, `sprintId`, `actorUserId`.
- `ProjectAnalyticsOutput`: Structured array containing:
  - `project`: `id`, `key`, `name`, `status`.
  - `lead_time`: summary metrics (`average_days`, `median_days`, `p85_days`) and distribution histogram (`<1d`, `1-3d`, `4-7d`, `8-14d`, `15d+`).
  - `cycle_time`: summary metrics (`average_days`, `median_days`, `p85_days`) and distribution histogram.
  - `cumulative_flow`: time-series array of `{ date, todo, in_progress, done }`.
  - `issue_types`: breakdown array `{ id, name, slug, count, color, icon }`.
  - `priorities`: breakdown array `{ id, name, slug, level, count, color }`.
  - `statuses`: breakdown array `{ id, name, slug, category, count, color }`.
  - `sprint_metrics`:
    - If sprints exist: active sprint burndown (daily ideal vs remaining points) + team velocity history (past completed sprints points).
    - If kanban (no sprints): continuous flow throughput (rolling weekly throughput) + WIP count.

#### 2. Query Classes (`app/Modules/Workload/Application/Queries/`):
- `GetWorkspaceAnalyticsQuery`: Implements the 5-query aggregation pipeline using `DB::table(...)` with zero N+1.
- `GetProjectAnalyticsQuery`: Implements project deep dive queries with portable SQLite/MySQL syntax.

#### 3. Presentation Layer:
- `GetWorkspaceAnalyticsHttpRequest` & `GetProjectAnalyticsHttpRequest` in `app/Modules/Workload/Presentation/Http/Requests/`:
  - Enforce RBAC in `authorize()`.
  - Validate `date_range` in `rules()`: `in:7d,30d,quarter,custom`.
- `GetWorkspaceAnalyticsController` & `GetProjectAnalyticsController` in `app/Modules/Workload/Presentation/Http/Controllers/`:
  - Single-action controllers returning JsonResponse.
- Routes in `app/Modules/Workload/Presentation/Http/routes.php`:
  - `Route::get('/{id}/analytics', GetWorkspaceAnalyticsController::class);` under `workspaces` prefix.
  - `Route::get('/{id}/analytics', GetProjectAnalyticsController::class);` under `projects` prefix.

---

## 5. Verification Method

### 5.1 Test Suite Verification
Run automated Pest tests covering analytics calculations, date filtering, empty states, and 403 unauthorized access:
```bash
./vendor/bin/pest tests/Feature/Modules/Workload/AnalyticsTest.php
```

To run the full suite and confirm no regressions:
```bash
./vendor/bin/pest tests/Feature/Modules/Workload/
```

### 5.2 Static Analysis Verification
Analyze the entire codebase at PHPStan level 7:
```bash
./vendor/bin/phpstan analyse --no-progress
```
Expected result: `[OK] No errors`.

### 5.3 Invalidation Conditions
- If any analytics endpoint generates $N$ queries inside loops (violates Acceptance Criteria R3).
- If tests fail when running in SQLite `:memory:` due to MySQL-specific syntax (e.g. `DATE_FORMAT`).
- If an unauthenticated or unauthorized user receives anything other than HTTP 403 / 401.
- If PHPStan reports any errors at level 7.
