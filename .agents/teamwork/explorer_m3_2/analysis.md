# Analysis: Project-Level Deep Dive Analytics Integration

## 1. Executive Summary

This report provides the architectural analysis and integration blueprint for **Project-Level Deep Dive Analytics** in the WLMS platform (Milestone M3).
The backend endpoints (`GET /api/projects/{id}/analytics`) and visualization system (`BurndownVelocityChart`, `CumulativeFlowChart`, `LeadTimeHistogram`, `DonutDistributionChart`) have been fully verified (28/28 Pest tests passing, 0 PHPStan level 7 errors, 0 TypeScript errors).
The standalone dashboard component `ProjectAnalyticsDashboard.tsx` and hook `useProjectAnalytics.ts` already exist in the codebase.
The critical missing link in the user journey is mounting `ProjectAnalyticsDashboard` as a native **'analytics'** tab inside `IssuesManager.tsx` (rendered by `resources/js/pages/Projects/Show.tsx`), and ensuring seamless URL tab state synchronization, density compliance, and localization.

---

## 2. Project Views & Modules Inspection

### 2.1 File & Module Hierarchy

```
resources/js/
├── pages/
│   ├── Projects/
│   │   ├── Index.tsx        # Workspace-level projects overview, all tasks table, workspace analytics
│   │   └── Show.tsx         # Individual project detail page, renders Project header & IssuesManager
│   └── Workspaces/
│       └── Issues.tsx       # Cross-project issues view for workspace
├── modules/Workload/
│   ├── Projects/
│   │   ├── components/
│   │   │   ├── ProjectAnalyticsDashboard.tsx  # Granular project-level analytics view
│   │   │   ├── ProjectsManager.tsx            # Project cards/grid manager for workspace
│   │   │   ├── ProjectList.tsx                # Project table view
│   │   │   └── ProjectFormDialog.tsx          # Create/edit project dialog
│   │   ├── hooks/useProjects.ts
│   │   └── index.ts                           # Module export barrel
│   └── Issues/
│       ├── components/
│       │   ├── IssuesManager.tsx              # Core container for project issues, board, backlog
│       │   ├── KanbanBoard.tsx                # Drag-and-drop / column transition board
│       │   ├── IssueList.tsx                  # Issue data table
│       │   ├── BacklogManager.tsx             # Sprints and backlog issue ranking
│       │   ├── BoardFilterBar.tsx             # Assignee, priority, type filters for board
│       │   └── ProjectActivitySheet.tsx       # Slide-over activity log audit trail
│       └── hooks/
│           ├── useIssues.ts                   # Issues CRUD, status transitions, worklogs
│           └── useSprints.ts                  # Sprints query and lifecycle
└── hooks/
    ├── useProjectAnalytics.ts                 # React Query hook for GET /api/projects/{id}/analytics
    └── useWorkspaceAnalytics.ts               # React Query hook for GET /api/workspaces/{id}/analytics
```

### 2.2 Project View Routing & Navigation Flow

1. **Workspace Projects Index (`/projects?workspace_id={id}`)**:
   - Controller: `ProjectIndexPageController` -> Page: `resources/js/pages/Projects/Index.tsx`.
   - Has 3 segmented top tabs: `Overview Projects` (`ProjectsManager`), `All Tasks` (`WorkspaceTasksTable`), and `Analytics` (`WorkspaceAnalyticsView`).
   - Clicking any project row or card invokes `router.visit('/projects/' + project.id)`.

2. **Project Detail / Issue Board (`/projects/{id}`)**:
   - Controller: `ShowProjectPageController` -> Page: `resources/js/pages/Projects/Show.tsx`.
   - Loads project record (`id`, `workspace_id`, `name`, `key`, `priority_id`, `status`) and lookup tables (`issueTypes`, `priorities`, `statuses`, `users`).
   - Renders a top header with project avatar initial, project key, priority badge, and global S / M / L density switcher (`useIconSize`).
   - Mounts `<IssuesManager projectId={project.id} lookups={lookups} />` at line 137.

3. **Current Views within `IssuesManager.tsx`**:
   - **Board** (`viewMode === 'board'`): Renders `KanbanBoard.tsx` with `BoardFilterBar.tsx` for sprint/kanban drag-and-drop and status transitions.
   - **List** (`viewMode === 'list'`): Renders `IssueList.tsx` for tabular inspection and bulk triage.
   - **Backlog** (`viewMode === 'backlog'`): Renders `BacklogManager.tsx` for sprint planning, start/complete sprint flows, and backlog ranking.
   - **Analytics** (`viewMode === 'analytics'`): **MISSING!** Currently `viewMode` is strictly typed as `'list' | 'board' | 'backlog'`.

