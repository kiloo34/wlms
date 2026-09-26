<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\GetProjectsInput;
use App\Modules\Workload\Application\DTOs\ProjectOutput;
use Illuminate\Support\Facades\DB;

final class GetProjectsUseCase
{
    /**
     * @return ProjectOutput[]
     */
    public function execute(GetProjectsInput $input): array
    {
        // Pragmatic CQRS/Performance: Query Builder langsung untuk performa (tanpa hydration yang berat)
        $query = DB::table('projects')
            ->where('workspace_id', $input->workspaceId)
            ->whereNull('deleted_at')
            ->select(['id', 'workspace_id', 'key', 'name', 'status', 'priority_id'])
            ->orderBy('id', 'asc') // untuk cursor pagination
            ->limit($input->limit);

        if ($input->cursor) {
            $query->where('id', '>', $input->cursor);
        }

        $results = $query->get();

        return $results->map(function ($row) {
            return new ProjectOutput(
                id: (string) $row->id,
                workspaceId: (string) $row->workspace_id,
                key: (string) $row->key,
                name: (string) $row->name,
                status: (string) $row->status,
                priorityId: $row->priority_id ? (string) $row->priority_id : null
            );
        })->all();
    }
}
