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
            ->where('projects.workspace_id', $input->workspaceId)
            ->whereNull('projects.deleted_at')
            ->select(['id', 'workspace_id', 'key', 'name', 'status', 'priority_id'])
            ->addSelect([
                'total_issues_count' => DB::table('issues')
                    ->selectRaw('count(*)')
                    ->whereColumn('issues.project_id', 'projects.id'),
                'completed_issues_count' => DB::table('issues')
                    ->join('statuses', 'issues.status_id', '=', 'statuses.id')
                    ->selectRaw('count(*)')
                    ->whereColumn('issues.project_id', 'projects.id')
                    ->where('statuses.category', 'DONE'),
            ])
            ->orderBy('id', 'asc') // untuk cursor pagination
            ->limit($input->limit);

        // Anti-IDOR / Context-Aware RBAC for Projects
        $isSuperAdmin = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $input->actorUserId)
            ->where('roles.name', 'Superadmin')
            ->exists();

        // Cek apakah user adalah Internal Member (Tuan Rumah) berdasarkan org_unit_id
        $isInternalMember = false;
        if (! $isSuperAdmin) {
            $user = DB::table('users')->where('id', $input->actorUserId)->select('org_unit_id')->first();
            $workspace = DB::table('workspaces')->where('id', $input->workspaceId)->select('owner_group_id')->first();
            if ($user && $workspace && $user->org_unit_id === $workspace->owner_group_id) {
                $isInternalMember = true;
            }
        }

        if (! $isSuperAdmin && ! $isInternalMember) {
            $isWorkspaceAdmin = DB::table('workspace_members')
                ->where('workspace_id', $input->workspaceId)
                ->where('user_id', $input->actorUserId)
                ->where('role', 'admin')
                ->exists();

            if (! $isWorkspaceAdmin) {
                $query->where(function ($q) use ($input) {
                    // 1. User is the Project Lead
                    $q->where('projects.lead_id', $input->actorUserId)
                      // 2. User has a context role in the project
                      ->orWhereExists(function ($sub) use ($input) {
                          $sub->select(DB::raw(1))
                              ->from('user_roles')
                              ->whereColumn('user_roles.context_id', 'projects.id')
                              ->where('user_roles.context_type', 'PROJECT')
                              ->where('user_roles.user_id', $input->actorUserId);
                      })
                      // 3. User is assigned to at least one issue in the project
                      ->orWhereExists(function ($sub) use ($input) {
                          $sub->select(DB::raw(1))
                              ->from('issues')
                              ->whereColumn('issues.project_id', 'projects.id')
                              ->whereNull('issues.deleted_at')
                              ->where('issues.assignee_id', $input->actorUserId);
                      });
                });
            }
        }

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
                priorityId: $row->priority_id ? (string) $row->priority_id : null,
                totalIssuesCount: (int) ($row->total_issues_count ?? 0),
                completedIssuesCount: (int) ($row->completed_issues_count ?? 0)
            );
        })->all();
    }
}
