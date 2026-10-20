<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Services;

use Illuminate\Support\Facades\DB;

final class WorkloadAccessChecker
{
    public static function isSuperAdmin(int|string $userId): bool
    {
        return DB::table('users')
            ->where('id', $userId)
            ->where(function ($q) use ($userId): void {
                $q->where('email', 'superadmin@wlms.com')
                    ->orWhereExists(function ($sub) use ($userId): void {
                        $sub->select(DB::raw(1))
                            ->from('user_roles')
                            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                            ->where('user_roles.user_id', $userId)
                            ->where('roles.name', 'Superadmin');
                    });
            })
            ->exists();
    }

    public static function canManageWorkspace(int|string $userId, string $workspaceId): bool
    {
        if (self::isSuperAdmin($userId)) {
            return true;
        }

        // Fast-path 1: Direct membership in workspace_members (role admin/owner)
        if (DB::table('workspace_members')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->whereIn('role', ['admin', 'owner'])
            ->exists()) {
            return true;
        }

        // Fast-path 2: Direct match with workspaces.owner_group_id
        $isDirectGroupMatch = DB::table('workspaces')
            ->join('users', 'workspaces.owner_group_id', '=', 'users.org_unit_id')
            ->where('workspaces.id', $workspaceId)
            ->where('users.id', $userId)
            ->whereNotNull('users.org_unit_id')
            ->exists();

        if ($isDirectGroupMatch) {
            return true;
        }

        // Path 3: Ancestor unit ONLY (unit atasan boleh kelola workspace unit bawahan)
        // Unit bawahan (descendant) TIDAK boleh mengelola/arsip workspace atasan!
        $userOrgUnitId = DB::table('users')->where('id', $userId)->value('org_unit_id');
        if (! $userOrgUnitId) {
            return false;
        }

        $workspaceOwnerGroupId = DB::table('workspaces')->where('id', $workspaceId)->value('owner_group_id');
        if (! $workspaceOwnerGroupId) {
            return false;
        }

        return DB::table('org_unit_closures')
            ->where('ancestor_id', $userOrgUnitId)
            ->where('descendant_id', $workspaceOwnerGroupId)
            ->exists();
    }

    public static function canBypassWorkspaceFilter(int|string $userId, string $workspaceId): bool
    {
        if (self::canManageWorkspace($userId, $workspaceId)) {
            return true;
        }

        // Unit bawahan boleh melihat proyek/isu di workspace atasan (shared platform)
        $userOrgUnitId = DB::table('users')->where('id', $userId)->value('org_unit_id');
        if ($userOrgUnitId) {
            $workspaceOwnerGroupId = DB::table('workspaces')->where('id', $workspaceId)->value('owner_group_id');
            if ($workspaceOwnerGroupId) {
                return DB::table('org_unit_closures')
                    ->where('ancestor_id', $workspaceOwnerGroupId)
                    ->where('descendant_id', $userOrgUnitId)
                    ->exists();
            }
        }

        return false;
    }

    public static function canViewWorkspace(int|string $userId, string $workspaceId): bool
    {
        if (self::canBypassWorkspaceFilter($userId, $workspaceId)) {
            return true;
        }

        return DB::table('workspace_members')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function canViewProject(
        int|string $userId,
        string $projectId,
        ?string $workspaceId = null,
        int|string|null $leadId = null
    ): bool {
        if ($workspaceId === null) {
            $project = DB::table('projects')
                ->where('id', $projectId)
                ->whereNull('deleted_at')
                ->first(['workspace_id', 'lead_id']);

            if (! $project) {
                return false;
            }

            $workspaceId = (string) $project->workspace_id;
            $leadId = $project->lead_id;
        }

        if (self::canBypassWorkspaceFilter($userId, $workspaceId)) {
            return true;
        }

        if ($leadId !== null && (int) $leadId === (int) $userId) {
            return true;
        }

        if (DB::table('workspace_members')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->exists()) {
            return true;
        }

        if (DB::table('user_roles')
            ->where('context_type', 'PROJECT')
            ->where('context_id', $projectId)
            ->where('user_id', $userId)
            ->exists()) {
            return true;
        }

        return DB::table('issues')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->where('assignee_id', $userId)
            ->exists();
    }
}

