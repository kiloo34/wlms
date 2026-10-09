# Quality Gates & Testing QA Infrastructure Survey (Survey 3)

## 1. Observation

### 1.1 Test Infrastructure & Framework Setup
- **Test Framework**: Pest PHP with `pestphp/pest-plugin-laravel` and PHPUnit `12.5.23` running on PHP `8.5.0` (`composer.json`: lines 33-35).
- **Test Configuration**:
  - `tests/Pest.php`: lines 5-8 bind `Tests\TestCase::class` to `Feature` tests. `RefreshDatabase::class` is commented out globally, requiring each Feature test to declare `uses(Illuminate\Foundation\Testing\RefreshDatabase::class);`.
  - `phpunit.xml`: lines 20-35 define testing environment:
    - `<env name="DB_CONNECTION" value="sqlite"/>`
    - `<env name="DB_DATABASE" value=":memory:"/>`
    - `<env name="CACHE_STORE" value="array"/>`
    - `<env name="QUEUE_CONNECTION" value="sync"/>`
- **Execution Benchmark**:
  - Full Pest test suite (`./vendor/bin/pest`): 104 tests, 349 assertions passed in 1,012ms.
  - Workload module Feature tests (`./vendor/bin/pest tests/Feature/Modules/Workload/`): 49 tests passed in 558ms.
  - Core Workload Feature tests (`./vendor/bin/pest tests/Feature/Workload/`): 12 tests passed in 267ms.
- **Factory Availability**:
  - Only **one** model factory exists in the entire application: `App\Modules\Identity\Infrastructure\Persistence\Eloquent\Factories\UserFactory.php`.
  - No factories exist for Workload entities (`workspaces`, `projects`, `issues`, `sprints`, `worklogs`, `statuses`, `issue_types`, `priorities`).
  - Existing tests (e.g. `tests/Feature/Modules/Workload/ProjectManagementApiSecurityTest.php`, `tests/Feature/Workload/IssueManagementTest.php`, `tests/Feature/Modules/Workload/VisibilityComprehensiveTest.php`) create fixtures via direct `DB::table(...)->insert(...)` or Eloquent `Model::create(...)`.
- **Seeders Availability**:
  - `Database\Seeders\WorkloadLookupSeeder`: seeds standard issue types (Epic, Story, Task, Bug, Subtask), priorities (Lowest to Critical), and statuses (To Do, In Progress, In Review, In QA, Done) using idempotent `upsert`.
  - `App\Modules\Workload\Infrastructure\Database\Seeders\WorkflowSeeder`: seeds statuses, default workflow, transitions (Create, Start Progress, Send to Review, Approve, Request Changes, Reopen), issue types, and priorities.

### 1.2 Static Analysis (PHPStan Level 7)
- **Configuration** (`phpstan.neon`: lines 1-14):
  - Includes `vendor/larastan/larastan/extension.neon` and `vendor/nesbot/carbon/extension.neon`.
  - Target paths: `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`.
  - Target level: `level: 7`.
- **Command & Memory Observation**:
  - Running `./vendor/bin/phpstan analyse --debug` with standard PHP CLI memory limit fails silently with exit code `255` (memory exhaustion from holding all ASTs in a single debug process).
  - Running `./vendor/bin/phpstan analyse` without flags hits parallel socket restriction in sandboxed execution: `Connection to tcp://127.0.0.1:58208 failed: Operation not permitted (EPERM)`.
  - Running `./vendor/bin/phpstan analyse --debug --memory-limit=2G` executes cleanly in 4.3 seconds with **0 errors**:
    ```json
    {"tool":"phpstan","result":"passed","errors":0}
    ```
- **Code Style** (`./vendor/bin/pint --test`): passed with 0 errors across all files.

### 1.3 Frontend Quality Gates & Toolchain
- **Package Scripts** (`package.json`: lines 5-13):
  - `"types:check": "tsc --noEmit"`
  - `"build": "vp build"` (Vite-plus 0.3.0 / Vite 8.0.0)
  - `"check": "vp check"`
