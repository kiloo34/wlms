<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\Services\WorkSchedule;
use App\Modules\Workload\Domain\Enums\WorkType;
use App\Modules\Workload\Infrastructure\Config\ReminderSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Laporan seluruh project (satu layar) + rekap owner.
 *
 * Semua data dibatasi ke project yang boleh dilihat user (AccessibleProjectsQuery).
 */
final class GetProjectReportQuery
{
    /**
     * @return array<string, mixed>
     */
    public function execute(
        int|string $userId,
        ?string $orgUnitId = null,
        ?string $workspaceId = null,
        bool $includeArchived = false,
        ?CarbonImmutable $today = null,
    ): array {
        $today = ($today ?? CarbonImmutable::now(ReminderSettings::timezone()))->startOfDay();
        $todayStr = $today->toDateString();

        $projectsQuery = AccessibleProjectsQuery::for($userId, $orgUnitId)
            ->leftJoin('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'projects.lead_id')
            ->select([
                'projects.id',
                'projects.key',
                'projects.name',
                'projects.description',
                'projects.status',
                'projects.workspace_id',
                'projects.lead_id',
                'projects.start_date',
                'projects.end_date',
                'workspaces.name as workspace_name',
                'owners.name as owner_name',
            ])
            ->orderBy('projects.name');

        if (! $includeArchived) {
            $projectsQuery->where('projects.status', '!=', 'ARCHIVED');
        }
        if ($workspaceId !== null && $workspaceId !== '') {
            $projectsQuery->where('projects.workspace_id', $workspaceId);
        }

        $projectRows = $projectsQuery->toBase()->get();
        $projectIds = $projectRows->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $statsByProject = [];
        $workTypeTotals = [];
        foreach (WorkType::cases() as $type) {
            $workTypeTotals[$type->value] = ['type' => $type->value, 'label' => $type->label(), 'total' => 0, 'open' => 0, 'done' => 0, 'overdue' => 0];
        }

        if ($projectIds !== []) {
            $issueStats = DB::table('issues')
                ->join('statuses', 'statuses.id', '=', 'issues.status_id')
                ->whereIn('issues.project_id', $projectIds)
                ->whereNull('issues.deleted_at')
                ->groupBy('issues.project_id', 'issues.work_type')
                ->selectRaw(
                    "issues.project_id, issues.work_type, COUNT(*) as total,
                    SUM(CASE WHEN statuses.category = 'DONE' THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN statuses.category != 'DONE' AND issues.due_date IS NOT NULL AND issues.due_date < ? THEN 1 ELSE 0 END) as overdue",
                    [$todayStr]
                )
                ->get();

            foreach ($issueStats as $row) {
                $pid = (string) $row->project_id;
                $type = WorkType::tryFrom((string) $row->work_type) ?? WorkType::DEFAULT;
                $total = (int) $row->total;
                $done = (int) $row->done;
                $overdue = (int) $row->overdue;

                $statsByProject[$pid] ??= ['total' => 0, 'done' => 0, 'overdue' => 0, 'work_types' => []];
                $statsByProject[$pid]['total'] += $total;
                $statsByProject[$pid]['done'] += $done;
                $statsByProject[$pid]['overdue'] += $overdue;
                $statsByProject[$pid]['work_types'][$type->value] = ($statsByProject[$pid]['work_types'][$type->value] ?? 0) + ($total - $done);

                $workTypeTotals[$type->value]['total'] += $total;
                $workTypeTotals[$type->value]['done'] += $done;
                $workTypeTotals[$type->value]['open'] += $total - $done;
                $workTypeTotals[$type->value]['overdue'] += $overdue;
            }
        }

        $projects = [];
        $summary = [
            'total_projects' => 0,
            'on_track' => 0,
            'at_risk' => 0,
            'overdue' => 0,
            'done' => 0,
            'no_schedule' => 0,
            'archived' => 0,
            'total_issues' => 0,
            'done_issues' => 0,
            'open_issues' => 0,
            'overdue_issues' => 0,
        ];

        foreach ($projectRows as $row) {
            $pid = (string) $row->id;
            $stats = $statsByProject[$pid] ?? ['total' => 0, 'done' => 0, 'overdue' => 0, 'work_types' => []];
            $startDate = WorkSchedule::toDateString($row->start_date !== null ? (string) $row->start_date : null);
            $endDate = WorkSchedule::toDateString($row->end_date !== null ? (string) $row->end_date : null);
            $progress = $stats['total'] > 0 ? (int) round($stats['done'] / $stats['total'] * 100) : 0;
            $remaining = WorkSchedule::remainingDays($endDate, $today);
            $duration = WorkSchedule::durationDays($startDate, $endDate);
            $elapsedPct = $this->elapsedPercent($startDate, $duration, $today);
            $health = $this->health((string) $row->status, $stats['total'], $stats['done'], $remaining, $progress, $elapsedPct);

            $projects[] = [
                'id' => $pid,
                'key' => (string) $row->key,
                'name' => (string) $row->name,
                'description' => $row->description !== null ? (string) $row->description : null,
                'status' => (string) $row->status,
                'workspace' => [
                    'id' => (string) $row->workspace_id,
                    'name' => $row->workspace_name !== null ? (string) $row->workspace_name : null,
                ],
                'owner' => $row->lead_id !== null ? [
                    'id' => (int) $row->lead_id,
                    'name' => $row->owner_name !== null ? (string) $row->owner_name : null,
                ] : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_days' => $duration,
                'remaining_days' => $remaining,
                'elapsed_percent' => $elapsedPct,
                'total_issues' => $stats['total'],
                'done_issues' => $stats['done'],
                'open_issues' => $stats['total'] - $stats['done'],
                'overdue_issues' => $stats['overdue'],
                'progress' => $progress,
                'health' => $health,
                'open_by_work_type' => (object) $stats['work_types'],
            ];

            $summary['total_projects']++;
            $summary[$health]++;
            $summary['total_issues'] += $stats['total'];
            $summary['done_issues'] += $stats['done'];
            $summary['open_issues'] += $stats['total'] - $stats['done'];
            $summary['overdue_issues'] += $stats['overdue'];
        }

        $summary['completion_rate'] = $summary['total_issues'] > 0
            ? (int) round($summary['done_issues'] / $summary['total_issues'] * 100)
            : 0;

        return [
            'generated_at' => CarbonImmutable::now(ReminderSettings::timezone())->toIso8601String(),
            'today' => $todayStr,
            'summary' => $summary,
            'projects' => $projects,
            'owners' => $this->owners($projectRows, $projectIds, $today),
            'work_types' => array_values($workTypeTotals),
        ];
    }

    /**
     * Rekap owner: owner project (lead) dan owner tugas (assignee issue yang belum selesai).
     *
     * @param  Collection<int, \stdClass>  $projectRows
     * @param  array<int, string>  $projectIds
     * @return list<array<string, mixed>>
     */
    private function owners(Collection $projectRows, array $projectIds, CarbonImmutable $today): array
    {
        $todayStr = $today->toDateString();
        $owners = [];

        foreach ($projectRows as $row) {
            if ($row->lead_id === null || (string) $row->status === 'ARCHIVED') {
                continue;
            }
            $id = (int) $row->lead_id;
            $owner = $owners[$id] ?? self::emptyOwner();
            $end = WorkSchedule::toDateString($row->end_date !== null ? (string) $row->end_date : null);

            $owner['projects_owned']++;
            if ($end !== null && $end < $todayStr) {
                $owner['overdue_projects']++;
            }
            $owner['next_deadline'] = self::earliestUpcoming($owner['next_deadline'], $end, $todayStr);
            $owners[$id] = $owner;
        }

        if ($projectIds !== []) {
            $taskRows = DB::table('issues')
                ->join('statuses', 'statuses.id', '=', 'issues.status_id')
                ->whereIn('issues.project_id', array_values($projectIds))
                ->whereNull('issues.deleted_at')
                ->whereNotNull('issues.assignee_id')
                ->where('statuses.category', '!=', 'DONE')
                ->groupBy('issues.assignee_id')
                ->selectRaw(
                    'issues.assignee_id, COUNT(*) as open_tasks,
                    SUM(CASE WHEN issues.due_date IS NOT NULL AND issues.due_date < ? THEN 1 ELSE 0 END) as overdue_tasks,
                    MIN(CASE WHEN issues.due_date >= ? THEN issues.due_date END) as next_due',
                    [$todayStr, $todayStr]
                )
                ->get();

            foreach ($taskRows as $row) {
                $id = (int) $row->assignee_id;
                $owner = $owners[$id] ?? self::emptyOwner();
                $owner['open_tasks'] = (int) $row->open_tasks;
                $owner['overdue_tasks'] = (int) $row->overdue_tasks;
                $nextDue = WorkSchedule::toDateString($row->next_due !== null ? (string) $row->next_due : null);
                $owner['next_deadline'] = self::earliestUpcoming($owner['next_deadline'], $nextDue, $todayStr);
                $owners[$id] = $owner;
            }
        }

        if ($owners === []) {
            return [];
        }

        $users = DB::table('users')
            ->whereIn('id', array_keys($owners))
            ->get(['id', 'name', 'email', 'avatar_url'])
            ->keyBy('id');

        $result = [];
        foreach ($owners as $id => $owner) {
            $user = $users->get($id);
            if ($user === null) {
                continue;
            }

            $result[] = [
                'id' => $id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'avatar_url' => $user->avatar_url !== null ? (string) $user->avatar_url : null,
                ...$owner,
                'remaining_days' => WorkSchedule::remainingDays($owner['next_deadline'], $today),
            ];
        }

        // Yang paling mendesak di atas: ada yang terlambat, lalu tenggat terdekat, lalu nama.
        usort($result, fn (array $a, array $b): int => self::urgencyKey($a) <=> self::urgencyKey($b));

        return $result;
    }

    /**
     * @return array{projects_owned: int, overdue_projects: int, open_tasks: int, overdue_tasks: int, next_deadline: ?string}
     */
    private static function emptyOwner(): array
    {
        return ['projects_owned' => 0, 'overdue_projects' => 0, 'open_tasks' => 0, 'overdue_tasks' => 0, 'next_deadline' => null];
    }

    private static function earliestUpcoming(?string $current, ?string $candidate, string $today): ?string
    {
        if ($candidate === null || $candidate < $today) {
            return $current;
        }

        return $current === null || $candidate < $current ? $candidate : $current;
    }

    /**
     * @param  array<string, mixed>  $owner
     * @return array{int, int, string}
     */
    private static function urgencyKey(array $owner): array
    {
        $late = (int) $owner['overdue_tasks'] + (int) $owner['overdue_projects'];
        $days = $owner['remaining_days'] ?? null;

        return [$late > 0 ? 0 : 1, is_int($days) ? $days : PHP_INT_MAX, (string) $owner['name']];
    }

    private function elapsedPercent(?string $startDate, ?int $duration, CarbonImmutable $today): ?int
    {
        if ($startDate === null || $duration === null || $duration < 1) {
            return null;
        }

        $elapsed = (int) CarbonImmutable::parse($startDate, $today->getTimezone())->startOfDay()->diffInDays($today, false) + 1;

        return max(0, min(100, (int) round($elapsed / $duration * 100)));
    }

    private function health(string $status, int $total, int $done, ?int $remaining, int $progress, ?int $elapsedPct): string
    {
        if ($status === 'ARCHIVED') {
            return 'archived';
        }
        if ($total > 0 && $done === $total) {
            return 'done';
        }
        if ($remaining === null) {
            return 'no_schedule';
        }
        if ($remaining < 0) {
            return 'overdue';
        }

        // Berisiko bila progres tertinggal jauh dari waktu yang sudah berjalan,
        // atau tenggat tinggal seminggu tapi progres belum separuh.
        $behind = $elapsedPct !== null && ($elapsedPct - $progress) >= 25;
        $tight = $remaining <= 7 && $progress < 50;

        return $behind || $tight ? 'at_risk' : 'on_track';
    }
}
