<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\DTOs\WorkspaceOutput;
use Illuminate\Support\Facades\DB;

final class GetWorkspacesByGroupQuery
{
    /**
     * @return WorkspaceOutput[]
     */
    public function execute(string $groupId, int $userId, int $limit = 50, ?string $cursor = null): array
    {
        // Pragmatic CQRS: Query Builder langsung untuk performa maksimal (Prinsip #3)
        $query = DB::table('workspaces')
            ->where(function ($q) use ($groupId, $userId) {
                $q->where('owner_group_id', $groupId)
                    ->orWhereExists(function ($q2) use ($userId) {
                        $q2->select(DB::raw(1))
                            ->from('workspace_members')
                            ->whereColumn('workspace_members.workspace_id', 'workspaces.id')
                            ->where('workspace_members.user_id', $userId);
                    });
            })
            ->whereNull('deleted_at')
            ->select(['id', 'name', 'status', 'owner_group_id'])
            ->orderBy('id', 'asc') // untuk cursor pagination
            ->limit($limit);

        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $results = $query->get();

        return $results->map(function ($row) {
            return new WorkspaceOutput(
                id: $row->id,
                name: $row->name,
                status: $row->status,
                ownerGroupId: $row->owner_group_id
            );
        })->all();
    }
}