- **TypeScript Configuration** (`tsconfig.json`: lines 2-120):
  - Target `ESNext`, strict mode `true`, `noImplicitAny: true`, module resolution `bundler`, JSX `react-jsx`.
  - Include pattern: `"resources/js/**/*.ts"`, `"resources/js/**/*.d.ts"`, `"resources/js/**/*.tsx"`.
- **Execution Benchmark**:
  - `npm run types:check`: finished in 1.4s with **0 errors**.
  - `npm run build`: built all production bundles (client assets and CSS) in 2.52s with **0 errors**.
- **Appearance & Density Systems**:
  - `resources/js/hooks/use-appearance.tsx`:
    - `useAppearance()` exports `{ appearance, resolvedAppearance, updateAppearance, iconSize, updateIconSize }`.
    - `resolvedAppearance`: `'light' | 'dark'`.
    - `useIconSize()` exports `{ iconSize, updateIconSize }` with values `'sm' | 'md' | 'lg'`.
    - Applies `data-icon-size` attribute and `.icon-size-{size}` classes to `document.documentElement`.
  - `resources/js/hooks/useTranslate.ts`:
    - `useTranslate()` exports `{ t, locale }`.
    - `lang/id.json`: contains 471 localization strings, currently has 0 keys for Analytics.

### 1.4 Database Schema & Model Characteristics
- **Models & Relations** (`app/Modules/Workload/Infrastructure/Persistence/Eloquent/Models/`):
  - `WorkspaceModel`: UUID primary key, `members()` belongsToMany `UserModel` via `workspace_members` with pivot `['role', 'daily_capacity_hours']`.
  - `ProjectModel`: UUID primary key, `workspace_id`, `lead_id`, `status` (`'ACTIVE' | 'ARCHIVED'`), `issues()` hasMany `IssueModel`.
  - `IssueModel`: UUID primary key, `project_id`, `sprint_id`, `status_id`, `number`, `title`, `story_points`, `original_estimate_seconds`, `remaining_estimate_seconds`, `assignee_id`, `reporter_id`, `created_at`, `updated_at`, `deleted_at`.
  - `WorklogModel`: UUID primary key, `issue_id`, `author_id`, `time_spent_seconds` (integer in seconds), `started_at` (datetime).
  - `StatusModel`: UUID primary key, `category` (`'TODO' | 'IN_PROGRESS' | 'DONE'`), `name`, `slug`, `color`.
  - `SprintModel`: UUID primary key, `project_id`, `state` (`'PENDING' | 'ACTIVE' | 'CLOSED'`), `committed_points`, `completed_points`.
- **Authorization & Policy**:
  - `WorkspacePolicy.php`: `view()` permits superadmin, user whose `org_unit_id === workspace->owner_group_id`, or user in `workspace_members`.
  - Project authorization in existing controllers checks either `workspace->members` or `$user->hasPermission('projects:view')`.

---

## 2. Logic Chain

1. **Test Suite Independence (Observation 1.1)**:
   - Tests execute against SQLite in-memory (`:memory:`).
   - Therefore, any database query written for backend aggregations must either:
     a) Use database-agnostic SQL standards compatible with SQLite, or
     b) Use driver conditional branching (e.g. `DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%W', ...)" : "DATE_FORMAT(..., '%Y-%u')"`).
   - If queries use MySQL-only functions (`UNIX_TIMESTAMP`, `DATE_FORMAT`, `WEEK`), automated Pest tests will fail with SQLite syntax exceptions.

2. **Test Setup Strategy without Model Factories (Observation 1.1)**:
   - Since only `UserFactory` exists, creating test data for `workspaces`, `projects`, `issues`, and `worklogs` cannot rely on non-existent `WorkspaceFactory` or `IssueFactory`.
   - Creating a test helper trait or private helper methods inside `AnalyticsTest.php` that use `DB::table(...)->insert(...)` or `Model::create(...)` ensures tests remain clean, reproducible, and fast.
   - Using `$this->seed(WorkloadLookupSeeder::class);` in `beforeEach` instantly primes `statuses`, `issue_types`, and `priorities` without repetitive boilerplate.

