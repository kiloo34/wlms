<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GetWorkspaceIssuesQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Paginator<int, IssueModel>
     */
    public function execute(string $workspaceId, array $filters = [], ?string $actorUserId = null): Paginator
    {
        $query = IssueModel::with(['project', 'assignee', 'reporter', 'status', 'type', 'priority', 'sprint'])
            ->whereHas('project', fn (Builder $q) => $q->where('workspace_id', $workspaceId));

        if ($actorUserId !== null && $actorUserId !== '') {
            if (! WorkloadAccessChecker::canBypassWorkspaceFilter($actorUserId, $workspaceId)) {
                $query->whereHas('project', function (Builder $pq) use ($actorUserId) {
                    $pq->where('projects.lead_id', $actorUserId)
                        ->orWhereExists(function ($sub) use ($actorUserId) {
                            $sub->select(DB::raw(1))
                                ->from('user_roles')
                                ->whereColumn('user_roles.context_id', 'projects.id')
                                ->where('user_roles.context_type', 'PROJECT')
                                ->where('user_roles.user_id', $actorUserId);
                        })
                        ->orWhereExists(function ($sub) use ($actorUserId) {
                            $sub->select(DB::raw(1))
                                ->from('issues as i2')
                                ->whereColumn('i2.project_id', 'projects.id')
                                ->whereNull('i2.deleted_at')
                                ->where('i2.assignee_id', $actorUserId);
                        });
                });
            }
        }

        if (! empty($filters['status_category'])) {
            $query->whereHas('status', fn (Builder $q) => $q->where('category', $filters['status_category']));
        }

        if (! empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (! empty($filters['sprint_id'])) {
            $query->where('sprint_id', $filters['sprint_id']);
        }

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (! empty($filters['type_id'])) {
            $query->where('issue_type_id', $filters['type_id']);
        }

        if (! empty($filters['priority_id'])) {
            $query->where('priority_id', $filters['priority_id']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('number', 'like', '%'.$filters['search'].'%');
            });
        }

        $perPage = min(500, max(1, (int) ($filters['per_page'] ?? $filters['limit'] ?? 15)));

        return $query->orderBy('created_at', 'desc')->simplePaginate($perPage);
    }
}
