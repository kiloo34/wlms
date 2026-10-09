# Frontend Architecture & Visualization Systems Survey Report

## 1. Observation

### 1.1 Tech Stack & Tooling
- **Project Configuration (`package.json`)**:
  - React 19: `"react": "^19.2.0"`, `"react-dom": "^19.2.0"`, `"@types/react": "^19.2.0"`.
  - Inertia.js React: `"@inertiajs/react": "^3.0.0"`, `"@inertiajs/vite": "^3.0.0"`.
  - Vite 8 & Vite Plus: `"vite": "^8.0.0"`, `"vite-plus": "0.3.0"`, `"@vitejs/plugin-react": "^6.1.1"`.
  - Tailwind CSS v4: `"tailwindcss": "^4.0.0"`, `"@tailwindcss/vite": "^4.1.11"`, `"tw-animate-css": "^1.4.0"`.
  - UI & Primitives: Radix UI primitives (`@radix-ui/react-*`), Lucide React (`"lucide-react": "^0.475.0"`), Sonner (`"sonner": "^2.0.0"`), Date utilities (`"date-fns": "^4.4.0"`), Class variance authority (`"class-variance-authority": "^0.7.1"`, `"clsx": "^2.1.1"`, `"tailwind-merge": "^3.0.1"`).
  - Data Fetching: TanStack React Query (`"@tanstack/react-query": "^5.102.8"`), TanStack React Table (`"@tanstack/react-table": "^8.21.3"`), Axios (`"axios": "^1.20.0"`).
  - TypeScript: `"typescript": "^5.7.2"`.
  - Scripts: `build`: `"vp build"`, `types:check`: `"tsc --noEmit"`. Both pass cleanly with exit code 0 (`npm run types:check` and `npm run build`).

### 1.2 Charting & Visualization Library Status
- **Inspection of `package.json`**:
  - Lines 14-67 & lines 68-83: No charting library is installed (no `recharts`, `chart.js`, `apexcharts`, `@tremor/react`, or `echarts`).
  - Search in `node_modules` for `*chart*` confirmed zero third-party chart libraries present.
- **Inspection of `resources/css/app.css` (lines 58-62, 95-99, 142-146)**:
  - Tailwind v4 `@theme` block defines chart color aliases:
    ```css
    --color-chart-1: var(--chart-1);
    --color-chart-2: var(--chart-2);
    --color-chart-3: var(--chart-3);
    --color-chart-4: var(--chart-4);
    --color-chart-5: var(--chart-5);
    ```
  - Light mode `:root` values (lines 95-99):
    ```css
    --chart-1: oklch(0.646 0.222 41.116); /* Warm coral/orange */
    --chart-2: oklch(0.6 0.118 184.704);  /* Teal/cyan */
    --chart-3: oklch(0.398 0.07 227.392);  /* Navy/slate */
    --chart-4: oklch(0.828 0.189 84.429);  /* Amber/yellow */
    --chart-5: oklch(0.645 0.246 16.439);  /* Crimson */
    ```
  - Dark mode `.dark` values (lines 142-146):
    ```css
    --chart-1: oklch(0.488 0.243 264.376); /* Indigo/blue */
    --chart-2: oklch(0.696 0.17 162.48);   /* Emerald green */
    --chart-3: oklch(0.769 0.188 70.08);   /* Gold/amber */
    --chart-4: oklch(0.627 0.265 303.9);   /* Purple/violet */
    --chart-5: oklch(0.645 0.246 16.439);  /* Rose */
    ```

### 1.3 Appearance System & Theme Harmony
- **Implementation in `resources/js/hooks/use-appearance.tsx`**:
  - `initializeTheme()` (lines 115-140): Called in `resources/js/app.tsx:64`. Syncs theme state with `localStorage`, cookies (`appearance`), system preference (`prefers-color-scheme`), and cross-tab storage listener.
  - `applyTheme(appearance)` (lines 61-70): Toggles `.dark` on `document.documentElement` and sets `document.documentElement.style.colorScheme = isDark ? 'dark' : 'light'`.
  - `useAppearance()` (lines 142-187): Uses `useSyncExternalStore` returning `{ appearance, resolvedAppearance, updateAppearance, iconSize, updateIconSize }`.

