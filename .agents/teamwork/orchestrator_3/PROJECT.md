# Project: WLMS Reporting & Analytics Dashboard

## Architecture
- **Framework & Backend**: Laravel 11 / PHP 8.5 with Clean Architecture & Pragmatic CQRS under `App\Modules\Workload`.
  - Application Queries (`DB::table(...)` direct query builder bypasses entity hydration for ultra-fast aggregations).
  - Presentation Layer: Single-action controllers, FormRequests with strict RBAC/IDOR checks.
  - Zero database migrations (all required schema tables/columns already exist).
- **Frontend**: Inertia.js React 19, Tailwind CSS v4, Lucide React, Radix UI.
  - Responsive pure React SVG visualization components.
  - Theme harmony with CSS variables `--chart-1` through `--chart-5` in OKLCH.
  - 3-tier density scaling via `useIconSize` (S / M / L).
  - Localization via `useTranslate` and `lang/id.json`.
- **Testing & QA**:
  - Pest Feature tests in `tests/Feature/Modules/Workload/AnalyticsTest.php`.
  - PHPStan static analysis at level 7 (`--debug --memory-limit=2G`).
  - TypeScript strict checks (`npm run types:check`).
  - Production build verification (`npm run build`).

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Overall Project Progress & Health | Aggregate progress percentage and health status (on track, at risk, completed) across workspace projects | M1, M3, E2E | Survey 1, Survey 2 |
| 2 | Portfolio Throughput | Completed tasks aggregated per week/month across all workspace projects | M1, M2, M3, E2E | Survey 1, Survey 2 |
| 3 | Cross-Project Member Workload | Member workload distribution (task count, estimated hours, logged hours) across projects | M1, M2, M3, E2E | Survey 1, Survey 2 |
| 4 | Date Range Filtering | Preset ranges (7d, 30d, quarter) and custom range filtering | M1, M3, E2E | Survey 1, Survey 2 |
| 5 | Cycle Time & Lead Time Metrics | Lead time (creation to done) and cycle time (start to done) distributions and percentiles (p50, p85) | M1, M2, M3, E2E | Survey 1, Survey 2 |
| 6 | Cumulative Flow Diagram (CFD) | State trends over time (TODO, IN_PROGRESS, DONE) showing bottleneck build-up | M1, M2, M3, E2E | Survey 1, Survey 2 |
| 7 | Issue Type & Priority Breakdown | Distribution breakdowns with counts, colors, and percentages | M1, M2, M3, E2E | Survey 1, Survey 2 |
| 8 | Sprint Burndown & Velocity | Ideal vs actual remaining burndown + historical velocity for sprints, or continuous flow throughput for kanban | M1, M2, M3, E2E | Survey 1, Survey 2 |
| 9 | High-Performance SQL Aggregations | Direct Query Builder queries executing in bounded $O(1)$ query count (<=5 queries), zero N+1, <200ms | M1, E2E | Survey 1, Survey 3 |
| 10 | Strict RBAC & IDOR Enforcement | Strict authorization verifying workspace/project membership, returning HTTP 403 for unauthorized access | M1, E2E | Survey 1, Survey 3 |
| 11 | Responsive SVG Chart Visualizations | Pure React SVG components (Bar, Stacked Bar, Area CFD, Burndown, Donut, Histogram) with tooltips and empty states | M2, M3 | Survey 2 |
| 12 | Appearance & Theme Harmony | Seamless Dark/Light mode transitions using `--chart-1` ... `--chart-5` CSS variables and semantic tokens | M2, M3 | Survey 2 |
| 13 | Global Density Scaling | S / M / L scaling compliance via `useIconSize` adapting chart heights, paddings, typography, and controls | M2, M3 | Survey 2 |
| 14 | Complete Localization | Full translation support via `useTranslate()` and Indonesian dictionary in `lang/id.json` | M2, M3 | Survey 2 |
| 15 | Quality Gates & Acceptance Verification | Passing Pest test suite, zero PHPStan level 7 errors, zero TypeScript errors, successful build | M4, E2E | Survey 3 |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| E2E | E2E Testing Track | Requirement-driven test suite in `tests/Feature/Modules/Workload/AnalyticsTest.php` with Tiers 1-4 test cases | None | IN_PROGRESS |
| M1 | Backend Aggregations & RBAC Core | DTOs, CQRS Queries, FormRequests with 403 RBAC, Controllers, API routes for workspace & project analytics | None | IN_PROGRESS |
| M2 | Visualization System & Appearance Harmony | Pure React SVG charts, Dark/Light tokens, S/M/L density scaling, `lang/id.json` translations | None | DONE |
| M3 | Analytics Hub Pages & Navigation | Date range filter, `/workspaces/{id}/analytics` page, `/projects/{id}/analytics` / tab, React Query hooks | M1, M2 | PLANNED |
| M4 | Final Milestone: 100% E2E Pass & Hardening | Phase 1: 100% pass on Pest E2E tests, PHPStan level 7, TypeScript check, build. Phase 2: Tier 5 adversarial hardening | E2E, M1, M2, M3 | PLANNED |