---

## 3. View & Tab Switching Mechanism

In `resources/js/modules/Workload/Issues/components/IssuesManager.tsx`:

### 3.1 State & URL Synchronization
Currently (lines 56–93):
```typescript
const [viewMode, setViewModeState] = useState<'list' | 'board' | 'backlog'>(() => {
    // 1. Check URL query parameters (?tab=...)
    const search = url.split('?')[1];
    if (search) {
        const params = new URLSearchParams('?' + search);
        const tab = params.get('tab');
        if (tab === 'list' || tab === 'board' || tab === 'backlog') return tab;
    }
    
    // 2. Check localStorage fallback
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem(`project-${projectId}-viewMode`);
        if (saved === 'list' || saved === 'board' || saved === 'backlog') return saved;
    }
    return 'board';
});

const setViewMode = (mode: 'list' | 'board' | 'backlog') => {
    setViewModeState(mode);
    if (typeof window !== 'undefined') {
        localStorage.setItem(`project-${projectId}-viewMode`, mode);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', mode);
        window.history.replaceState({}, '', url.toString());
    }
};

React.useEffect(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        if (!params.get('tab')) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', viewMode);
            window.history.replaceState({}, '', url.toString());
        }
    }
}, [viewMode]);
```

### 3.2 UI Tab Controls
Currently (lines 253–278):
A segmented pill button group styled with `bg-muted p-1 rounded-lg shrink-0`:
```tsx
<div className="flex gap-1.5 bg-muted p-1 rounded-lg shrink-0">
    <Button 
        variant={viewMode === 'board' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('board')}
    >
        {t('Board')}
    </Button>
    <Button 
        variant={viewMode === 'list' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('list')}
    >
        {t('List')}
    </Button>
    <Button 
        variant={viewMode === 'backlog' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('backlog')}
    >
        {t('Backlog')}
    </Button>
</div>
```

---

## 4. Embedding Project-Level Deep Dive Analytics

### 4.1 Component Structure: `ProjectAnalyticsDashboard.tsx`

Located at: `resources/js/modules/Workload/Projects/components/ProjectAnalyticsDashboard.tsx` (390 lines).

#### Props Contract:
```typescript
export interface ProjectAnalyticsDashboardProps {
    projectId: string;
    className?: string;
}
```

#### Internal Architecture:
1. **Hook Invocation**:
   ```typescript
   const { data, isLoading, error } = useProjectAnalytics({
       projectId,
       dateRange,
       from: customFrom,
       to: customTo,
       sprintId: selectedSprintId,
   });
   ```
2. **Header Toolbar**:
   - Localized Title (`Project Deep-Dive Analytics`) and subtitle.
   - Sprint Filter Selector (Radix `Select`): Displays `Active / Latest Sprint` and all completed/active sprints from `sprint_metrics.velocity`.
   - Date Range Filter (`<DateRangeFilter />`): Preset buttons (`7d`, `30d`, `quarter`) and custom date picker popover (`from`, `to`).
3. **Row 1 — KPI Summary Cards (4 Cards)**:
   - **Total Issues**: Total issues count with `Layers` icon.
   - **Completed Issues**: Delivered count + completion rate percentage with `CheckCircle2` icon.
   - **In Progress**: Active work in progress count with `Flame` icon.
   - **To Do Backlog**: Backlog count waiting to be started with `Clock` icon.
4. **Row 2 — Delivery Speed & Cumulative Flow (2 Cards, 6-col each)**:
   - **Lead Time / Cycle Time Histogram** (`LeadTimeHistogram`):
     - Interactive segmented toggle: `Lead Time` (creation to done) vs `Cycle Time` (in progress to done).
     - Bucket distribution bars (`0-2d`, `3-5d`, `6-10d`, `>10d`).
     - KPI metric chips: Average Days, Median Days (p50), 85th Percentile Days (p85).
   - **Cumulative Flow Diagram (CFD)** (`CumulativeFlowChart`):
     - Daily stacked area curves for `done`, `in_progress`, and `todo`.
     - Hover crosshairs and tooltip for identifying bottleneck build-up.