### 1.4 Global Density Scaling (`useIconSize` - S / M / L)
- **Implementation in `resources/js/hooks/use-appearance.tsx` (lines 5, 44-55, 72-80, 189-195)**:
  - Supported density values: `export type IconSize = 'sm' | 'md' | 'lg';`.
  - Stored in `localStorage.getItem('icon_size')` (and fallback `wlms_projects_icon_size`). Default is `'md'`.
  - DOM reflection: Sets `document.documentElement.setAttribute('data-icon-size', size)` and classes `icon-size-sm`, `icon-size-md`, `icon-size-lg`.
  - Hook export: `export function useIconSize(): { readonly iconSize: IconSize; readonly updateIconSize: (size: IconSize) => void; }`.
- **Observed Component Usage Patterns**:
  - In `WorkspaceTasksTable.tsx:243-273` & `Projects/Show.tsx:104-133`: A segmented density button group (`S`, `M`, `L`) is presented in the toolbar.
  - In `SprintWorkloadWidget.tsx:30-36`:
    ```tsx
    const avatarClass = iconSize === 'sm' ? 'h-4.5 w-4.5' : iconSize === 'lg' ? 'h-6 w-6' : 'h-5 w-5';
    const textClass = iconSize === 'sm' ? 'text-[11px]' : iconSize === 'lg' ? 'text-sm' : 'text-xs';
    const progressHeight = iconSize === 'sm' ? 'h-1' : iconSize === 'lg' ? 'h-2' : 'h-1.5';
    ```
  - In `WorkspaceTasksTable.tsx:315-317`:
    ```tsx
    className={`hover:bg-muted/40 transition-colors ${
        iconSize === 'sm' ? 'h-11 text-xs' : iconSize === 'lg' ? 'h-16 text-sm' : 'h-13'
    }`}
    ```
  - In `IssuesManager.tsx:209-226`:
    ```tsx
    const searchInputClass = iconSize === 'sm' ? 'h-8 text-xs pl-8' : iconSize === 'lg' ? 'h-10 text-sm pl-8' : 'h-9 text-xs pl-8';
    const tabBtnClass = iconSize === 'sm' ? 'h-7.5 px-2.5 text-xs' : iconSize === 'lg' ? 'h-9.5 px-4 text-sm' : 'h-8 px-3 text-xs';
    const progressCardPadding = iconSize === 'sm' ? 'p-3 text-xs' : iconSize === 'lg' ? 'p-5 text-base' : 'p-4 text-sm';
    const progressBarHeight = iconSize === 'sm' ? 'h-1.5' : iconSize === 'lg' ? 'h-2.5' : 'h-2';
    ```

### 1.5 Localization System (`useTranslate` & `lang/id.json`)
- **Hook in `resources/js/hooks/useTranslate.ts`**:
  - Reads `{ locale, translations }` from Inertia `usePage<SharedData>().props`.
  - Signature: `t(key: string, replacements?: Record<string, string | number>) => string`.
  - Performs regex replacement: `translation.replace(new RegExp(':' + replaceKey, 'g'), String(replacements[replaceKey]))`.
  - Falls back to `key` if no translation entry exists in `translations`.
- **Backend Provider (`HandleInertiaRequests.php:113-120`)**:
  - Shares `'locale' => app()->getLocale()` and `'translations' => json_decode(file_get_contents(lang/{locale}.json))`.
- **Language Dictionary (`lang/id.json`)**:
  - Contains 471 lines of key-value mappings where the English string is the JSON key and Indonesian is the translation value.

