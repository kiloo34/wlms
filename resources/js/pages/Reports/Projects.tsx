import { useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import { toPng } from 'html-to-image';
import {
    AlertOctagon,
    AlertTriangle,
    Archive,
    CalendarX,
    CheckCheck,
    Download,
    FolderKanban,
    ListChecks,
    Loader2,
    TrendingUp,
    UserCircle2,
    Users,
} from 'lucide-react';
import { toast } from 'sonner';
import axios from '@/lib/axios';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { Progress } from '@/components/ui/progress';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTranslate } from '@/hooks/useTranslate';
import { useGetWorkspaces } from '@/modules/Workload/Workspaces/hooks/useWorkspaces';
import { RemainingDaysBadge } from '@/modules/Workload/shared/RemainingDaysBadge';
import { WorkTypeBadge } from '@/modules/Workload/shared/WorkTypeBadge';
import { formatDisplayDate } from '@/modules/Workload/shared/schedule';
import { ReferenceText } from '@/modules/Workload/shared/ReferenceText';
import { referencesToPlainText } from '@/modules/Workload/shared/references';

type Health =
    | 'on_track'
    | 'at_risk'
    | 'overdue'
    | 'done'
    | 'no_schedule'
    | 'archived';

interface ReportProject {
    id: string;
    key: string;
    name: string;
    description: string | null;
    status: string;
    workspace: { id: string; name: string | null };
    owner: { id: number; name: string | null } | null;
    start_date: string | null;
    end_date: string | null;
    duration_days: number | null;
    remaining_days: number | null;
    elapsed_percent: number | null;
    total_issues: number;
    done_issues: number;
    open_issues: number;
    overdue_issues: number;
    progress: number;
    health: Health;
    open_by_work_type: Record<string, number>;
}

interface ReportOwner {
    id: number;
    name: string;
    email: string;
    projects_owned: number;
    open_tasks: number;
    overdue_tasks: number;
    overdue_projects: number;
    next_deadline: string | null;
    remaining_days: number | null;
}

interface ReportData {
    generated_at: string;
    today: string;
    summary: {
        total_projects: number;
        on_track: number;
        at_risk: number;
        overdue: number;
        done: number;
        no_schedule: number;
        archived: number;
        total_issues: number;
        done_issues: number;
        open_issues: number;
        overdue_issues: number;
        completion_rate: number;
    };
    projects: ReportProject[];
    owners: ReportOwner[];
    work_types: {
        type: string;
        label: string;
        total: number;
        open: number;
        done: number;
        overdue: number;
    }[];
}

interface OwnerDetail {
    owner: { id: number; name: string; email: string };
    projects: {
        id: string;
        key: string;
        name: string;
        description: string | null;
        workspace: { id: string; name: string | null };
        start_date: string | null;
        end_date: string | null;
        duration_days: number | null;
        remaining_days: number | null;
        total_issues: number;
        done_issues: number;
        progress: number;
    }[];
    tasks: {
        id: string;
        key: string;
        title: string;
        description: string | null;
        work_type: string;
        start_date: string | null;
        due_date: string | null;
        duration_days: number | null;
        remaining_days: number | null;
        is_overdue: boolean;
        project: { id: string; key: string; name: string };
        status: { name: string | null };
    }[];
    summary: {
        projects_owned: number;
        open_tasks: number;
        done_tasks: number;
        overdue_tasks: number;
    };
}

// Status kesehatan: warna status + ikon + label (tidak pernah warna saja).
const HEALTH: Record<
    Health,
    { label: string; icon: typeof CheckCheck; className: string }
> = {
    overdue: {
        label: 'Overdue',
        icon: AlertOctagon,
        className:
            'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300',
    },
    at_risk: {
        label: 'At risk',
        icon: AlertTriangle,
        className:
            'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    on_track: {
        label: 'On track',
        icon: TrendingUp,
        className:
            'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
    },
    done: {
        label: 'Completed',
        icon: CheckCheck,
        className:
            'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    no_schedule: {
        label: 'No schedule',
        icon: CalendarX,
        className: 'text-muted-foreground border-dashed',
    },
    archived: {
        label: 'Archived',
        icon: Archive,
        className: 'text-muted-foreground',
    },
};

