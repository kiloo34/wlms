<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Project lintas workspace yang boleh dilihat seorang user.
 *
 * Dipakai bersama oleh Dashboard, Laporan Project, Rekap Owner, dan Pencarian Global
 * supaya aturan visibilitasnya selalu sama.
 */
final class AccessibleProjectsQuery
{
    /**
     * @return Builder<ProjectModel>
     */
    public static function for(int|string $userId, ?string $orgUnitId = null): Builder
    {
        $query = ProjectModel::query()->whereNull('projects.deleted_at');

        if (WorkloadAccessChecker::isSuperAdmin($userId)) {
            return $query;
        }

        return $query->where(function ($q) use ($userId, $orgUnitId): void {
            $q->whereExists(function ($ws) use ($userId, $orgUnitId): void {
                $ws->select(DB::raw(1))
                    ->from('workspaces')
                    ->whereColumn('workspaces.id', 'projects.workspace_id')
                    ->whereNull('workspaces.deleted_at')
                    ->where(function ($wsAccess) use ($userId, $orgUnitId): void {
                        if ($orgUnitId !== null && $orgUnitId !== '') {
                            $wsAccess->where('workspaces.owner_group_id', $orgUnitId)
                                ->orWhereExists(function ($wm) use ($userId): void {
                                    $wm->select(DB::raw(1))
                                        ->from('workspace_members')
                                        ->whereColumn('workspace_members.workspace_id', 'workspaces.id')
                                        ->where('workspace_members.user_id', $userId);
                                });
                        } else {
                            $wsAccess->whereExists(function ($wm) use ($userId): void {
                                $wm->select(DB::raw(1))
                                    ->from('workspace_members')
                                    ->whereColumn('workspace_members.workspace_id', 'workspaces.id')
                                    ->where('workspace_members.user_id', $userId);
                            });
                        }
                    });
            })
                ->orWhere('projects.lead_id', $userId)
                ->orWhereExists(function ($ur) use ($userId): void {
                    $ur->select(DB::raw(1))
                        ->from('user_roles')
                        ->whereColumn('user_roles.context_id', 'projects.id')
                        ->where('user_roles.context_type', 'PROJECT')
                        ->where('user_roles.user_id', $userId);
                })
                ->orWhereExists(function ($iss) use ($userId): void {
                    $iss->select(DB::raw(1))
                        ->from('issues')
                        ->whereColumn('issues.project_id', 'projects.id')
                        ->whereNull('issues.deleted_at')
                        ->where('issues.assignee_id', $userId);
                });
        });
    }

    /**
     * @return list<string>
     */
    public static function ids(int|string $userId, ?string $orgUnitId = null): array
    {
        return array_values(array_map(
            'strval',
            self::for($userId, $orgUnitId)->pluck('projects.id')->all()
        ));
    }
}
