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
    public function execute(string $groupId, int|string $userId, int $limit = 50, ?string $cursor = null, bool $isSuperadmin = false): array
    {
        // Pragmatic CQRS: Query Builder langsung untuk performa maksimal (Prinsip #3)
        $query = DB::table('workspaces');

        if (! $isSuperadmin) {
            $query->where(function ($q) use ($groupId, $userId) {
                // 1. Tuan Rumah (Internal Divisi)
                $q->where('owner_group_id', $groupId)
                    // 2. Diundang langsung sebagai Member Workspace
                    ->orWhereExists(function ($q2) use ($userId) {
                        $q2->select(DB::raw(1))
                            ->from('workspace_members')
                            ->whereColumn('workspace_members.workspace_id', 'workspaces.id')
                            ->where('workspace_members.user_id', $userId);
                    })
                    // 3. Tamu (Eksternal) yang diundang ke Project spesifik
                    ->orWhereExists(function ($q3) use ($userId) {
                        $q3->select(DB::raw(1))
                            ->from('projects')
                            ->whereColumn('projects.workspace_id', 'workspaces.id')
                            ->whereNull('projects.deleted_at')
                            ->where(function ($qProj) use ($userId) {
                                // Tamu adalah Project Lead
                                $qProj->where('projects.lead_id', $userId)
                                    // Tamu punya role di project ini
                                    ->orWhereExists(function ($qRole) use ($userId) {
                                        $qRole->select(DB::raw(1))
                                            ->from('user_roles')
                                            ->whereColumn('user_roles.context_id', 'projects.id')
                                            ->where('user_roles.context_type', 'PROJECT')
                                            ->where('user_roles.user_id', $userId);
                                    })
                                    // Tamu diassign ke tiket di project ini
                                    ->orWhereExists(function ($qIssue) use ($userId) {
                                        $qIssue->select(DB::raw(1))
                                            ->from('issues')
                                            ->whereColumn('issues.project_id', 'projects.id')
                                            ->whereNull('issues.deleted_at')
                                            ->where('issues.assignee_id', $userId);
                                    });
                            });
                    });
            });
        }

        $query->whereNull('deleted_at')
            ->select(['id', 'name', 'status', 'owner_group_id'])
            ->orderBy('id', 'asc') // untuk cursor pagination
            ->limit($limit);

        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $results = $query->get();

        return $results->map(function ($row) {
            return new WorkspaceOutput(
                id: (string) $row->id,
                name: (string) $row->name,
                status: (string) $row->status,
                ownerGroupId: (string) $row->owner_group_id
            );
        })->all();
    }
}
