# Dispatch: Worker M1 (Backend Aggregations & RBAC Core)

## Objective
Implement Milestone M1: High-Performance Backend Aggregator & RBAC Enforcement adhering strictly to Clean Architecture, CQRS, and zero N+1 queries.

## Exclusive Write Ownership
You own ONLY the following files:
- `app/Modules/Workload/Application/DTOs/WorkspaceAnalyticsInput.php`
- `app/Modules/Workload/Application/DTOs/WorkspaceAnalyticsOutput.php`
- `app/Modules/Workload/Application/DTOs/ProjectAnalyticsInput.php`
- `app/Modules/Workload/Application/DTOs/ProjectAnalyticsOutput.php`
- `app/Modules/Workload/Application/Queries/GetWorkspaceAnalyticsQuery.php`
- `app/Modules/Workload/Application/Queries/GetProjectAnalyticsQuery.php`
- `app/Modules/Workload/Presentation/Http/Requests/GetWorkspaceAnalyticsHttpRequest.php`
- `app/Modules/Workload/Presentation/Http/Requests/GetProjectAnalyticsHttpRequest.php`
- `app/Modules/Workload/Presentation/Http/Controllers/GetWorkspaceAnalyticsController.php`
- `app/Modules/Workload/Presentation/Http/Controllers/GetProjectAnalyticsController.php`
- `app/Modules/Workload/Presentation/Http/routes.php` (update with new routes)
- `.agents/teamwork/worker_m1_backend/handoff.md`
- `.agents/teamwork/worker_m1_backend/progress.md`
- `.agents/teamwork/worker_m1_backend/BRIEFING.md`

Do NOT touch frontend files in `resources/` or test files in `tests/`.

## Inputs to Read
- Original User Request: `/Users/robileksono/Sites/wlms/.agents/teamwork/ORIGINAL_REQUEST.md`
- Master Project Architecture & Interface Contracts: `/Users/robileksono/Sites/wlms/.agents/teamwork/orchestrator_3/PROJECT.md`
- Backend Exploration Report: `/Users/robileksono/Sites/wlms/.agents/teamwork/explorer_survey_1/handoff.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Technical Specifications
1. **Clean Architecture & CQRS**:
   - DTOs in `Application/DTOs/` with strict types (`declare(strict_types=1);`).
   - Query Classes in `Application/Queries/` executing direct `DB::table(...)` SQL queries (bypass entity hydration).
   - Single-Action Controllers in `Presentation/Http/Controllers/` returning `JsonResponse`.
2. **High Performance & Zero N+1**:
   - Bounded $O(1)$ query count ($\le 5$ queries for workspace, $\le 5$ queries for project).
   - SQL queries must be SQLite AND MySQL compatible. Do NOT use `DATE_FORMAT()`; use portable grouping or parse timestamps in PHP using `CarbonImmutable`.
   - Protect against division-by-zero on empty states.
3. **RBAC & Authorization**:
   - `GetWorkspaceAnalyticsHttpRequest`: FormRequest checking `WorkspacePolicy::view` (superadmin, owner group, or workspace membership in `workspace_members`). Return 403 when unauthorized.
   - `GetProjectAnalyticsHttpRequest`: Check project lead, issue assignment, context roles, or workspace membership. Return 403 when unauthorized.
4. **Code Quality**:
   - Declare strict types (`declare(strict_types=1);`) in every PHP file.
   - PHPStan level 7 compliant with explicit typehints and phpdoc annotations. Run `./vendor/bin/phpstan analyse --debug --memory-limit=2G` to verify.

## Completion Criteria
1. Implement all files listed under Exclusive Write Ownership.
2. Run `./vendor/bin/phpstan analyse --debug --memory-limit=2G` and ensure 0 errors.
3. Run existing feature tests (`./vendor/bin/pest tests/Feature/Modules/Workload/`) to verify no regressions.
4. Document all implemented classes, methods, queries, and verification results in `/Users/robileksono/Sites/wlms/.agents/teamwork/worker_m1_backend/handoff.md`.

