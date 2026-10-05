<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final class GetWorkspaceIssuesQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Paginator<int, IssueModel>
     */
    public function execute(string $workspaceId, array $filters = []): Paginator
    {
        $query = IssueModel::with(['project', 'assignee', 'reporter', 'status', 'type', 'priority', 'sprint'])
            ->whereHas('project', fn (Builder $q) => $q->where('workspace_id', $workspaceId));

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

        return $query->orderBy('created_at', 'desc')->simplePaginate((int) ($filters['limit'] ?? 15));
    }
}
