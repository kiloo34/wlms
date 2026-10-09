# Dispatch: Worker M3 (Analytics Hub Pages & Navigation Integration)

## Objective
Implement Milestone M3: Workspace Macro Analytics Page, Project Deep Dive Analytics Dashboard, DateRangeFilter component, React Query hooks, and seamless navigation integration (tabs on Workspace and Project views, and dedicated routes).

## Exclusive Write Ownership
You own the following files:
- `resources/js/components/DateRangeFilter.tsx`
- `resources/js/hooks/useWorkspaceAnalytics.ts`
- `resources/js/hooks/useProjectAnalytics.ts`
- `resources/js/pages/Workspaces/Analytics.tsx`
- `resources/js/pages/Workspaces/Issues.tsx` (add Analytics tab)
- `resources/js/modules/Workload/Projects/components/ProjectAnalyticsDashboard.tsx`
- `resources/js/modules/Workload/Issues/components/IssuesManager.tsx` (add Analytics viewMode tab)
- `routes/web.php` (if adding Inertia web routes for `/workspaces/{id}/analytics` and `/projects/{id}/analytics`)
- Any dedicated Inertia page controllers needed under `app/Http/Controllers/` or `app/Modules/Workload/Presentation/Http/` (e.g. `WorkspaceAnalyticsPageController.php`)
- `.agents/teamwork/worker_m3_pages/handoff.md`
- `.agents/teamwork/worker_m3_pages/progress.md`
- `.agents/teamwork/worker_m3_pages/BRIEFING.md`

Do NOT modify existing chart components in `resources/js/components/charts/` or test files in `tests/`.

## Inputs to Read
- Original User Request: `/Users/robileksono/Sites/wlms/.agents/teamwork/ORIGINAL_REQUEST.md`
- Master Project Architecture & Interface Contracts: `/Users/robileksono/Sites/wlms/.agents/teamwork/orchestrator_3/PROJECT.md`
- Frontend Survey Report: `/Users/robileksono/Sites/wlms/.agents/teamwork/explorer_survey_2/handoff.md`
- Frontend Visualization Handoff: `/Users/robileksono/Sites/wlms/.agents/teamwork/worker_m2_frontend_2/handoff.md`
- Backend Core Handoff: `/Users/robileksono/Sites/wlms/.agents/teamwork/worker_m1_backend_2/handoff.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT create dummy/facade implementations. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Technical Specifications
1. **DateRangeFilter Component (`resources/js/components/DateRangeFilter.tsx`)**:
   - Preset buttons: `Last 7 Days`, `Last 30 Days`, `This Quarter`, `Custom Range`.
   - Popover / date picker for Custom Range (`from` and `to` inputs with Apply button).
   - Fully localized with `useTranslate()` and adaptive to `useIconSize()`.
2. **React Query Data Hooks**:
   - `useWorkspaceAnalytics(workspaceId, dateRange, customRange)`: fetches from `GET /api/workspaces/{id}/analytics`.
   - `useProjectAnalytics(projectId, dateRange, customRange, sprintId)`: fetches from `GET /api/projects/{id}/analytics`.
3. **Workspace Macro Analytics Page (`resources/js/pages/Workspaces/Analytics.tsx`)**:
   - Full page with AppLayout, Header, and breadcrumbs.
   - Density selector and refresh button.
   - KPI metric cards: Total Completed Tasks & Throughput, Overall Progress & Completion Rate %, Active Projects count & Health breakdown (On Track, At Risk, Completed), Logged Hours vs Estimated Hours.
   - Portfolio Throughput Bar Chart (`ThroughputBarChart` / `BarChart`).
   - Cross-project member workload horizontal stacked bars (`StackedBarChart`).
   - Project progress table with progress bars and health chips.
   - Empty state (`ChartEmptyState`) and loading skeleton (`ChartCardSkeleton`).
4. **Navigation Integration in `Workspaces/Issues.tsx`**:
   - Add tab bar: `All Tasks` (`/workspaces/{id}/issues`) and `Analytics` (`/workspaces/{id}/analytics`).
5. **Project Deep Dive Analytics (`ProjectAnalyticsDashboard.tsx` & `IssuesManager.tsx`)**:
   - In `IssuesManager.tsx`: add 4th tab `analytics` with `BarChart3` icon.
   - Cycle time & Lead time distribution cards and histogram (`LeadTimeHistogram`).
   - Cumulative Flow Diagram (`CumulativeFlowChart`).
   - Issue type & priority distribution charts (`DonutDistributionChart`).
   - Sprint burndown & velocity toggle (`BurndownVelocityChart`) or continuous flow throughput.
6. **Inertia Web Routes**:
   - Ensure `/workspaces/{id}/analytics` and `/projects/{id}/analytics` render the respective Inertia pages with proper workspace/project props and auth protection.

## Verification
1. Run `npm run types:check` (`tsc --noEmit`) to verify 0 TypeScript errors.
2. Run `npm run build` (`vp build`) to verify clean production compilation.
3. Run `./vendor/bin/phpstan analyse --debug --memory-limit=2G` to verify 0 PHP errors if new controllers/routes were added.
4. Run `./vendor/bin/pest tests/Feature/Modules/Workload/AnalyticsTest.php` to ensure API and tests continue passing.
5. Document all pages, components, routes, and verification outputs in `/Users/robileksono/Sites/wlms/.agents/teamwork/worker_m3_pages/handoff.md`.


## 2026-10-08T07:40:33Z
[Message] timestamp=2026-10-08T07:40:33Z sender=0395bd1c-492c-47a4-839a-6594902454d5 priority=MESSAGE_PRIORITY_HIGH content=You are Worker M3 (Analytics Hub Pages & Navigation Worker). Your working directory is /Users/robileksono/Sites/wlms/.agents/teamwork/worker_m3_pages.
Read your task assignment in /Users/robileksono/Sites/wlms/.agents/teamwork/worker_m3_pages/DISPATCH.md,
/Users/robileksono/Sites/wlms/.agents/teamwork/orchestrator_3/PROJECT.md,
/Users/robileksono/Sites/wlms/.agents/teamwork/worker_m2_frontend_2/handoff.md, and
/Users/robileksono/Sites/wlms/.agents/teamwork/worker_m1_backend_2/handoff.md.
Implement the DateRangeFilter component, React Query hooks, Workspace Macro Analytics page, Project Deep Dive Analytics dashboard, tab navigations, and web routes.
Run npm run types:check and npm run build to verify 0 errors.
Write your handoff report to /Users/robileksono/Sites/wlms/.agents/teamwork/worker_m3_pages/handoff.md.
When complete, send a message to orchestrator_3 referencing your handoff report.