3. **PHPStan Level 7 Compliance (Observation 1.2)**:
   - Running `./vendor/bin/phpstan analyse --debug` requires setting memory limit to 2G (`--memory-limit=2G` or configuring `parameters: memoryLimit: 2G` in `phpstan.neon`) to prevent memory exhaustion fatal crashes (code 255).
   - All new backend classes (Controllers, UseCases, Queries, DTOs, Resources) must declare `declare(strict_types=1);`, specify strict parameter and return typehints, annotate array structures (e.g. `array<string, mixed>`), and use typed Eloquent relations.

4. **N+1 Prevention and Query Cap Verification (Observation 1.4 & ORIGINAL_REQUEST R3)**:
   - Acceptance criteria require 0 N+1 queries and query execution < 200ms.
   - Aggregating workspace metrics across multiple projects by querying projects and then looping over each project to count issues is an $O(N)$ antipattern.
   - Macro queries must execute bounded $O(1)$ SQL aggregations:
     - Query 1: Single SQL aggregate for project progress (`GROUP BY project_id`).
     - Query 2: Single SQL aggregate for throughput (`GROUP BY period`).
     - Query 3: Single SQL aggregate for cross-project member workload (`GROUP BY assignee_id`).
     - Query 4: Member details / user lookup (`WHERE id IN (...)`).
   - Total queries remain $\le 5$ regardless of whether the workspace has 1 project or 50 projects.
   - This can be strictly verified in automated Pest tests using `DB::enableQueryLog()`, `DB::getQueryLog()`, and invariance testing ($Q_1 == Q_2$).

5. **Frontend Quality Gates & Theme Harmony (Observation 1.3 & ORIGINAL_REQUEST R4)**:
   - TypeScript is configured with strict type checking and passes cleanly.
   - Any new chart component or page must define complete TypeScript interfaces for analytics payloads.
   - Chart visualizations must consume `useAppearance()` to dynamically select dark/light color palettes and `useIconSize()` to adjust card heights, paddings, and font sizes.
   - All user-facing strings must use `useTranslate()` with corresponding entries added to `lang/id.json`.

---

## 3. Caveats

1. **Database Dialect Nuances**:
   - SQLite handles dates as strings (ISO 8601). Aggregating by week or month using `strftime` works on SQLite, but MySQL uses `DATE_FORMAT`. The query layer must abstract or test this difference.
2. **Cycle Time & Lead Time Definition**:
   - Lead time is measured from ticket creation (`created_at`) to completion (`updated_at` where status is in category `DONE`, or history entry).
   - Cycle time requires tracking when an issue entered `IN_PROGRESS`. If issues transition directly from `TODO` to `DONE` without entering `IN_PROGRESS`, cycle time should default to lead time or be logged appropriately.
3. **Null Assignees and Estimates**:
   - Issues may have `assignee_id = null`, `original_estimate_seconds = null`, or 0 worklogs. Aggregation queries must use `COALESCE` / `NULLIF` to prevent SQL errors or dropped records in `INNER JOIN` vs `LEFT JOIN`.
4. **Environment Isolation**:
   - In sandboxed command execution, parallel PHPStan sockets fail with `EPERM`. The quality gate must always invoke `--debug --memory-limit=2G` for single-process evaluation.

---

## 4. Conclusion & Actionable Test Plan

### 4.1 Target Test Suite Specification: `tests/Feature/Modules/Workload/AnalyticsTest.php`

The automated test file must cover the following test scenarios:

#### Part 1: Workspace Macro Analytics (`GET /api/workspaces/{id}/analytics`)
1. **Security & RBAC**:
   - `test('unauthenticated user cannot access workspace analytics (401)')`
   - `test('non-member user cannot access workspace analytics (403 IDOR prevention)')`
   - `test('workspace member with viewer/member/admin role can access analytics (200 OK)')`
   - `test('superadmin can access workspace analytics across workspaces (200 OK)')`
   - `test('non-existent workspace returns 404')`