### 1.6 Page Layouts & Navigation Structure
- **Workspace Navigation**:
  - Workspace Index: `resources/js/pages/Workspaces/Index.tsx` (`/workspaces`).
  - Workspace Issues/Tasks: `resources/js/pages/Workspaces/Issues.tsx` (`/workspaces/{workspace_id}/issues`).
  - Controller: `WorkspaceIssuesPageController.php` rendering `inertia('Workspaces/Issues', ...)`.
- **Project Navigation**:
  - Project Show: `resources/js/pages/Projects/Show.tsx` (`/projects/{project}`).
  - Controller: `ShowProjectPageController.php` rendering `inertia('Projects/Show', ...)`.
  - View Modes in `IssuesManager.tsx:56-80`:
    - Tab state `viewMode` toggles between `'board' | 'list' | 'backlog'`, synced via URL parameter `?tab=board|list|backlog` and `localStorage.setItem('project-${projectId}-viewMode', mode)`.

---

## 2. Logic Chain

### 2.1 Charting Solution Strategy
1. **Fact (Observation 1.1 & 1.2)**: The application is running React 19 (`19.2.0`) with Vite 8. No charting library is currently installed in `package.json`.
2. **Fact (Observation 1.2)**: Standard external React charting libraries (e.g., Recharts 2.x, Chart.js React wrappers) frequently declare `peerDependencies` limited to React 18 and can introduce dependency conflicts, bundle bloat (>250KB), or hydration warnings under React 19 / Vite.
3. **Fact (Observation 1.2)**: `app.css` already contains native Tailwind v4 CSS chart variables (`--chart-1` through `--chart-5`) with distinct OKLCH palettes for both light and dark modes.
4. **Deduction**: Developing pure React SVG visualization components (e.g., `ThroughputBarChart`, `WorkloadStackedBar`, `DonutDistributionChart`, `CumulativeFlowAreaChart`, `BurndownLineChart`):
   - Requires zero external npm packages, guaranteeing zero dependency failure, zero React 19 peer conflict, and 100% build pass (`vp build`).
   - Inherits Tailwind v4 CSS color variables automatically (`fill-[var(--chart-1)]`, `stroke-[var(--chart-2)]`, etc.), enabling zero-lag theme transitions when toggling Dark/Light mode.
   - Provides pixel-perfect responsive vector scaling via SVG `viewBox` and `preserveAspectRatio="none"`.
   - Supports native hover tooltips integrated with Radix UI `@/components/ui/tooltip` or SVG hover coordinates.
   - Enables direct adaptation to `useIconSize` density modes.

### 2.2 Global Appearance & Dark/Light Theme Integration
1. **Fact (Observation 1.3)**: Toggling appearance toggles `.dark` on `<html>`.
2. **Fact (Observation 1.2)**: `--chart-1` to `--chart-5` automatically swap values under `.dark`.
3. **Deduction**: All chart components and visualization cards must strictly reference semantic Tailwind tokens (`bg-card`, `text-card-foreground`, `border-border`, `text-muted-foreground`, `bg-muted/40`) and chart CSS variables (`var(--chart-1)` through `var(--chart-5)`, or `var(--color-chart-1)`). Hardcoded hex or rgb strings must be avoided so contrast and color harmony remain flawless across mode switches.

### 2.3 Global S / M / L Display Density Integration
1. **Fact (Observation 1.4)**: `useIconSize` returns `iconSize: 'sm' | 'md' | 'lg'`.
2. **Deduction**: All reporting widgets and charts must adhere to the 3-tier density scale:
   - **Card & Padding Scale**:
     - `sm`: `p-3.5 gap-2 rounded-lg`
     - `md`: `p-5 gap-3 rounded-xl`
     - `lg`: `p-6 gap-4 rounded-2xl`
   - **KPI Metric Typography**:
     - `sm`: Label `text-[11px]`, Metric `text-xl font-bold`
     - `md`: Label `text-xs`, Metric `text-2xl font-bold`
     - `lg`: Label `text-sm`, Metric `text-3xl font-extrabold`
   - **Chart Height & Bar/Stroke Scale**:
     - `sm`: Container height `h-[180px]`, bar thickness `w-4` / `16px`, stroke `1.5px`, axis label `text-[10px]`
     - `md`: Container height `h-[240px]`, bar thickness `w-6` / `24px`, stroke `2px`, axis label `text-xs`
     - `lg`: Container height `h-[320px]`, bar thickness `w-8` / `32px`, stroke `2.5px`, axis label `text-sm`
   - **Filter & Action Controls**:
     - `sm`: Button `h-7.5 px-2.5 text-xs`, Icon `h-3.5 w-3.5`
     - `md`: Button `h-8 px-3 text-xs`, Icon `h-4 w-4`
     - `lg`: Button `h-9.5 px-4 text-sm`, Icon `h-4.5 w-4.5`

