<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\DTOs\ProjectActivityOutput;
use Illuminate\Support\Facades\DB;

final class GetProjectActivityQuery
{
    /**
     * @return ProjectActivityOutput[]
     */
    public function execute(string $projectId, int $limit = 50, ?string $cursor = null): array
    {
        // Pragmatic CQRS: Query Builder directly for max performance and preventing N+1
        $query = DB::table('issue_histories')
            ->join('issues', 'issue_histories.issue_id', '=', 'issues.id')
            ->join('users', 'issue_histories.actor_id', '=', 'users.id')
            ->where('issues.project_id', $projectId)
            ->whereNull('issues.deleted_at')
            ->select([
                'issue_histories.id',
                'issue_histories.issue_id',
                'issues.title as issue_title',
                'issue_histories.actor_id',
                'users.name as actor_name',
                'issue_histories.field_changed',
                'issue_histories.old_value',
                'issue_histories.new_value',
                'issue_histories.created_at',
            ])
            ->orderBy('issue_histories.created_at', 'desc')
            ->orderBy('issue_histories.id', 'desc')
            ->limit($limit);

        if ($cursor) {
            // Basic cursor pagination using created_at
            $query->where('issue_histories.created_at', '<', $cursor);
        }

        $results = $query->get();

        return $results->map(function ($row) {
            return new ProjectActivityOutput(
                id: (string) $row->id,
                issueId: (string) $row->issue_id,
                issueTitle: (string) $row->issue_title,
                actorId: (string) $row->actor_id,
                actorName: (string) $row->actor_name,
                fieldChanged: (string) $row->field_changed,
                oldValue: $row->old_value ? (string) $row->old_value : null,
                newValue: $row->new_value ? (string) $row->new_value : null,
                createdAt: (string) $row->created_at
            );
        })->all();
    }
}