2. **Empty States**:
   - `test('returns zeroed metrics for workspace with zero projects without division by zero')`: asserts `total_projects: 0`, `total_issues: 0`, `completed_issues: 0`, `completion_rate: 0.0`, `throughput: []`, `member_workload: []`.
   - `test('returns zeroed metrics for workspace with projects but zero issues')`
3. **Calculation Logic**:
   - `test('calculates overall project progress and throughput accurately across multiple projects')`: sets up Project A (5 tasks, 3 done = 60%) and Project B (4 tasks, 1 done = 25%), asserts workspace total (9 tasks, 4 done = 44.4%) and throughput counts.
   - `test('aggregates cross-project member workload distribution (task count, estimated hours, logged hours)')`: validates User A across multiple projects with original estimates and worklog hours, plus handling of unassigned tasks.
4. **Date Range Filtering**:
   - `test('filters workspace analytics by preset range (last_7_days, last_30_days, this_quarter)')`: asserts issues outside range are excluded from throughput.
   - `test('filters workspace analytics by custom date range (?from=...&to=...)')`
5. **N+1 Query & Performance Gates**:
   - `test('workspace macro analytics executes with zero N+1 queries (constant query count <= 5)')`
   - `test('workspace macro analytics query count is invariant when scaling from 2 to 10 projects')`
   - `test('workspace macro analytics executes in < 200ms')`

#### Part 2: Project Deep Dive Analytics (`GET /api/projects/{id}/analytics`)
1. **Security & RBAC**:
   - `test('unauthenticated user cannot access project analytics (401)')`
   - `test('unauthorized user cannot access project analytics (403)')`
   - `test('project member can access project analytics (200 OK)')`
   - `test('non-existent project returns 404')`
2. **Empty States**:
   - `test('returns clean empty analytics for project with zero issues without error')`
   - `test('handles kanban project with zero sprints gracefully (falls back to continuous throughput)')`
3. **Calculation Logic**:
   - `test('calculates lead time and cycle time distributions accurately')`: verifies lead time (created -> done) and cycle time (in progress -> done) metrics.
   - `test('computes cumulative flow trend (CFD) across TODO, IN_PROGRESS, DONE statuses')`
   - `test('computes issue type and priority distributions with counts and percentages')`
   - `test('computes sprint burndown and velocity when active and completed sprints exist')`
4. **N+1 Query & Performance Gates**:
   - `test('project deep dive analytics executes with zero N+1 queries (query count <= 6)')`
   - `test('project deep dive analytics executes in < 200ms')`

---

## 5. Verification Method

To independently verify the test suite, quality gates, and performance:

1. **Pest Test Suite**:
   ```bash
   ./vendor/bin/pest tests/Feature/Modules/Workload/AnalyticsTest.php
   ```
   *Expected*: All tests pass, 0 failures, total execution duration < 500ms.

2. **PHPStan Static Analysis Level 7**:
   ```bash
   ./vendor/bin/phpstan analyse --debug --memory-limit=2G
   ```
   *Expected*: Code 0, `{"tool":"phpstan","result":"passed","errors":0}`.

3. **Frontend Type Check**:
   ```bash
   npm run types:check
   ```
   *Expected*: Code 0, `tsc --noEmit` completes with 0 errors.

4. **Frontend Production Build**:
   ```bash
   npm run build
   ```
   *Expected*: Code 0, Vite builds all client assets without warnings or errors.

5. **Code Style Linter**:
   ```bash
   ./vendor/bin/pint --test
   ```
   *Expected*: Code 0, all files comply with project code styling.

6. **Invalidation Conditions**:
   - Any query executing inside a PHP loop (`foreach ($projects as $project)` running queries) violates N+1 criteria.
   - Any division by zero on empty workspaces resulting in PHP warning or 500 status violates acceptance criteria.
   - Any raw SQL function unsupported by SQLite (like `DATE_FORMAT` without dialect abstraction) breaks the test suite.