5. **Row 3 — Breakdown & Sprint Burndown / Velocity (2 Cards, 6-col each)**:
   - **Issue Classification Donut** (`DonutDistributionChart`):
     - Interactive toggle: `Issue Types` (Task, Bug, Story) vs `Priorities` (Urgent, High, Medium, Low).
     - Pure SVG arcs with slice hover zoom and center total count.
   - **Sprint Burndown & Velocity** (`BurndownVelocityChart`):
     - If `sprint_metrics.has_sprints`: Interactive toggle between Burndown (dashed ideal story points vs actual remaining points with gradient area fill) and Velocity (completed vs committed points bars with average velocity reference line).
     - If Kanban project (`!sprint_metrics.has_sprints`): Renders a continuous kanban flow informational callout card.
6. **State Fallbacks & Density Harmony**:
   - `isLoading`: Renders 4 KPI skeleton cards and 2 chart skeleton cards (`ChartCardSkeleton`).
   - `error || !data`: Renders a localized error card with `AlertCircle`.
   - Density compliance: Adapts `cardPadding`, `kpiValSize`, `toggleBtnSize` via `useIconSize()`.

### 4.2 Mounting Strategy in `IssuesManager.tsx`

To mount `ProjectAnalyticsDashboard` properly:

1. **Expand `viewMode` type**:
   ```typescript
   type ProjectViewMode = 'list' | 'board' | 'backlog' | 'analytics';
   ```

2. **Update URL parameter validation**:
   ```typescript
   if (tab === 'list' || tab === 'board' || tab === 'backlog' || tab === 'analytics') return tab;
   ```

3. **Add `Analytics` tab button**:
   ```tsx
   <Button 
       variant={viewMode === 'analytics' ? 'default' : 'ghost'} 
       size="sm" 
       className={tabBtnClass}
       onClick={() => setViewMode('analytics')}
   >
       <BarChart2 className="mr-1.5 h-3.5 w-3.5" />
       {t('Analytics')}
   </Button>
   ```

4. **Header and Toolbar Adaptations for Analytics Mode**:
   - When `viewMode === 'analytics'`:
     - The issue search input (`Search issues...`) and `ProjectActivitySheet` trigger are not applicable to macro project statistics. We conditionally hide the search input or replace it with a clean title badge.
     - The basic progress bar card (`Project Progress` at line 282) should be suppressed when `viewMode === 'analytics'` because `ProjectAnalyticsDashboard` already contains comprehensive KPI cards with total issues, completed issues, in progress, todo, and completion rate %.
   - When `viewMode === 'analytics'`, render:
     ```tsx
     <ProjectAnalyticsDashboard projectId={projectId} />
     ```

5. **Barrel Export**:
   Export `ProjectAnalyticsDashboard` in `resources/js/modules/Workload/Projects/index.ts`:
   ```typescript
   export * from './components/ProjectAnalyticsDashboard';
   ```

---

## 5. Hook Implementation Analysis: `useProjectAnalytics.ts`

Located at: `resources/js/hooks/useProjectAnalytics.ts` (124 lines).

### 5.1 Endpoint & Method
- Endpoint: `GET /api/projects/{projectId}/analytics`
- Transport: Axios HTTP client (`axios.get<ProjectAnalyticsResponse>`)
- Library: `@tanstack/react-query` (`useQuery`)

### 5.2 Query Key & Parameters
```typescript
queryKey: ['project-analytics', projectId, dateRange, from, to, sprintId]
```
- Ensures complete cache invalidation whenever:
  - The active project changes (`projectId`).
  - The date preset changes (`7d`, `30d`, `quarter`, `custom`).
  - Custom date boundaries change (`from`, `to`).
  - Active sprint filter changes (`sprintId`).

### 5.3 Request Parameters
Built via `URLSearchParams`:
- `date_range`: `'7d' | '30d' | 'quarter' | 'custom'` (defaults to `'30d'`)
- `from`: string (`YYYY-MM-DD`, optional for custom range)
- `to`: string (`YYYY-MM-DD`, optional for custom range)
- `sprint_id`: string (UUID, optional for targeting specific sprint burndown)

### 5.4 State Management & Return Interface
`useQuery` returns:
- `data`: `ProjectAnalyticsResponse | undefined`
- `isLoading`: `boolean` (true during initial fetch)
- `isError`: `boolean` (true on 401, 403, 404, or 500)
- `error`: `Error | null` (AxiosError containing HTTP status and response message)
- `refetch`: `() => Promise<...>` (manual data refresh)
- `isFetching`: `boolean` (background refetching indicator)
- `staleTime`: `60 * 1000` (1 minute cache TTL before background refetch)
- `enabled`: `Boolean(projectId)` (prevents executing queries with empty projectId)

