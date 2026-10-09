# E2E Test Infra: WLMS Reporting & Analytics Dashboard

## Test Philosophy
- Opaque-box, requirement-driven derived strictly from `ORIGINAL_REQUEST.md`.
- Methodology: Category-Partition + Boundary Value Analysis (BVA) + Pairwise Combinatorial + Real-World Workload Testing.
- Target test file: `tests/Feature/Modules/Workload/AnalyticsTest.php`.

## Feature Inventory
| # | Feature | Source (requirement) | Tier 1 | Tier 2 | Tier 3 |
|---|---------|---------------------|:------:|:------:|:------:|
| 1 | Workspace Progress & Health | ORIGINAL_REQUEST § R1 | 5 | 5 | ✓ |
| 2 | Portfolio Throughput | ORIGINAL_REQUEST § R1 | 5 | 5 | ✓ |
| 3 | Cross-Project Member Workload | ORIGINAL_REQUEST § R1 | 5 | 5 | ✓ |
| 4 | Date Range Filtering | ORIGINAL_REQUEST § R1 | 5 | 5 | ✓ |
| 5 | Project Cycle & Lead Time | ORIGINAL_REQUEST § R2 | 5 | 5 | ✓ |
| 6 | Cumulative Flow Diagram (CFD) | ORIGINAL_REQUEST § R2 | 5 | 5 | ✓ |
| 7 | Issue Type & Priority Breakdown | ORIGINAL_REQUEST § R2 | 5 | 5 | ✓ |
| 8 | Sprint Burndown & Velocity / Kanban | ORIGINAL_REQUEST § R2 | 5 | 5 | ✓ |
| 9 | Zero N+1 & <200ms Performance | ORIGINAL_REQUEST § R3 | 5 | 5 | ✓ |
| 10 | Strict RBAC / IDOR Protection | ORIGINAL_REQUEST § R3 | 5 | 5 | ✓ |

## Test Architecture
- Test runner: Pest PHP (`./vendor/bin/pest tests/Feature/Modules/Workload/AnalyticsTest.php`).
- Database: In-memory SQLite (`:memory:`) via `RefreshDatabase`.
- Fixture creation: Seed `WorkloadLookupSeeder` for statuses/types/priorities, and clean Eloquent / DB inserts for workspaces, projects, issues, worklogs, and sprints.
- Pass/Fail semantics: All tests must assert exact HTTP status codes (200, 401, 403, 404), schema structure, mathematical precision of aggregations, zero division-by-zero errors, and query count bounds ($\le 5$ queries).

## Test Case Tier Breakdown
- **Tier 1 (Feature Coverage, Happy Path)**:
  - 1.1: Workspace analytics returns overall progress, active project count, and health status accurately.
  - 1.2: Workspace analytics returns throughput grouped by period.
  - 1.3: Cross-project member workload aggregates tasks, estimated hours, and logged hours.
  - 1.4: Date range filtering (last_7_days, last_30_days, this_quarter).
  - 1.5: Project analytics returns average and percentile lead time and cycle time.
  - 1.6: Cumulative flow status trends over time.
  - 1.7: Issue type and priority breakdowns.
  - 1.8: Sprint burndown and velocity metrics when sprints exist.
  - 1.9: Continuous flow throughput for kanban projects without sprints.
  - 1.10: Strict RBAC: authorized workspace member can access analytics.
- **Tier 2 (Boundary & Corner Cases)**:
  - 2.1: Empty workspace with 0 projects returns zeroed metrics (no division by zero).
  - 2.2: Workspace with projects but 0 issues returns zeroed metrics.
  - 2.3: Project with 0 issues returns zeroed lead/cycle time and empty CFD.
  - 2.4: Issues with null assignee, null estimate, or 0 worklogs handle gracefully.
  - 2.5: Issues transitioning directly from TODO to DONE without IN_PROGRESS.
  - 2.6: Custom date range boundaries (same start and end date, future dates).
  - 2.7: Non-existent workspace or project ID returns 404.
  - 2.8: Unauthenticated request returns 401.
  - 2.9: Non-member user without permission returns 403 Forbidden.
  - 2.10: Cross-tenant project access blocked (user in Workspace A cannot access Workspace B project).
- **Tier 3 (Cross-Feature Combinations & Invariance)**:
  - 3.1: Combining date range filter with multiple projects and multiple assignees.
  - 3.2: Query count invariance: scaling from 2 to 10 projects does NOT increase query count (0 N+1).
  - 3.3: Query count invariance: scaling from 5 to 50 issues does NOT increase query count.
  - 3.4: Sprint metrics combined with custom date range.
  - 3.5: Superadmin access combined with archived projects.
- **Tier 4 (Real-World Application Scenarios)**:
  - 4.1: Multi-project software team sprint cycle: 3 projects, 5 team members, mixed story points, worklogs, and status transitions verifying end-to-end KPI alignment.
  - 4.2: Mixed scrum/kanban portfolio with varying member capacities and utilization rates.
  - 4.3: High-load simulation asserting query execution time < 200ms on seeded dataset.

## Coverage Thresholds
- Tier 1: $\ge 10$ test cases
- Tier 2: $\ge 10$ test cases
- Tier 3: $\ge 5$ test cases
- Tier 4: $\ge 3$ scenarios
- Total: 28+ comprehensive test assertions in `tests/Feature/Modules/Workload/AnalyticsTest.php`.

