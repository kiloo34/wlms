<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\Services\WorkSchedule;
use App\Modules\Workload\Domain\Enums\WorkType;
use App\Modules\Workload\Infrastructure\Config\ReminderSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Detail pekerjaan satu owner: project yang ia pegang dan tugas yang belum selesai.
 * Hanya mencakup project yang boleh dilihat oleh user yang meminta (viewer).
 */
final class GetOwnerWorkQuery
{
    /**
     * @return array<string, mixed>|null
     */
    public function execute(
        int|string $viewerId,
        ?string $viewerOrgUnitId,
        int $ownerId,
        ?CarbonImmutable $today = null,
    ): ?array {
        $owner = DB::table('users')->where('id', $ownerId)->first(['id', 'name', 'email', 'avatar_url', 'employee_id']);
        if ($owner === null) {
            return null;
        }

        $today = ($today ?? CarbonImmutable::now(ReminderSettings::timezone()))->startOfDay();
        $todayStr = $today->toDateString();
        $accessible = AccessibleProjectsQuery::for($viewerId, $viewerOrgUnitId)->select('projects.id');

        $projectRows = DB::table('projects')
            ->leftJoin('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
            ->whereIn('projects.id', $accessible->toBase())
            ->where('projects.lead_id', $ownerId)
            ->where('projects.status', '!=', 'ARCHIVED')
            ->whereNull('projects.deleted_at')
            ->orderByRaw('CASE WHEN projects.end_date IS NULL THEN 1 ELSE 0 END, projects.end_date ASC')
            ->get([
                'projects.id', 'projects.key', 'projects.name', 'projects.description', 'projects.status',
                'projects.start_date', 'projects.end_date', 'projects.workspace_id', 'workspaces.name as workspace_name',
            ]);

        $projectStats = [];
        if ($projectRows->isNotEmpty()) {
            $projectStats = DB::table('issues')
                ->join('statuses', 'statuses.id', '=', 'issues.status_id')
                ->whereIn('issues.project_id', $projectRows->pluck('id')->all())
                ->whereNull('issues.deleted_at')
                ->groupBy('issues.project_id')
                ->selectRaw("issues.project_id, COUNT(*) as total, SUM(CASE WHEN statuses.category = 'DONE' THEN 1 ELSE 0 END) as done")
                ->get()
                ->keyBy('project_id')
                ->all();
        }

        $projects = $projectRows->map(function ($row) use ($projectStats, $today): array {
            $stats = $projectStats[(string) $row->id] ?? null;
            $total = $stats !== null ? (int) $stats->total : 0;
            $done = $stats !== null ? (int) $stats->done : 0;
            $start = WorkSchedule::toDateString($row->start_date !== null ? (string) $row->start_date : null);
            $end = WorkSchedule::toDateString($row->end_date !== null ? (string) $row->end_date : null);

            return [
                'id' => (string) $row->id,
                'key' => (string) $row->key,
                'name' => (string) $row->name,
                'description' => $row->description !== null ? (string) $row->description : null,
                'workspace' => ['id' => (string) $row->workspace_id, 'name' => $row->workspace_name],
                'start_date' => $start,
                'end_date' => $end,
                'duration_days' => WorkSchedule::durationDays($start, $end),
                'remaining_days' => WorkSchedule::remainingDays($end, $today),
                'total_issues' => $total,
                'done_issues' => $done,
                'progress' => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        })->values()->all();

        $issueRows = DB::table('issues')
            ->join('projects', 'projects.id', '=', 'issues.project_id')
            ->join('statuses', 'statuses.id', '=', 'issues.status_id')
            ->leftJoin('priorities', 'priorities.id', '=', 'issues.priority_id')
            ->whereIn('issues.project_id', $accessible->toBase())
            ->where('issues.assignee_id', $ownerId)
            ->whereNull('issues.deleted_at')
            ->where('statuses.category', '!=', 'DONE')
            ->orderByRaw('CASE WHEN issues.due_date IS NULL THEN 1 ELSE 0 END, issues.due_date ASC, issues.created_at DESC')
            ->limit(200)
            ->get([
                'issues.id', 'issues.number', 'issues.title', 'issues.description', 'issues.work_type',
                'issues.start_date', 'issues.due_date', 'issues.project_id',
                'projects.key as project_key', 'projects.name as project_name', 'projects.workspace_id',
                'statuses.name as status_name', 'statuses.category as status_category', 'statuses.color as status_color',
                'priorities.name as priority_name', 'priorities.color as priority_color',
            ]);

        $tasks = $issueRows->map(function ($row) use ($today, $todayStr): array {
            $start = WorkSchedule::toDateString($row->start_date !== null ? (string) $row->start_date : null);
            $due = WorkSchedule::toDateString($row->due_date !== null ? (string) $row->due_date : null);

            return [
                'id' => (string) $row->id,
                'key' => $row->project_key.'-'.$row->number,
                'title' => (string) $row->title,
                'description' => $row->description !== null ? (string) $row->description : null,
                'work_type' => (string) ($row->work_type ?: WorkType::DEFAULT->value),
                'work_type_label' => WorkType::labelFor($row->work_type),
                'start_date' => $start,
                'due_date' => $due,
                'duration_days' => WorkSchedule::durationDays($start, $due),
                'remaining_days' => WorkSchedule::remainingDays($due, $today),
                'is_overdue' => $due !== null && $due < $todayStr,
                'project' => [
                    'id' => (string) $row->project_id,
                    'key' => (string) $row->project_key,
                    'name' => (string) $row->project_name,
                    'workspace_id' => (string) $row->workspace_id,
                ],
                'status' => ['name' => $row->status_name, 'category' => $row->status_category, 'color' => $row->status_color],
                'priority' => ['name' => $row->priority_name, 'color' => $row->priority_color],
            ];
        })->values()->all();

        $doneCount = DB::table('issues')
            ->join('statuses', 'statuses.id', '=', 'issues.status_id')
            ->whereIn('issues.project_id', $accessible->toBase())
            ->where('issues.assignee_id', $ownerId)
            ->whereNull('issues.deleted_at')
            ->where('statuses.category', 'DONE')
            ->count();

        return [
            'owner' => [
                'id' => (int) $owner->id,
                'name' => (string) $owner->name,
                'email' => (string) $owner->email,
                'employee_id' => $owner->employee_id !== null ? (string) $owner->employee_id : null,
                'avatar_url' => $owner->avatar_url !== null ? (string) $owner->avatar_url : null,
            ],
            'today' => $todayStr,
            'projects' => $projects,
            'tasks' => $tasks,
            'summary' => [
                'projects_owned' => count($projects),
                'open_tasks' => count($tasks),
                'done_tasks' => $doneCount,
                'overdue_tasks' => count(array_filter($tasks, fn (array $t): bool => $t['is_overdue'])),
            ],
        ];
    }
}