### 5.5 Response Type Harmony
The TypeScript interface `ProjectAnalyticsResponse` strictly mirrors the backend `ProjectAnalyticsOutput.php`:
- `project`: `{ id, key, name, status }`
- `summary`: `{ total_issues, completed_issues, in_progress_issues, todo_issues, completion_rate }`
- `lead_time`: `{ average_days, median_days, p85_days, distribution: [{ bucket, count }] }`
- `cycle_time`: `{ average_days, median_days, p85_days, distribution: [{ bucket, count }] }`
- `cumulative_flow`: `[{ date, todo, in_progress, done }]`
- `issue_types`: `[{ id, name, slug, count, color, percentage }]`
- `priorities`: `[{ id, name, slug, level, count, color, percentage }]`
- `sprint_metrics`: `{ has_sprints, burndown: [{ day, ideal_points, actual_points }], velocity: [{ sprint_id, sprint_name, committed_points, completed_points }] }`
- `filters`: `{ date_range, start_date, end_date, sprint_id }`

---

## 6. Localization Review (`lang/id.json`)

To achieve 100% Indonesian localization without raw untranslated strings, the following keys used in `ProjectAnalyticsDashboard.tsx` must be present in `lang/id.json`:

| English Key | Indonesian Value | Status in `lang/id.json` |
|---|---|---|
| `Analytics` | `Analitik` | Present |
| `Project Analytics` | `Analitik Proyek` | Present |
| `Lead Time` | `Waktu Tunggu (Lead Time)` | Present |
| `Cycle Time` | `Waktu Siklus (Cycle Time)` | Present |
| `Total Issues` | `Total Tiket` | Present |
| `Completed` | `Selesai` | Present |
| `Project Deep-Dive Analytics` | `Analitik Mendalam Proyek` | Missing |
| `Cycle time, cumulative flow, distribution, and sprint burndown/velocity.` | `Waktu siklus, diagram alir kumulatif, distribusi, dan burndown/kecepatan sprint.` | Missing |
| `Failed to load project analytics` | `Gagal memuat analitik proyek` | Missing |
| `Could not retrieve analytics metrics for this project.` | `Tidak dapat mengambil metrik analitik untuk proyek ini.` | Missing |
| `All Sprints` | `Semua Sprint` | Missing |
| `Active / Latest Sprint` | `Sprint Aktif / Terbaru` | Missing |
| `All issues in this project` | `Semua tiket di proyek ini` | Missing |
| `Delivered successfully` | `Berhasil diselesaikan` | Missing |
| `In Progress` | `Sedang Dikerjakan` | Missing |
| `Currently active in workflow` | `Saat ini aktif dalam alur kerja` | Missing |
| `To Do Backlog` | `Backlog To Do` | Missing |
| `Waiting to be started` | `Menunggu untuk dimulai` | Missing |
| `Lead Time Distribution` | `Distribusi Waktu Tunggu` | Missing |
| `Cycle Time Distribution` | `Distribusi Waktu Siklus` | Missing |
| `Time from issue creation to completion.` | `Waktu dari pembuatan tiket hingga selesai.` | Missing |
| `Time from active work start to completion.` | `Waktu dari awal pengerjaan aktif hingga selesai.` | Missing |
| `Cumulative Flow (CFD)` | `Diagram Alir Kumulatif (CFD)` | Missing |
| `Work in progress bottlenecks and flow stability over time.` | `Hambatan WIP dan stabilitas alir sepanjang waktu.` | Missing |
| `Issue Types Breakdown` | `Rincian Tipe Tiket` | Missing |
| `Priority Breakdown` | `Rincian Prioritas` | Missing |
| `Proportional breakdown of project issues.` | `Rincian proporsional tiket proyek.` | Missing |
| `Sprint Burndown & Velocity` | `Burndown & Kecepatan Sprint` | Missing |
| `Ideal vs actual story points burndown and sprint velocity.` | `Burndown story point ideal vs aktual dan kecepatan sprint.` | Missing |
| `Continuous delivery metrics for kanban workflows.` | `Metrik pengiriman kontinu untuk alur kerja kanban.` | Missing |
| `Continuous Kanban Flow` | `Alir Kanban Kontinu` | Missing |
| `This project uses continuous flow. Track completion rates and lead times above for team velocity.` | `Proyek ini menggunakan alir kontinu. Pantau tingkat penyelesaian dan waktu tunggu di atas untuk kecepatan tim.` | Missing |
| `No Delivery Time Data` | `Tidak Ada Data Waktu Penyelesaian` | Missing |
| `Complete issues to see lead time & cycle time statistics.` | `Selesaikan tiket untuk melihat statistik lead time & cycle time.` | Missing |
| `No Flow Data` | `Tidak Ada Data Alir` | Missing |
| `No issues found in this date range.` | `Tidak ada tiket ditemukan dalam rentang tanggal ini.` | Missing |
| `No Breakdown Data` | `Tidak Ada Data Rincian` | Missing |
| `No issues available to classify.` | `Tidak ada tiket untuk diklasifikasikan.` | Missing |
| `No Sprint Metrics` | `Tidak Ada Metrik Sprint` | Missing |
| `No sprint history available for this project.` | `Tidak ada riwayat sprint untuk proyek ini.` | Missing |