### 2.4 Localization Architecture & Translation Key Inventory
1. **Fact (Observation 1.5)**: Any UI string rendered without an entry in `lang/id.json` will display in English even when Indonesian is selected.
2. **Deduction**: Every user-facing term for R1, R2, and R4 must be wrapped in `t('...')` and mapped in `lang/id.json`.

---

## 3. Caveats
- No caveats regarding frontend build tools: `vp build` and `tsc --noEmit` have been directly tested and pass without issue.
- If external npm chart packages are introduced in the future, care must be taken with React 19 compatibility (`peerDependencies`). Pure SVG components bypass this risk entirely.

---

## 4. Conclusion & Actionable Architecture Plan

### 4.1 Component Inventory & Proposed Architecture

#### 4.1.1 Chart & Visualization Components (`resources/js/components/charts/`)
1. **`ResponsiveContainer.tsx`**:
   - Container component measuring available width/height or rendering responsive SVG `viewBox`.
   - Props: `aspectRatio?`, `minHeight?`, `densityHeight?: Record<IconSize, string>`.
2. **`BarChart.tsx` & `StackedBarChart.tsx`**:
   - Used for:
     - R1: Portfolio throughput (tasks completed per week/month).
     - R1: Cross-project member workload (task count & hours per assignee).
     - R2: Issue type and priority breakdown.
   - Features: Interactive SVG rect elements, hover indicator, Radix-based tooltip displaying detailed breakdown, customizable color palettes mapped to `--chart-1` ... `--chart-5`.
3. **`CumulativeFlowChart.tsx` (CFD)**:
   - Used for R2: Cumulative Flow Diagram / Status trends over time showing bottlenecks (To Do, In Progress, In Review, Done).
   - Features: Stacked SVG area paths (`d="M ... Z"`) with translucent fills (`opacity-80`), hover cursor line, tooltip with status counts on hovered date.
4. **`BurndownVelocityChart.tsx`**:
   - Used for R2: Sprint Burndown (Ideal vs Actual Remaining story points/tasks) & Sprint Velocity comparison.
   - Features: Multi-line SVG paths with data points (`<circle>`), dashed ideal line, velocity comparison bars.
5. **`LeadTimeHistogram.tsx`**:
   - Used for R2: Cycle time & lead time distributions (e.g. 0-2 days, 3-5 days, 6-10 days, >10 days) with median and 85th percentile markers.
6. **`ChartTooltip.tsx` & `ChartLegend.tsx`**:
   - Reusable legend with color dot indicator (`rounded-full size-2.5`) and density-adaptive font size.
   - Reusable hover floating tooltip with dark/light background styling.

#### 4.1.2 Date Range Picker Component (`resources/js/components/DateRangeFilter.tsx`)
- Supports R1 requirement: "Date range filtering (Last 7 Days, Last 30 Days, This Quarter, Custom Range)".
- UI Structure:
  - Preset segmented buttons: `Last 7 Days` | `Last 30 Days` | `This Quarter` | `Custom Range`.
  - When `Custom Range` is selected: Popover opens showing Start Date and End Date pickers (`<Input type="date">` or calendar) with "Apply" button.
  - Fully reactive with `useTranslate()` and `useIconSize()`.