const HEALTH_FILTERS: (Health | 'all')[] = [
    'all',
    'overdue',
    'at_risk',
    'on_track',
    'done',
    'no_schedule',
];

function HealthBadge({ health }: { health: Health }) {
    const { t } = useTranslate();
    const config = HEALTH[health];
    const Icon = config.icon;
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-medium whitespace-nowrap',
                config.className,
            )}
        >
            <Icon className="h-3 w-3" aria-hidden />
            {t(config.label)}
        </span>
    );
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function StatTile({
    label,
    value,
    hint,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    hint?: string;
    icon: typeof CheckCheck;
}) {
    return (
        <Card className="gap-0 py-0">
            <CardContent className="flex items-start justify-between gap-3 p-4">
                <div className="min-w-0">
                    <p className="text-muted-foreground text-xs font-medium">
                        {label}
                    </p>
                    <p className="mt-1 text-2xl font-semibold tabular-nums">
                        {value}
                    </p>
                    {hint && (
                        <p className="text-muted-foreground mt-0.5 truncate text-xs">
                            {hint}
                        </p>
                    )}
                </div>
                <Icon
                    className="text-muted-foreground h-5 w-5 shrink-0"
                    aria-hidden
                />
            </CardContent>
        </Card>
    );
}

export default function ProjectReport() {
    const { t, locale } = useTranslate();
    const reportRef = useRef<HTMLDivElement>(null);
    const [workspaceId, setWorkspaceId] = useState('all');
    const [includeArchived, setIncludeArchived] = useState(false);
    const [healthFilter, setHealthFilter] = useState<Health | 'all'>('all');
    const [selectedOwner, setSelectedOwner] = useState<ReportOwner | null>(
        null,
    );
    const [isExporting, setIsExporting] = useState(false);

    const { data: workspaces = [] } = useGetWorkspaces();

    const { data, isLoading, isError } = useQuery<ReportData>({
        queryKey: ['reports', 'projects', workspaceId, includeArchived],
        queryFn: async () => {
            const { data } = await axios.get('/api/reports/projects', {
                params: {
                    workspace_id:
                        workspaceId === 'all' ? undefined : workspaceId,
                    include_archived: includeArchived ? 1 : undefined,
                },
            });
            return data.data;
        },
    });

    const projects = useMemo(
        () =>
            (data?.projects ?? []).filter(
                (p) => healthFilter === 'all' || p.health === healthFilter,
            ),
        [data, healthFilter],
    );

    const workspaceOptions = [
        { value: 'all', label: t('All workspaces') },
        ...workspaces.map((w: { id: string; name: string }) => ({
            value: w.id,
            label: w.name,
        })),
    ];

    const handleExport = async () => {
        const node = reportRef.current;
        if (!node) return;

        setIsExporting(true);
        // Tunggu render ulang tanpa scroll horizontal agar seluruh tabel ikut tergambar.
        await new Promise((resolve) =>
            requestAnimationFrame(() => requestAnimationFrame(resolve)),
        );

        try {
            const background =
                getComputedStyle(document.body).backgroundColor || '#ffffff';
            const dataUrl = await toPng(node, {
                pixelRatio: 2,
                cacheBust: true,
                backgroundColor: background,
                width: node.scrollWidth,
                height: node.scrollHeight,
                filter: (el) =>
                    !(el instanceof HTMLElement && el.dataset.exportIgnore),
            });
            const link = document.createElement('a');
            link.download = `laporan-project-${data?.today ?? 'wlms'}.png`;
            link.href = dataUrl;
            link.click();
            toast.success(t('Report image downloaded.'));
        } catch {
            toast.error(t('Failed to export the report image.'));
        } finally {
            setIsExporting(false);
        }
    };

    const summary = data?.summary;

    return (
        <>
            <Head title={t('Project Report')} />

            <div className="flex h-full w-full flex-col gap-4 p-4 md:p-6">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {t('Project Report')}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {t(
                                'All projects, owners, and remaining time on one screen.',
                            )}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="w-56">
                            <Combobox
                                options={workspaceOptions}
                                value={workspaceId}
                                onChange={(v) => setWorkspaceId(v || 'all')}
                                placeholder={t('Filter workspace...')}
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                id="include_archived"
                                checked={includeArchived}
                                onCheckedChange={setIncludeArchived}
                            />
                            <Label
                                htmlFor="include_archived"
                                className="text-sm font-normal"
                            >
                                {t('Include archived')}
                            </Label>
                        </div>
                        <Button
                            onClick={handleExport}
                            disabled={isExporting || isLoading || !data}
                        >
                            {isExporting ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <Download className="mr-2 h-4 w-4" />
                            )}
                            {t('Export as image')}
                        </Button>
                    </div>
                </div>

                <div
                    ref={reportRef}
                    className={cn(
                        'bg-background flex flex-col gap-4 rounded-lg',
                        isExporting && 'w-max min-w-[1280px] p-6',
                    )}
                >
                    {/* Judul ini ikut tergambar pada hasil ekspor. */}
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 className="text-lg font-semibold">
                            {t('Project Report')}
                            {workspaceId !== 'all' &&
                                ` · ${workspaceOptions.find((o) => o.value === workspaceId)?.label ?? ''}`}
                        </h2>
                        {data && (
                            <span className="text-muted-foreground text-xs">
                                {t('Generated :date', {
                                    date: new Date(
                                        data.generated_at,
                                    ).toLocaleString(
                                        locale === 'id' ? 'id-ID' : 'en-GB',
                                    ),
                                })}
                            </span>
                        )}
                    </div>

                    {isError ? (
                        <Card>
                            <CardContent className="text-destructive p-6 text-sm">
                                {t('Failed to load the report.')}
                            </CardContent>
                        </Card>
                    ) : isLoading || !summary ? (
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                            {Array.from({ length: 6 }).map((_, i) => (
                                <Skeleton key={i} className="h-24" />
                            ))}
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                            <StatTile
                                label={t('Projects')}
                                value={summary.total_projects}
                                hint={t(':count without schedule', {
                                    count: summary.no_schedule,
                                })}
                                icon={FolderKanban}
                            />
                            <StatTile
                                label={t('On track')}
                                value={summary.on_track}
                                icon={TrendingUp}
                            />
                            <StatTile
                                label={t('At risk')}
                                value={summary.at_risk}
                                icon={AlertTriangle}
                            />
                            <StatTile
                                label={t('Overdue')}
                                value={summary.overdue}
                                hint={t(':count overdue tasks', {
                                    count: summary.overdue_issues,
                                })}
                                icon={AlertOctagon}
                            />
                            <StatTile
                                label={t('Completed')}
                                value={summary.done}
                                icon={CheckCheck}
                            />
                            <StatTile
                                label={t('Task completion')}
                                value={`${summary.completion_rate}%`}
                                hint={t(':done of :total tasks', {
                                    done: summary.done_issues,
                                    total: summary.total_issues,
                                })}
                                icon={ListChecks}
                            />
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                        <Card className="min-w-0 gap-0 py-0">
                            <CardHeader className="gap-3 border-b p-4">
                                <div>
                                    <CardTitle className="text-base">
                                        {t('All projects')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t(
                                            'Owner, schedule, remaining time, and progress of each project.',
                                        )}
                                    </CardDescription>
                                </div>
                                <div className="flex flex-wrap gap-1.5">
                                    {HEALTH_FILTERS.map((key) => {
                                        const count =
                                            key === 'all'
                                                ? (data?.projects.length ?? 0)
                                                : (data?.projects.filter(
                                                      (p) => p.health === key,
                                                  ).length ?? 0);
                                        return (
                                            <Button
                                                key={key}
                                                size="sm"
                                                variant={
                                                    healthFilter === key
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="h-7 px-2.5 text-xs"
                                                onClick={() =>
                                                    setHealthFilter(key)
                                                }
                                            >
                                                {key === 'all'
                                                    ? t('All')
                                                    : t(HEALTH[key].label)}
                                                <span className="ml-1 tabular-nums opacity-70">
                                                    {count}
                                                </span>
                                            </Button>
                                        );
                                    })}
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div
                                    className={cn(
                                        isExporting
                                            ? 'overflow-visible'
                                            : 'overflow-x-auto',
                                    )}
                                >
                                    <table className="w-full min-w-[860px] text-sm">
                                        <thead className="bg-muted/50 text-muted-foreground text-left text-xs">
                                            <tr>
                                                <th className="px-4 py-2 font-medium">
                                                    {t('Project')}
                                                </th>
                                                <th className="px-3 py-2 font-medium">
                                                    {t('Owner')}
                                                </th>
                                                <th className="px-3 py-2 font-medium">
                                                    {t('Schedule')}
                                                </th>
                                                <th className="px-3 py-2 font-medium">
                                                    {t('Remaining time')}
                                                </th>
                                                <th className="w-[170px] px-3 py-2 font-medium">
                                                    {t('Progress')}
                                                </th>
                                                <th className="px-3 py-2 font-medium">
                                                    {t('Health')}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {isLoading ? (
                                                <tr>
                                                    <td
                                                        colSpan={6}
                                                        className="p-4"
                                                    >
                                                        <Skeleton className="h-24 w-full" />
                                                    </td>
                                                </tr>
                                            ) : projects.length === 0 ? (
                                                <tr>
                                                    <td
                                                        colSpan={6}
                                                        className="text-muted-foreground p-8 text-center"
                                                    >
                                                        {t(
                                                            'No projects match this filter.',
                                                        )}
                                                    </td>
                                                </tr>
                                            ) : (
                                                projects.map((project) => (
                                                    <tr
                                                        key={project.id}
                                                        className="hover:bg-muted/40 cursor-pointer align-top"
                                                        onClick={() =>
                                                            router.visit(
                                                                `/projects/${project.id}`,
                                                            )
                                                        }
                                                    >
                                                        <td className="max-w-[320px] px-4 py-3">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-muted-foreground font-mono text-[11px]">
                                                                    {
                                                                        project.key
                                                                    }
                                                                </span>
                                                                <span className="truncate font-medium">
                                                                    {
                                                                        project.name
                                                                    }
                                                                </span>
                                                            </div>
                                                            <p className="text-muted-foreground text-xs">
                                                                {project
                                                                    .workspace
                                                                    .name ??
                                                                    '-'}
                                                            </p>
                                                            {project.description && (
                                                                <p
                                                                    className="text-muted-foreground mt-1 line-clamp-2 text-xs"
                                                                    title={referencesToPlainText(
                                                                        project.description,
                                                                    )}
                                                                >
                                                                    <ReferenceText
                                                                        text={
                                                                            project.description
                                                                        }
                                                                    />
                                                                </p>
                                                            )}
                                                            {Object.keys(
                                                                project.open_by_work_type,
                                                            ).length > 0 && (
                                                                <div className="mt-1.5 flex flex-wrap gap-1">
                                                                    {Object.entries(
                                                                        project.open_by_work_type,
                                                                    )
                                                                        .filter(
                                                                            ([
                                                                                ,
                                                                                count,
                                                                            ]) =>
                                                                                count >
                                                                                0,
                                                                        )
                                                                        .map(
                                                                            ([
                                                                                type,
                                                                                count,
                                                                            ]) => (
                                                                                <span
                                                                                    key={
                                                                                        type
                                                                                    }
                                                                                    className="inline-flex items-center gap-1"
                                                                                >
                                                                                    <WorkTypeBadge
                                                                                        value={
                                                                                            type
                                                                                        }
                                                                                    />
                                                                                    <span className="text-muted-foreground text-[11px] tabular-nums">
                                                                                        {
                                                                                            count
                                                                                        }
                                                                                    </span>
                                                                                </span>
                                                                            ),
                                                                        )}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3 text-xs">
                                                            {project.owner
                                                                ?.name ?? (
                                                                <span className="text-muted-foreground">
                                                                    {t(
                                                                        'No owner',
                                                                    )}
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="text-muted-foreground px-3 py-3 text-xs whitespace-nowrap">
                                                            {project.start_date ||
                                                            project.end_date ? (
                                                                <>
                                                                    <div>
                                                                        {formatDisplayDate(
                                                                            project.start_date,
                                                                            locale,
                                                                        )}{' '}
                                                                        -{' '}
                                                                        {formatDisplayDate(
                                                                            project.end_date,
                                                                            locale,
                                                                        )}
                                                                    </div>
                                                                    {project.duration_days && (
                                                                        <div>
                                                                            {t(
                                                                                ':days days',
                                                                                {
                                                                                    days: project.duration_days,
                                                                                },
                                                                            )}
                                                                        </div>
                                                                    )}
                                                                </>
                                                            ) : (
                                                                '-'
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            <RemainingDaysBadge
                                                                days={
                                                                    project.remaining_days
                                                                }
                                                                done={
                                                                    project.health ===
                                                                    'done'
                                                                }
                                                            />
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            <div className="text-muted-foreground mb-1 flex justify-between text-xs">
                                                                <span className="tabular-nums">
                                                                    {
                                                                        project.done_issues
                                                                    }
                                                                    /
                                                                    {
                                                                        project.total_issues
                                                                    }
                                                                </span>
                                                                <span className="text-foreground font-medium tabular-nums">
                                                                    {
                                                                        project.progress
                                                                    }
                                                                    %
                                                                </span>
                                                            </div>
                                                            <Progress
                                                                value={
                                                                    project.progress
                                                                }
                                                                className="h-1.5"
                                                            />
                                                            {project.elapsed_percent !==
                                                                null && (
                                                                <p className="text-muted-foreground mt-1 text-[11px]">
                                                                    {t(
                                                                        'Time elapsed :pct%',
                                                                        {
                                                                            pct: project.elapsed_percent,
                                                                        },
                                                                    )}
                                                                </p>
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            <HealthBadge
                                                                health={
                                                                    project.health
                                                                }
                                                            />
                                                            {project.overdue_issues >
                                                                0 && (
                                                                <p className="mt-1 text-[11px] text-red-700 dark:text-red-300">
                                                                    {t(
                                                                        ':count overdue tasks',
                                                                        {
                                                                            count: project.overdue_issues,
                                                                        },
                                                                    )}
                                                                </p>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex min-w-0 flex-col gap-4">
                            <OwnerRecap
                                owners={data?.owners ?? []}
                                isLoading={isLoading}
                                onSelect={setSelectedOwner}
                            />
                            <WorkTypeSummary
                                rows={data?.work_types ?? []}
                                isLoading={isLoading}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <OwnerDetailSheet
                owner={selectedOwner}
                onClose={() => setSelectedOwner(null)}
            />
        </>
    );
}

function OwnerRecap({
    owners,
    isLoading,
    onSelect,
}: {
    owners: ReportOwner[];
    isLoading: boolean;
    onSelect: (owner: ReportOwner) => void;
}) {
    const { t, locale } = useTranslate();

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="border-b p-4">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Users className="h-4 w-4" aria-hidden />
                    {t('Owner recap')}
                </CardTitle>
                <CardDescription>
                    {t(
                        'Who owns the projects and tasks, and how much time is left. Click for details.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="p-0">
                {isLoading ? (
                    <div className="space-y-2 p-4">
                        <Skeleton className="h-12 w-full" />
                        <Skeleton className="h-12 w-full" />
                    </div>
                ) : owners.length === 0 ? (
                    <p className="text-muted-foreground p-4 text-sm">
                        {t('No owners yet.')}
                    </p>
                ) : (
                    <ul className="divide-y">
                        {owners.map((owner) => {
                            const late =
                                owner.overdue_tasks + owner.overdue_projects;
                            return (
                                <li key={owner.id}>
                                    <button
                                        type="button"
                                        onClick={() => onSelect(owner)}
                                        className="hover:bg-muted/50 focus-visible:bg-muted/50 flex w-full items-center gap-3 px-4 py-3 text-left outline-none"
                                    >
                                        <span className="bg-muted text-foreground flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
                                            {initials(owner.name)}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm font-medium">
                                                {owner.name}
                                            </span>
                                            <span className="text-muted-foreground block text-xs">
                                                {t(
                                                    ':projects projects · :tasks open tasks',
                                                    {
                                                        projects:
                                                            owner.projects_owned,
                                                        tasks: owner.open_tasks,
                                                    },
                                                )}
                                            </span>
                                            {owner.next_deadline && (
                                                <span className="text-muted-foreground block text-[11px]">
                                                    {t('Next deadline :date', {
                                                        date: formatDisplayDate(
                                                            owner.next_deadline,
                                                            locale,
                                                        ),
                                                    })}
                                                </span>
                                            )}
                                        </span>
                                        <span className="flex shrink-0 flex-col items-end gap-1">
                                            {late > 0 && (
                                                <span className="inline-flex items-center gap-1 rounded-md border border-red-500/30 bg-red-500/10 px-1.5 py-0.5 text-[11px] font-medium text-red-700 dark:text-red-300">
                                                    <AlertOctagon
                                                        className="h-3 w-3"
                                                        aria-hidden
                                                    />
                                                    {t(':count late', {
                                                        count: late,
                                                    })}
                                                </span>
                                            )}
                                            {owner.next_deadline && (
                                                <RemainingDaysBadge
                                                    days={owner.remaining_days}
                                                />
                                            )}
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}

function WorkTypeSummary({
    rows,
    isLoading,
}: {
    rows: ReportData['work_types'];
    isLoading: boolean;
}) {
    const { t } = useTranslate();

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="border-b p-4">
                <CardTitle className="text-base">
                    {t('Tasks by work type')}
                </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
                {isLoading ? (
                    <Skeleton className="m-4 h-24" />
                ) : (
                    <table className="w-full text-xs">
                        <thead className="text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    {t('Work Type')}
                                </th>
                                <th className="px-2 py-2 text-right font-medium">
                                    {t('Open')}
                                </th>
                                <th className="px-2 py-2 text-right font-medium">
                                    {t('Overdue')}
                                </th>
                                <th className="px-4 py-2 text-right font-medium">
                                    {t('Done')}
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {rows.map((row) => (
                                <tr key={row.type}>
                                    <td className="px-4 py-2">
                                        <WorkTypeBadge value={row.type} />
                                    </td>
                                    <td className="px-2 py-2 text-right tabular-nums">
                                        {row.open}
                                    </td>
                                    <td
                                        className={cn(
                                            'px-2 py-2 text-right tabular-nums',
                                            row.overdue > 0 &&
                                                'font-medium text-red-700 dark:text-red-300',
                                        )}
                                    >
                                        {row.overdue}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {row.done}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </CardContent>
        </Card>
    );
}

function OwnerDetailSheet({
    owner,
    onClose,
}: {
    owner: ReportOwner | null;
    onClose: () => void;
}) {
    const { t, locale } = useTranslate();

    const { data, isLoading } = useQuery<OwnerDetail>({
        queryKey: ['reports', 'owners', owner?.id],
        queryFn: async () => {
            const { data } = await axios.get(
                `/api/reports/owners/${owner?.id}`,
            );
            return data.data;
        },
        enabled: owner !== null,
    });

    return (
        <Sheet
            open={owner !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <SheetContent className="w-full overflow-y-auto sm:max-w-xl">
                <SheetHeader>
                    <SheetTitle className="flex items-center gap-2">
                        <UserCircle2 className="h-5 w-5" aria-hidden />
                        {owner?.name}
                    </SheetTitle>
                    <SheetDescription>{owner?.email}</SheetDescription>
                </SheetHeader>

                {isLoading || !data ? (
                    <div className="space-y-3 px-4">
                        <Skeleton className="h-16 w-full" />
                        <Skeleton className="h-32 w-full" />
                    </div>
                ) : (
                    <div className="space-y-6 px-4 pb-6">
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            {[
                                {
                                    label: t('Projects owned'),
                                    value: data.summary.projects_owned,
                                },
                                {
                                    label: t('Open tasks'),
                                    value: data.summary.open_tasks,
                                },
                                {
                                    label: t('Overdue'),
                                    value: data.summary.overdue_tasks,
                                },
                                {
                                    label: t('Done'),
                                    value: data.summary.done_tasks,
                                },
                            ].map((item) => (
                                <div
                                    key={item.label}
                                    className="rounded-md border p-3"
                                >
                                    <p className="text-muted-foreground text-[11px]">
                                        {item.label}
                                    </p>
                                    <p className="text-lg font-semibold tabular-nums">
                                        {item.value}
                                    </p>
                                </div>
                            ))}
                        </div>

                        <section className="space-y-2">
                            <h3 className="text-sm font-semibold">
                                {t('Projects owned')}
                            </h3>
                            {data.projects.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {t('Not the owner of any project.')}
                                </p>
                            ) : (
                                data.projects.map((project) => (
                                    <button
                                        key={project.id}
                                        type="button"
                                        onClick={() =>
                                            router.visit(
                                                `/projects/${project.id}`,
                                            )
                                        }
                                        className="hover:bg-muted/40 w-full space-y-1.5 rounded-md border p-3 text-left"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    <span className="text-muted-foreground mr-1.5 font-mono text-[11px]">
                                                        {project.key}
                                                    </span>
                                                    {project.name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {project.workspace.name}
                                                </p>
                                            </div>
                                            <RemainingDaysBadge
                                                days={project.remaining_days}
                                                done={
                                                    project.total_issues > 0 &&
                                                    project.done_issues ===
                                                        project.total_issues
                                                }
                                            />
                                        </div>
                                        <p className="text-muted-foreground text-xs">
                                            {formatDisplayDate(
                                                project.start_date,
                                                locale,
                                            )}{' '}
                                            -{' '}
                                            {formatDisplayDate(
                                                project.end_date,
                                                locale,
                                            )}
                                            {project.duration_days
                                                ? ` · ${t(':days days', { days: project.duration_days })}`
                                                : ''}
                                        </p>
                                        <div className="flex items-center gap-2">
                                            <Progress
                                                value={project.progress}
                                                className="h-1.5 flex-1"
                                            />
                                            <span className="text-xs tabular-nums">
                                                {project.progress}%
                                            </span>
                                        </div>
                                        {project.description && (
                                            <p className="text-muted-foreground line-clamp-3 text-xs whitespace-pre-line">
                                                <ReferenceText
                                                    text={project.description}
                                                />
                                            </p>
                                        )}
                                    </button>
                                ))
                            )}
                        </section>

                        <section className="space-y-2">
                            <h3 className="text-sm font-semibold">
                                {t('Open tasks')}
                            </h3>
                            {data.tasks.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {t('No open tasks.')}
                                </p>
                            ) : (
                                data.tasks.map((task) => (
                                    <button
                                        key={task.id}
                                        type="button"
                                        onClick={() =>
                                            router.visit(
                                                `/projects/${task.project.id}?issue=${task.id}`,
                                            )
                                        }
                                        className="hover:bg-muted/40 w-full space-y-1.5 rounded-md border p-3 text-left"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="min-w-0 text-sm font-medium">
                                                <span className="text-muted-foreground mr-1.5 font-mono text-[11px]">
                                                    {task.key}
                                                </span>
                                                {task.title}
                                            </p>
                                            <RemainingDaysBadge
                                                days={task.remaining_days}
                                            />
                                        </div>
                                        <div className="text-muted-foreground flex flex-wrap items-center gap-1.5 text-xs">
                                            <WorkTypeBadge
                                                value={task.work_type}
                                            />
                                            <span>{task.project.name}</span>
                                            <span>·</span>
                                            <span>
                                                {task.status.name ?? '-'}
                                            </span>
                                            {task.due_date && (
                                                <>
                                                    <span>·</span>
                                                    <span>
                                                        {t('Due :date', {
                                                            date: formatDisplayDate(
                                                                task.due_date,
                                                                locale,
                                                            ),
                                                        })}
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                        {task.description && (
                                            <p className="text-muted-foreground line-clamp-3 text-xs whitespace-pre-line">
                                                <ReferenceText
                                                    text={task.description}
                                                />
                                            </p>
                                        )}
                                    </button>
                                ))
                            )}
                        </section>
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}

ProjectReport.layout = {
    breadcrumbs: [
        {
            title: 'Project Report',
            href: '/reports/projects',
        },
    ],
};
