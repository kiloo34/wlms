<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RemoveWorkspaceMemberUseCase
{
    public function execute(string $workspaceId, int $userId): void
    {
        DB::transaction(function () use ($workspaceId, $userId) {
            $member = DB::table('workspace_members')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $userId)
                ->first();

            if ($member !== null && in_array($member->role, ['admin', 'owner'], true)) {
                $otherAdminsCount = DB::table('workspace_members')
                    ->where('workspace_id', $workspaceId)
                    ->where('user_id', '!=', $userId)
                    ->whereIn('role', ['admin', 'owner'])
                    ->count();

                if ($otherAdminsCount === 0) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Cannot remove the last remaining admin or owner in the workspace.',
                    ]);
                }
            }

            DB::table('issues')
                ->where('assignee_id', $userId)
                ->whereIn('project_id', function ($q) use ($workspaceId) {
                    $q->select('id')->from('projects')->where('workspace_id', $workspaceId);
                })
                ->update(['assignee_id' => null]);

            DB::table('workspace_members')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $userId)
                ->delete();
        });
    }
}
