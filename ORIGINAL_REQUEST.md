# Original User Request

## 2026-10-08T05:05:06Z

Build a comprehensive Reporting & Analytics Dashboard module for the WLMS platform, providing both Workspace Macro Analytics (portfolio throughput, cross-project workload, completion rates) and Project-Level Deep Dive Metrics (lead time, status distribution, CFD, and team velocity).

Working directory: /Users/robileksono/Sites/wlms
Integrity mode: benchmark

## Requirements

### R1. Workspace Macro Analytics Hub
Provide an analytical overview at the Workspace level (`/workspaces/{id}/analytics` or workspace tab) aggregating metrics across all projects within the active workspace:
- Overall project progress, health status, and throughput (tasks completed per week/month).
- Cross-project member workload distribution (task count & estimated/logged hours per assignee).
- Date range filtering (Last 7 Days, Last 30 Days, This Quarter, Custom Range).

### R2. Project-Level Deep Dive Analytics
Provide granular performance metrics for individual projects (`/projects/{id}/analytics` or tab):
- Cycle time & lead time distributions (from ticket creation to completion).
- Cumulative flow / status trend over time showing bottlenecks.
- Issue type distribution and priority breakdown.
- Sprint burndown / velocity metrics when active sprints exist, or continuous flow throughput for kanban projects.

### R3. High-Performance Backend Aggregator & RBAC Enforcement
Implement dedicated queries/use-cases adhering to Clean Architecture:
- Aggregated endpoints (`GET /api/workspaces/{id}/analytics` and `GET /api/projects/{id}/analytics`) optimized with SQL aggregations (`COUNT`, `SUM`, `GROUP BY`) with zero N+1 query overhead.
- Strict authorization checks verifying the requesting user has membership or appropriate permissions for the workspace/project.

### R4. Accessible, Responsive UI Visualizations
- Interactive charts built with responsive UI components (supporting clean hover tooltips, legends, and empty states).
- Seamless integration with the global appearance system (Dark/Light mode theme harmony, and global S / M / L display density compliance via `useIconSize`).
- Complete localization using `useTranslate()` / `lang/id.json`.

## Acceptance Criteria

### Backend & API Verification
- [ ] Automated Pest tests in `tests/Feature/Modules/Workload/AnalyticsTest.php` cover calculation logic, empty state handling, date filtering, and unauthorized access (403).
- [ ] No N+1 queries; analytics query performance executes in < 200ms on seeded test databases.
- [ ] Zero PHPStan errors at level 7 (`./vendor/bin/phpstan analyse --debug`).

### Frontend & UI Verification
- [ ] Responsive charts correctly resize across viewport dimensions without horizontal breaking or layout shifts.
- [ ] Theme toggling (Dark Mode / Light Mode) updates chart color palettes and contrast seamlessly.
- [ ] Density scaling (S / M / L) adapts chart card heights, paddings, and font sizes.
- [ ] TypeScript check (`npm run types:check`) passes with 0 errors.
- [ ] Frontend production build (`npm run build`) completes successfully.