#### 4.1.3 Workspace Macro Analytics Hub (`resources/js/pages/Workspaces/Analytics.tsx`)
- **Route**: `GET /workspaces/{workspace_id}/analytics` (Inertia page).
- **Navigation Integration**:
  - In `Workspaces/Issues.tsx`, add a navigation tab header:
    - Tab 1: `All Tasks` (`/workspaces/{workspace_id}/issues`)
    - Tab 2: `Analytics` (`/workspaces/{workspace_id}/analytics`)
- **Data Hook**: `useWorkspaceAnalytics(workspaceId, dateRange)` using `@tanstack/react-query` to call `GET /api/workspaces/{workspace_id}/analytics`.
- **Layout & Sections**:
  1. **Top Bar**: Workspace title, date range filter selector, density toggle (`S` / `M` / `L`), refresh button.
  2. **Macro KPI Cards Row**:
     - Total Completed Tasks & Throughput (with delta % vs previous period).
     - Average Completion Rate & Progress %.
     - Active Projects count & Health breakdown (On Track, At Risk, Off Track).
     - Total Logged Hours vs Estimated Hours.
  3. **Portfolio Throughput Chart**: Weekly/monthly completion velocity bar chart across projects.
  4. **Cross-Project Workload Distribution**: Horizontal stacked bars per member (task count & hours allocated).
  5. **Project Health & Progress Table**: Summary table showing each project, completion progress bar, open issues, and throughput.

#### 4.1.4 Project Deep Dive Analytics (`resources/js/modules/Workload/Projects/components/ProjectAnalyticsDashboard.tsx`)
- **Route & Tab Integration**:
  - In `resources/js/modules/Workload/Issues/components/IssuesManager.tsx`:
    - Extend `viewMode` state type: `'board' | 'list' | 'backlog' | 'analytics'`.
    - Add tab button: `{t('Analytics')}` with `SlidersHorizontal` / `BarChart3` icon.
    - Sync with URL parameter: `/projects/{id}?tab=analytics`.
  - Also provide dedicated Inertia route: `/projects/{project_id}/analytics` pointing to `Projects/Show` with `tab: 'analytics'`.
- **Data Hook**: `useProjectAnalytics(projectId, dateRange)` calling `GET /api/projects/{projectId}/analytics`.
- **Layout & Sections**:
  1. **Header Controls**: Date range selector and sprint selector (if sprints exist in project).
  2. **Cycle Time & Lead Time Metric Cards & Distribution**:
     - Average Lead Time (creation to completion) & Average Cycle Time (start to finish).
     - Distribution histogram / box plot.
  3. **Cumulative Flow Diagram (CFD)**:
     - Stacked area chart illustrating task progression through workflow states over time, highlighting bottleneck build-ups.
  4. **Sprint Velocity / Burndown or Continuous Flow Throughput**:
     - If active sprint exists: Burndown chart (Ideal vs Actual Remaining) + historical velocity bars.
     - If kanban (no sprints): Continuous flow throughput trend.
  5. **Issue Breakdown**:
     - Donut / segment charts for Issue Type distribution (Task, Bug, Story) and Priority breakdown (Urgent, High, Medium, Low).

#### 4.1.5 Empty States and Loading Skeletons
- **Empty State Component (`ChartEmptyState.tsx`)**:
  - Clean card with dashed border: `border-2 border-dashed border-border/70 rounded-xl flex flex-col items-center justify-center p-8 text-center bg-muted/10`.
  - Icon: `BarChart3` in a muted circular background.
  - Title: `{t('No analytics data available')}`.
  - Subtitle: `{t('No completed tasks or activity recorded in the selected period.')}`.
- **Skeleton Loading State (`ChartCardSkeleton.tsx`)**:
  - Card with header skeleton (`<Skeleton className="h-4 w-32" />`), badge skeleton, and chart area `<Skeleton className="h-[220px] w-full rounded-lg" />`.

