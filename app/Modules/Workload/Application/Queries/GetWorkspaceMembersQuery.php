<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use Illuminate\Support\Facades\DB;

final class GetWorkspaceMembersQuery
{
    /**
     * @return array<int, object>
     */
    public function execute(string $workspaceId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('workspace_members')
            ->join('users', 'workspace_members.user_id', '=', 'users.id')
            ->where('workspace_members.workspace_id', $workspaceId)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'workspace_members.role',
                'workspace_members.created_at',
            ])
            ->orderBy('workspace_members.created_at', 'desc')
            ->limit($limit)
            ->offset($offset);

        return $query->get()->all();
    }
}
