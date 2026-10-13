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

    public static function canBypassWorkspaceFilter(int|string $userId, string $workspaceId): bool
    {
        if (self::isSuperAdmin($userId)) {
            return true;
        }

        $isInternalMember = DB::table('workspaces')
            ->join('users', 'workspaces.owner_group_id', '=', 'users.org_unit_id')
            ->where('workspaces.id', $workspaceId)
            ->where('users.id', $userId)
            ->whereNotNull('users.org_unit_id')
            ->exists();

        if ($isInternalMember) {
            return true;
        }

        return DB::table('workspace_members')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->exists();
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

    public static function canViewProject(int|string $userId, string $projectId): bool
    {
        $project = DB::table('projects')
            ->where('id', $projectId)
            ->whereNull('deleted_at')
            ->first();

        if (! $project) {
            return false;
        }

        if (self::canBypassWorkspaceFilter($userId, (string) $project->workspace_id)) {
            return true;
        }

        if ($project->lead_id !== null && (int) $project->lead_id === (int) $userId) {
            return true;
        }

        if (DB::table('workspace_members')
            ->where('workspace_id', $project->workspace_id)
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