Worker M3 should merge these keys into `lang/id.json`.

---

## 7. Concrete Implementation Blueprint for Worker M3

### Blueprint 1: `IssuesManager.tsx` Changes

```typescript
// 1. Import BarChart2 and ProjectAnalyticsDashboard
import { Search, Activity, BarChart2 } from 'lucide-react';
import { ProjectAnalyticsDashboard } from '@/modules/Workload/Projects/components/ProjectAnalyticsDashboard';

// 2. Expand viewMode state type:
type ProjectTabMode = 'list' | 'board' | 'backlog' | 'analytics';

const [viewMode, setViewModeState] = useState<ProjectTabMode>(() => {
    const search = url.split('?')[1];
    if (search) {
        const params = new URLSearchParams('?' + search);
        const tab = params.get('tab');
        if (tab === 'list' || tab === 'board' || tab === 'backlog' || tab === 'analytics') return tab as ProjectTabMode;
    }
    
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem(`project-${projectId}-viewMode`);
        if (saved === 'list' || saved === 'board' || saved === 'backlog' || saved === 'analytics') return saved as ProjectTabMode;
    }
    return 'board';
});

const setViewMode = (mode: ProjectTabMode) => {
    setViewModeState(mode);
    if (typeof window !== 'undefined') {
        localStorage.setItem(`project-${projectId}-viewMode`, mode);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', mode);
        window.history.replaceState({}, '', url.toString());
    }
};

// 3. Tab Button Pill:
<div className="flex gap-1.5 bg-muted p-1 rounded-lg shrink-0">
    <Button 
        variant={viewMode === 'board' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('board')}
    >
        {t('Board')}
    </Button>
    <Button 
        variant={viewMode === 'list' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('list')}
    >
        {t('List')}
    </Button>
    <Button 
        variant={viewMode === 'backlog' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('backlog')}
    >
        {t('Backlog')}
    </Button>
    <Button 
        variant={viewMode === 'analytics' ? 'default' : 'ghost'} 
        size="sm" 
        className={tabBtnClass}
        onClick={() => setViewMode('analytics')}
    >
        <BarChart2 className="mr-1.5 h-3.5 w-3.5" />
        {t('Analytics')}
    </Button>
</div>

// 4. Suppress issue search & progress bar when in analytics tab:
{viewMode !== 'analytics' && (
    <div className={`flex items-center gap-4 bg-card border rounded-lg ${progressCardPadding}`}>
        ...
    </div>
)}

// 5. Render analytics dashboard:
{viewMode === 'list' ? (
    <IssueList ... />
) : viewMode === 'backlog' ? (
    <BacklogManager ... />
) : viewMode === 'analytics' ? (
    <ProjectAnalyticsDashboard projectId={projectId} />
) : (
    <div className="space-y-4">
        ...
        <KanbanBoard ... />
    </div>
)}
```

### Blueprint 2: `resources/js/modules/Workload/Projects/index.ts`
Add:
```typescript
export * from './components/ProjectAnalyticsDashboard';
```

---

## 8. Summary Table of Tasks for Worker M3

| Action | Target File | Impact |
|---|---|---|
| Mount Analytics Tab | `resources/js/modules/Workload/Issues/components/IssuesManager.tsx` | Enables users to switch to the Analytics view on any project page |
| Barrel Export | `resources/js/modules/Workload/Projects/index.ts` | Clean export of `ProjectAnalyticsDashboard` |
| Add Translation Keys | `lang/id.json` | 32 missing Indonesian translation keys for analytics |
| Verify Build & Types | `npm run types:check && npm run build` | Zero type errors, clean production bundle |