---

### 4.2 Complete Localization Dictionary to Add in `lang/id.json`

```json
{
  "Analytics": "Analitik",
  "Workspace Analytics": "Analitik Ruang Kerja",
  "Project Analytics": "Analitik Proyek",
  "Portfolio Throughput": "Throughput Portofolio",
  "Project Progress & Health": "Progres & Kesehatan Proyek",
  "Team Workload Distribution": "Distribusi Beban Kerja Tim",
  "Cross-Project Workload": "Beban Kerja Lintas Proyek",
  "Completion Rate": "Tingkat Penyelesaian",
  "Tasks Completed": "Tiket Selesai",
  "Active Projects": "Proyek Aktif",
  "Total Tasks": "Total Tiket",
  "Logged Hours": "Jam Tercatat",
  "Estimated Hours": "Estimasi Jam",
  "Date Range": "Rentang Tanggal",
  "Last 7 Days": "7 Hari Terakhir",
  "Last 30 Days": "30 Hari Terakhir",
  "This Quarter": "Kuartal Ini",
  "Custom Range": "Rentang Kustom",
  "Start Date": "Tanggal Mulai",
  "End Date": "Tanggal Selesai",
  "Apply": "Terapkan",
  "Filter by Project": "Filter berdasarkan Proyek",
  "All Projects": "Semua Proyek",
  "Lead Time": "Waktu Tunggu (Lead Time)",
  "Cycle Time": "Waktu Siklus (Cycle Time)",
  "Average Lead Time": "Rata-rata Lead Time",
  "Average Cycle Time": "Rata-rata Cycle Time",
  "Days": "Hari",
  "Cumulative Flow Diagram": "Diagram Alir Kumulatif (CFD)",
  "Status Trend": "Tren Status",
  "Issue Type Distribution": "Distribusi Tipe Tiket",
  "Priority Breakdown": "Rincian Prioritas",
  "Sprint Velocity": "Kecepatan Sprint (Velocity)",
  "Sprint Burndown": "Burndown Sprint",
  "Ideal Burndown": "Burndown Ideal",
  "Actual Remaining": "Sisa Aktual",
  "Continuous Flow Throughput": "Throughput Alir Kontinu",
  "Bottlenecks": "Hambatan (Bottlenecks)",
  "On Track": "Sesuai Jadwal",
  "At Risk": "Beresiko",
  "Off Track": "Terlambat",
  "No analytics data available": "Tidak ada data analitik",
  "No data for selected period": "Tidak ada data untuk periode yang dipilih",
  "Completed per week": "Selesai per minggu",
  "Story Points": "Story Point",
  "Assignee": "Penerima Tugas",
  "Throughput": "Throughput",
  "Velocity": "Kecepatan",
  "Overview": "Ikhtisar"
}
```

---

## 5. Verification Method

To verify the frontend implementation:
1. **TypeScript Validation**:
   ```bash
   npm run types:check
   ```
   *Expected result*: Exit code 0, 0 type errors.
2. **Production Bundle Build**:
   ```bash
   npm run build
   ```
   *Expected result*: `vp build` completes successfully without chunking, SSR, or CSS errors.
3. **Theme Switching Verification**:
   - Inspect the DOM when toggling theme between light and dark:
     - Verify `document.documentElement` toggles `.dark`.
     - Verify SVG chart fills and strokes seamlessly render light OKLCH tokens vs dark OKLCH tokens without color clipping.
4. **Density Scaling Verification**:
   - Toggle S / M / L density buttons:
     - Verify `document.documentElement.getAttribute('data-icon-size')` reflects `'sm'`, `'md'`, and `'lg'`.
     - Verify chart containers dynamically adjust heights (`180px` / `240px` / `320px`) and typography scales correctly.
5. **Localization Verification**:
   - Switch application language to Bahasa Indonesia (`id`):
     - Verify all titles, metrics, chart axis labels, legends, and tooltips render translated Indonesian strings.
