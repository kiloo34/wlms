import React, { useState } from 'react';
import { useWorkspaceAnalytics } from '@/hooks/useWorkspaceAnalytics';
import { DateRangeFilter, DateRangePreset } from '@/components/DateRangeFilter';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart } from '@/components/charts/BarChart';
import { ChartCardSkeleton } from '@/components/charts/ChartCardSkeleton';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { useTranslate } from '@/hooks/useTranslate';
import { useIconSize } from '@/hooks/use-appearance';
import { 
    FolderKanban, 
    CheckCircle2, 
    Clock, 
    TrendingUp, 
    Users, 
    AlertCircle, 
    Activity 
} from 'lucide-react';
import { cn } from '@/lib/utils';

export interface WorkspaceAnalyticsViewProps {
    workspaceId: string;
    className?: string;
}

export function WorkspaceAnalyticsView({ workspaceId, className }: WorkspaceAnalyticsViewProps) {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const [dateRange, setDateRange] = useState<DateRangePreset>('30d');
    const [customFrom, setCustomFrom] = useState<string | undefined>();
    const [customTo, setCustomTo] = useState<string | undefined>();

    const { data, isLoading, error } = useWorkspaceAnalytics({
        workspaceId,
        dateRange,
        from: customFrom,
        to: customTo,
    });

    const handleDateRangeChange = (params: {
        dateRange: DateRangePreset;
        from?: string;
        to?: string;
    }) => {
        setDateRange(params.dateRange);
        setCustomFrom(params.from);
        setCustomTo(params.to);
    };

    const cardPadding =
        iconSize === 'sm' ? 'p-3' : iconSize === 'lg' ? 'p-5' : 'p-4';

    const kpiValSize =
        iconSize === 'sm' ? 'text-xl' : iconSize === 'lg' ? 'text-3xl' : 'text-2xl';

    if (isLoading) {
        return (
            <div className={cn('space-y-6', className)}>
                <div className="flex items-center justify-between gap-4">
                    <div className="h-8 w-48 bg-muted animate-pulse rounded" />
                    <div className="h-8 w-64 bg-muted animate-pulse rounded" />
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {[1, 2, 3, 4].map((i) => (
                        <div key={i} className="h-24 bg-card border rounded-xl animate-pulse" />
                    ))}
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <ChartCardSkeleton height={260} />
                    <ChartCardSkeleton height={260} />
                </div>
            </div>
        );
    }

    if (error || !data) {
        return (
            <Card className="p-8 text-center">
                <AlertCircle className="h-10 w-10 text-destructive mx-auto mb-3" />
                <h3 className="text-base font-semibold">{t('Failed to load analytics')}</h3>
                <p className="text-sm text-muted-foreground mt-1">
                    {t('Could not retrieve analytics data for this workspace.')}
                </p>
            </Card>
        );
    }

    const { summary, projects, throughput, member_workload } = data;

    const getHealthBadge = (health: string) => {
        switch (health) {
            case 'completed':
                return <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30">{t('Completed')}</Badge>;
            case 'on_track':
                return <Badge className="bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30">{t('On Track')}</Badge>;
            case 'delayed':
                return <Badge className="bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30">{t('Delayed')}</Badge>;
            case 'at_risk':
                return <Badge className="bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30">{t('At Risk')}</Badge>;
            default:
                return <Badge variant="outline">{health}</Badge>;
        }
    };

    return (
        <div className={cn('space-y-6', className)}>
            {/* Header Toolbar */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-lg font-bold tracking-tight">{t('Workspace Macro Analytics')}</h2>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        {t('Portfolio throughput, cross-project workload, and health performance.')}
                    </p>
                </div>
                <DateRangeFilter
                    dateRange={dateRange}
                    from={customFrom}
                    to={customTo}
                    onChange={handleDateRangeChange}
                />
            </div>

            {/* KPI Summary Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('Active Projects')}</span>
                        <FolderKanban className="h-4 w-4 text-blue-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold', kpiValSize)}>{summary.active_projects}</span>
                        <span className="text-xs text-muted-foreground">/ {summary.total_projects} {t('total')}</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('Across workspace portfolio')}
                    </p>
                </Card>

                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('Completion Rate')}</span>
                        <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold', kpiValSize)}>{summary.completion_rate}%</span>
                        <span className="text-xs text-muted-foreground">({summary.completed_issues}/{summary.total_issues})</span>
                    </div>
                    <div className="mt-2">
                        <Progress value={summary.completion_rate} className="h-1.5" />
                    </div>
                </Card>

                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('Estimated Hours')}</span>
                        <Clock className="h-4 w-4 text-purple-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold', kpiValSize)}>{summary.total_estimated_hours}h</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('Original estimated workload')}
                    </p>
                </Card>

                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('Logged Hours')}</span>
                        <TrendingUp className="h-4 w-4 text-amber-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold', kpiValSize)}>{summary.total_logged_hours}h</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('Actual time recorded in worklogs')}
                    </p>
                </Card>
            </div>

            {/* Middle Grid: Throughput + Projects Health */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {/* Portfolio Throughput */}
                <Card className="lg:col-span-7 flex flex-col">
                    <CardHeader className="pb-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold">{t('Portfolio Throughput')}</CardTitle>
                                <CardDescription className="text-xs">
                                    {t('Completed tasks delivered over time.')}
                                </CardDescription>
                            </div>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent className="pt-2 flex-1">
                        <BarChart
                            data={throughput}
                            xKey="period"
                            dataKey="completed_count"
                            barColor="var(--chart-1)"
                            yAxisLabel={t('Tasks')}
                            emptyTitle={t('No Throughput Data')}
                            emptyMessage={t('No tasks completed within the selected period.')}
                        />
                    </CardContent>
                </Card>

                {/* Project Progress & Health */}
                <Card className="lg:col-span-5 flex flex-col">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-semibold">{t('Project Progress & Health')}</CardTitle>
                        <CardDescription className="text-xs">
                            {t('Status and issue progress per project.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-2 flex-1">
                        {projects.length === 0 ? (
                            <div className="flex flex-col items-center justify-center h-48 text-muted-foreground text-xs">
                                {t('No projects in this workspace')}
                            </div>
                        ) : (
                            <div className="space-y-3.5 max-h-[320px] overflow-y-auto pr-1">
                                {projects.map((proj) => (
                                    <div key={proj.id} className="p-2.5 rounded-lg border bg-card/60 space-y-2">
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="min-w-0">
                                                <span className="font-semibold text-xs text-foreground block truncate">
                                                    {proj.name}
                                                </span>
                                                <span className="text-[10px] text-muted-foreground font-mono">
                                                    {proj.key}
                                                </span>
                                            </div>
                                            <div className="shrink-0">
                                                {getHealthBadge(proj.health_status)}
                                            </div>
                                        </div>
                                        <div className="space-y-1">
                                            <div className="flex justify-between text-[11px] text-muted-foreground">
                                                <span>{proj.completed_issues} / {proj.total_issues} {t('Done')}</span>
                                                <span className="font-medium text-foreground">{proj.progress_percent}%</span>
                                            </div>
                                            <Progress value={proj.progress_percent} className="h-1.5" />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Bottom Section: Cross-Project Member Workload */}
            <Card>
                <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                        <div>
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Users className="h-4 w-4 text-primary" />
                                <span>{t('Cross-Project Member Workload')}</span>
                            </CardTitle>
                            <CardDescription className="text-xs">
                                {t('Task volume, hours distribution, and capacity utilization.')}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {member_workload.length === 0 ? (
                        <div className="text-center py-8 text-xs text-muted-foreground">
                            {t('No team members assigned in this workspace.')}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-xs text-left">
                                <thead className="text-[11px] uppercase bg-muted/50 text-muted-foreground border-b">
                                    <tr>
                                        <th className="py-2.5 px-3 font-semibold">{t('Team Member')}</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">{t('Tasks Assigned')}</th>
                                        <th className="py-2.5 px-3 font-semibold text-center">{t('Tasks Done')}</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">{t('Estimated')}</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">{t('Logged')}</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">{t('Capacity')}</th>
                                        <th className="py-2.5 px-3 font-semibold text-right">{t('Utilization')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60">
                                    {member_workload.map((m) => {
                                        const isOverloaded = m.utilization_rate > 100;
                                        return (
                                            <tr key={String(m.user_id)} className="hover:bg-muted/30 transition-colors">
                                                <td className="py-2.5 px-3 font-medium text-foreground">
                                                    <div>{m.name}</div>
                                                    <div className="text-[10px] text-muted-foreground">{m.email}</div>
                                                </td>
                                                <td className="py-2.5 px-3 text-center font-mono font-medium">{m.task_count}</td>
                                                <td className="py-2.5 px-3 text-center font-mono text-emerald-600 dark:text-emerald-400 font-medium">
                                                    {m.completed_task_count}
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono text-muted-foreground">{m.estimated_hours}h</td>
                                                <td className="py-2.5 px-3 text-right font-mono font-semibold text-foreground">{m.logged_hours}h</td>
                                                <td className="py-2.5 px-3 text-right font-mono text-muted-foreground">{m.capacity_hours}h</td>
                                                <td className="py-2.5 px-3 text-right">
                                                    <span className={cn(
                                                        'inline-flex items-center px-2 py-0.5 rounded font-mono text-[11px] font-semibold',
                                                        isOverloaded
                                                            ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400'
                                                            : m.utilization_rate >= 75
                                                            ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                                            : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                    )}>
                                                        {m.utilization_rate}%
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