## Interface Contracts
### Backend API Endpoints:
1. `GET /api/workspaces/{id}/analytics`
   - Parameters: `date_range` (`7d` | `30d` | `quarter` | `custom`), `from` (`YYYY-MM-DD`), `to` (`YYYY-MM-DD`).
   - Headers: `Authorization: Bearer <token>`, `Accept: application/json`.
   - Response:
     ```json
     {
       "summary": { "total_projects": 3, "active_projects": 2, "total_issues": 45, "completed_issues": 25, "completion_rate": 55.6 },
       "projects": [{ "id": "...", "key": "PROJ", "name": "...", "status": "ACTIVE", "total_issues": 20, "completed_issues": 12, "progress_percent": 60.0, "health_status": "on_track" }],
       "throughput": [{ "period": "2026-W39", "completed_count": 8 }],
       "member_workload": [{ "user_id": 1, "name": "...", "email": "...", "task_count": 5, "completed_task_count": 3, "estimated_hours": 40.0, "logged_hours": 28.5, "capacity_hours": 40, "utilization_rate": 71.3 }],
       "filters": { "date_range": "30d", "start_date": "2026-09-08", "end_date": "2026-10-08" }
     }
     ```
   - Errors: 401 Unauthorized, 403 Forbidden, 404 Not Found.
2. `GET /api/projects/{id}/analytics`
   - Parameters: `date_range`, `from`, `to`, `sprint_id`.
   - Response:
     ```json
     {
       "project": { "id": "...", "key": "PROJ", "name": "..." },
       "lead_time": { "average_days": 4.2, "median_days": 3.5, "p85_days": 7.0, "distribution": [{ "bucket": "0-2d", "count": 10 }, { "bucket": "3-5d", "count": 12 }, { "bucket": "6-10d", "count": 5 }, { "bucket": ">10d", "count": 2 }] },
       "cycle_time": { "average_days": 2.8, "median_days": 2.0, "p85_days": 5.0, "distribution": [...] },
       "cumulative_flow": [{ "date": "2026-10-01", "todo": 10, "in_progress": 5, "done": 12 }],
       "issue_types": [{ "id": "...", "name": "Task", "slug": "task", "count": 15, "color": "#3B82F6" }],
       "priorities": [{ "id": "...", "name": "High", "slug": "high", "level": 3, "count": 8, "color": "#EF4444" }],
       "sprint_metrics": { "has_sprints": true, "burndown": [{ "day": "Day 1", "ideal_points": 30, "actual_points": 30 }], "velocity": [{ "sprint_name": "Sprint 1", "completed_points": 28 }] }
     }
     ```

## Code Layout
- Backend:
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
  - `app/Modules/Workload/Presentation/Http/routes.php` (update routes)
- Frontend:
  - `resources/js/components/charts/ResponsiveContainer.tsx`
  - `resources/js/components/charts/BarChart.tsx`
  - `resources/js/components/charts/StackedBarChart.tsx`
  - `resources/js/components/charts/CumulativeFlowChart.tsx`
  - `resources/js/components/charts/BurndownVelocityChart.tsx`
  - `resources/js/components/charts/DonutDistributionChart.tsx`
  - `resources/js/components/charts/LeadTimeHistogram.tsx`
  - `resources/js/components/charts/ChartTooltip.tsx`
  - `resources/js/components/charts/ChartLegend.tsx`
  - `resources/js/components/charts/ChartEmptyState.tsx`
  - `resources/js/components/charts/ChartCardSkeleton.tsx`
  - `resources/js/components/DateRangeFilter.tsx`
  - `resources/js/pages/Workspaces/Analytics.tsx`
  - `resources/js/pages/Workspaces/Issues.tsx` (add tab)
  - `resources/js/modules/Workload/Projects/components/ProjectAnalyticsDashboard.tsx`
  - `resources/js/modules/Workload/Issues/components/IssuesManager.tsx` (add tab)
  - `resources/js/hooks/useWorkspaceAnalytics.ts`
  - `resources/js/hooks/useProjectAnalytics.ts`
  - `lang/id.json` (add translations)
- Tests:
  - `tests/Feature/Modules/Workload/AnalyticsTest.php`
