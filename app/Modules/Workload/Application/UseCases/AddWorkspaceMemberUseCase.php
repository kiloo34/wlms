<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Domain\Enums\WorkspaceMemberRole;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AddWorkspaceMemberUseCase
{
    public function execute(string $workspaceId, int $userId, string $role = 'member', int $dailyCapacityHours = 8): void
    {
        // Validasi role
        if (! in_array($role, WorkspaceMemberRole::values(), true)) {
            throw new InvalidArgumentException('Invalid role provided.');
        }

        $existing = DB::table('workspace_members')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            DB::table('workspace_members')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $userId)
                ->update(['updated_at' => now()]);

            return;
        }

        DB::table('workspace_members')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
            'daily_capacity_hours' => $dailyCapacityHours,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
