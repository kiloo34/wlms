<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;

/**
 * CQRS Read: Issues not yet assigned to any sprint (the Backlog).
 */
final class GetBacklogIssuesQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $projectId): array
    {
        return IssueModel::query()
            ->where('project_id', $projectId)
            ->whereNull('sprint_id')
            ->orderBy('number')
            ->get()
            ->map(fn (IssueModel $issue) => [
                'id' => $issue->id,
                'number' => $issue->number,
                'title' => $issue->title,
                'story_points' => $issue->story_points,
                'assignee_id' => $issue->assignee_id,
                'status_id' => $issue->status_id,
                'priority_id' => $issue->priority_id,
                'issue_type_id' => $issue->issue_type_id,
            ])
            ->toArray();
    }
}
