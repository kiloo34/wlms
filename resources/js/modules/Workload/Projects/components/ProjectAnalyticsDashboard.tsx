import React, { useState } from 'react';
import { useProjectAnalytics } from '@/hooks/useProjectAnalytics';
import { DateRangeFilter, DateRangePreset } from '@/components/DateRangeFilter';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CumulativeFlowChart } from '@/components/charts/CumulativeFlowChart';
import { LeadTimeHistogram } from '@/components/charts/LeadTimeHistogram';
import { DonutDistributionChart } from '@/components/charts/DonutDistributionChart';
import { BurndownVelocityChart } from '@/components/charts/BurndownVelocityChart';
import { ChartCardSkeleton } from '@/components/charts/ChartCardSkeleton';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslate } from '@/hooks/useTranslate';
import { useIconSize } from '@/hooks/use-appearance';
import { 
    CheckCircle2, 
    Clock, 
    Layers, 
    AlertCircle, 
    Flame, 
    Sparkles, 
    ListFilter 
} from 'lucide-react';
import { cn } from '@/lib/utils';

export interface ProjectAnalyticsDashboardProps {
    projectId: string;
    className?: string;
}

export function ProjectAnalyticsDashboard({
    projectId,
    className,
}: ProjectAnalyticsDashboardProps) {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();

    const [dateRange, setDateRange] = useState<DateRangePreset>('30d');
    const [customFrom, setCustomFrom] = useState<string | undefined>();
    const [customTo, setCustomTo] = useState<string | undefined>();
    const [selectedSprintId, setSelectedSprintId] = useState<string | undefined>();
    const [timeMetricMode, setTimeMetricMode] = useState<'lead' | 'cycle'>('lead');
    const [breakdownMode, setBreakdownMode] = useState<'type' | 'priority'>('type');

    const { data, isLoading, error } = useProjectAnalytics({
        projectId,
        dateRange,
        from: customFrom,
        to: customTo,
        sprintId: selectedSprintId,
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

    const toggleBtnSize =
        iconSize === 'sm' ? 'h-6 text-[11px] px-2' : iconSize === 'lg' ? 'h-8 text-xs px-3' : 'h-7 text-xs px-2.5';

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
                    <ChartCardSkeleton height={280} />
                    <ChartCardSkeleton height={280} />
                </div>
            </div>
        );
    }

    if (error || !data) {
        return (
            <Card className="p-8 text-center">
                <AlertCircle className="h-10 w-10 text-destructive mx-auto mb-3" />
                <h3 className="text-base font-semibold">{t('Failed to load project analytics')}</h3>
                <p className="text-sm text-muted-foreground mt-1">
                    {t('Could not retrieve analytics metrics for this project.')}
                </p>
            </Card>
        );
    }

    const { summary, lead_time, cycle_time, cumulative_flow, issue_types, priorities, sprint_metrics } = data;

    const timeMetrics = timeMetricMode === 'lead' ? lead_time : cycle_time;

    const donutData = breakdownMode === 'type'
        ? issue_types.map((it) => ({
            label: it.name,
            value: it.count,
            color: it.color || 'var(--chart-1)',
        }))
        : priorities.map((p) => ({
            label: p.name,
            value: p.count,
            color: p.color || 'var(--chart-2)',
        }));

    const burndownFormatted = sprint_metrics.burndown.map((b) => ({
        day: b.day,
        ideal_points: b.ideal_points,
        actual_points: b.actual_points ?? 0,
    }));

    const velocityFormatted = sprint_metrics.velocity.map((v) => ({
        sprint_name: v.sprint_name,
        completed_points: v.completed_points,
        target_points: v.committed_points,
    }));

    return (
        <div className={cn('space-y-6', className)}>
            {/* Header Toolbar */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-lg font-bold tracking-tight">{t('Project Deep-Dive Analytics')}</h2>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        {t('Cycle time, cumulative flow, distribution, and sprint burndown/velocity.')}
                    </p>
                </div>

                <div className="flex items-center gap-2.5 flex-wrap">
                    {sprint_metrics.has_sprints && sprint_metrics.velocity.length > 0 && (
                        <Select
                            value={selectedSprintId || 'all'}
                            onValueChange={(val) => setSelectedSprintId(val === 'all' ? undefined : val)}
                        >
                            <SelectTrigger className="h-8 text-xs w-40">
                                <SelectValue placeholder={t('All Sprints')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('Active / Latest Sprint')}</SelectItem>
                                {sprint_metrics.velocity.map((v) => (
                                    <SelectItem key={v.sprint_id} value={v.sprint_id}>
                                        {v.sprint_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}

                    <DateRangeFilter
                        dateRange={dateRange}
                        from={customFrom}
                        to={customTo}
                        onChange={handleDateRangeChange}
                    />
                </div>
            </div>

            {/* KPI Summary Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('Total Issues')}</span>
                        <Layers className="h-4 w-4 text-blue-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold', kpiValSize)}>{summary.total_issues}</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('All issues in this project')}
                    </p>
                </Card>

                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('Completed')}</span>
                        <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold text-emerald-600 dark:text-emerald-400', kpiValSize)}>
                            {summary.completed_issues}
                        </span>
                        <span className="text-xs text-muted-foreground font-mono">({summary.completion_rate}%)</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('Delivered successfully')}
                    </p>
                </Card>

                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('In Progress')}</span>
                        <Flame className="h-4 w-4 text-amber-500" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold text-amber-600 dark:text-amber-400', kpiValSize)}>
                            {summary.in_progress_issues}
                        </span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('Currently active in workflow')}
                    </p>
                </Card>

                <Card className={cardPadding}>
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">{t('To Do Backlog')}</span>
                        <Clock className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={cn('font-bold', kpiValSize)}>{summary.todo_issues}</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">
                        {t('Waiting to be started')}
                    </p>
                </Card>
            </div>

            {/* Row 2: Delivery Speed Histogram & Cumulative Flow */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {/* Lead / Cycle Time */}
                <Card className="lg:col-span-6 flex flex-col">
                    <CardHeader className="pb-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold">
                                    {timeMetricMode === 'lead' ? t('Lead Time Distribution') : t('Cycle Time Distribution')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {timeMetricMode === 'lead'
                                        ? t('Time from issue creation to completion.')
                                        : t('Time from active work start to completion.')}
                                </CardDescription>
                            </div>
                            <div className="flex items-center rounded-lg border bg-muted/40 p-0.5">
                                <Button
                                    variant={timeMetricMode === 'lead' ? 'default' : 'ghost'}
                                    size="sm"
                                    className={cn(toggleBtnSize, 'rounded-md')}
                                    onClick={() => setTimeMetricMode('lead')}
                                >
                                    {t('Lead Time')}
                                </Button>
                                <Button
                                    variant={timeMetricMode === 'cycle' ? 'default' : 'ghost'}
                                    size="sm"
                                    className={cn(toggleBtnSize, 'rounded-md')}
                                    onClick={() => setTimeMetricMode('cycle')}
                                >
                                    {t('Cycle Time')}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-2 flex-1">
                        <LeadTimeHistogram
                            data={timeMetrics.distribution}
                            averageDays={timeMetrics.average_days}
                            medianDays={timeMetrics.median_days}
                            p85Days={timeMetrics.p85_days}
                            metricLabel={timeMetricMode === 'lead' ? t('Lead Time') : t('Cycle Time')}
                            barColor={timeMetricMode === 'lead' ? 'var(--chart-1)' : 'var(--chart-2)'}
                            emptyTitle={t('No Delivery Time Data')}
                            emptyMessage={t('Complete issues to see lead time & cycle time statistics.')}
                        />
                    </CardContent>
                </Card>

                {/* Cumulative Flow Diagram */}
                <Card className="lg:col-span-6 flex flex-col">
                    <CardHeader className="pb-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold">{t('Cumulative Flow (CFD)')}</CardTitle>
                                <CardDescription className="text-xs">
                                    {t('Work in progress bottlenecks and flow stability over time.')}
                                </CardDescription>
                            </div>
                            <Sparkles className="h-4 w-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent className="pt-2 flex-1">
                        <CumulativeFlowChart
                            data={cumulative_flow}
                            xKey="date"
                            emptyTitle={t('No Flow Data')}
                            emptyMessage={t('No issues found in this date range.')}
                        />
                    </CardContent>
                </Card>
            </div>

            {/* Row 3: Breakdowns & Sprint Velocity */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {/* Distribution Breakdown (Type / Priority) */}
                <Card className="lg:col-span-6 flex flex-col">
                    <CardHeader className="pb-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold">
                                    {breakdownMode === 'type' ? t('Issue Types Breakdown') : t('Priority Breakdown')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {t('Proportional breakdown of project issues.')}
                                </CardDescription>
                            </div>
                            <div className="flex items-center rounded-lg border bg-muted/40 p-0.5">
                                <Button
                                    variant={breakdownMode === 'type' ? 'default' : 'ghost'}
                                    size="sm"
                                    className={cn(toggleBtnSize, 'rounded-md')}
                                    onClick={() => setBreakdownMode('type')}
                                >
                                    {t('Types')}
                                </Button>
                                <Button
                                    variant={breakdownMode === 'priority' ? 'default' : 'ghost'}
                                    size="sm"
                                    className={cn(toggleBtnSize, 'rounded-md')}
                                    onClick={() => setBreakdownMode('priority')}
                                >
                                    {t('Priorities')}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-2 flex-1">
                        <DonutDistributionChart
                            data={donutData}
                            centerLabel={t('Total')}
                            centerValue={summary.total_issues}
                            emptyTitle={t('No Breakdown Data')}
                            emptyMessage={t('No issues available to classify.')}
                        />
                    </CardContent>
                </Card>

                {/* Sprint Burndown & Velocity */}
                <Card className="lg:col-span-6 flex flex-col">
                    <CardHeader className="pb-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold">
                                    {sprint_metrics.has_sprints ? t('Sprint Burndown & Velocity') : t('Throughput Metrics')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {sprint_metrics.has_sprints
                                        ? t('Ideal vs actual story points burndown and sprint velocity.')
                                        : t('Continuous delivery metrics for kanban workflows.')}
                                </CardDescription>
                            </div>
                            <ListFilter className="h-4 w-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent className="pt-2 flex-1">
                        {sprint_metrics.has_sprints ? (
                            <BurndownVelocityChart
                                burndownData={burndownFormatted}
                                velocityData={velocityFormatted}
                                emptyTitle={t('No Sprint Metrics')}
                                emptyMessage={t('No sprint history available for this project.')}
                            />
                        ) : (
                            <div className="flex flex-col items-center justify-center h-56 text-center text-muted-foreground p-6">
                                <CheckCircle2 className="h-8 w-8 text-primary/40 mb-2" />
                                <p className="text-sm font-semibold text-foreground">{t('Continuous Kanban Flow')}</p>
                                <p className="text-xs text-muted-foreground max-w-sm mt-1">
                                    {t('This project uses continuous flow. Track completion rates and lead times above for team velocity.')}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

