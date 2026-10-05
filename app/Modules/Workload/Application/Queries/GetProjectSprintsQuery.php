<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use Illuminate\Support\Facades\DB;

final class GetProjectSprintsQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $projectId): array
    {
        return DB::table('sprints')
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sprint) {
                return [
                    'id' => $sprint->id,
                    'project_id' => $sprint->project_id,
                    'name' => $sprint->name,
                    'goal' => $sprint->goal,
                    'state' => $sprint->state,
                    'start_date' => $sprint->start_date,
                    'end_date' => $sprint->end_date,
                    'committed_points' => $sprint->committed_points,
                    'completed_points' => $sprint->completed_points,
                    'created_at' => $sprint->created_at,
                ];
            })
            ->toArray();
    }
}
