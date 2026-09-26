<?php
declare(strict_types=1);
namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;

/**
 * CQRS Read: Bypass Domain Layer for read-optimized board view.
 * Groups issues by status for Kanban rendering.
 */
final class GetBoardIssuesQuery
{
    public function execute(string $projectId, string $sprintId): array
    {
        // Fetch project to know its workflow
        $project = \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel::find($projectId);
        $workflowId = $project->workflow_id;
        if (!$workflowId) {
            $defaultWorkflow = \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel::where('is_default', true)->first();
            $workflowId = $defaultWorkflow->id;
        }

        // Get unique status IDs used in this workflow
        $transitions = \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowTransitionModel::where('workflow_id', $workflowId)->get();
        $statusIds = collect();
        foreach ($transitions as $t) {
            if ($t->from_status_id) $statusIds->push($t->from_status_id);
            if ($t->to_status_id) $statusIds->push($t->to_status_id);
        }
        $statusIds = $statusIds->unique()->values()->all();

        // Fetch only those statuses
        $statuses = StatusModel::query()->whereIn('id', $statusIds)->get();
        
        // Optionally sort them in a logical order (e.g. TODO -> IN_PROGRESS -> DONE)
        $statusOrder = ['TODO' => 1, 'IN_PROGRESS' => 2, 'DONE' => 3];
        $statuses = $statuses->sortBy(function ($status) use ($statusOrder) {
            return $statusOrder[$status->category] ?? 99;
        })->values();
        $issues = IssueModel::query()
            ->where('project_id', $projectId)
            ->where('sprint_id', $sprintId)
            ->get()
            ->groupBy('status_id');

        return $statuses->map(fn($status) => [
            'status_id' => $status->id,
            'status_name' => $status->name,
            'status_slug' => $status->slug,
            'status_color' => $status->color,
            'issues' => collect($issues->get($status->id, []))->map(fn($issue) => [
                'id' => $issue->id,
                'number' => $issue->number,
                'title' => $issue->title,
                'story_points' => $issue->story_points,
                'assignee_id' => $issue->assignee_id,
                'priority_id' => $issue->priority_id,
                'issue_type_id' => $issue->issue_type_id,
            ])->values(),
        ])->toArray();
    }
}
