<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class AddWorkspaceMemberUseCase
{
    public function execute(string $workspaceId, int $userId, string $role = 'member'): void
    {
        // Validasi role
        if (!in_array($role, ['viewer', 'member', 'admin'])) {
            throw new InvalidArgumentException("Invalid role provided.");
        }

        DB::transaction(function () use ($workspaceId, $userId, $role) {
            $workspace = WorkspaceModel::findOrFail($workspaceId);
            
            // Sync without detaching (upsert role)
            $workspace->members()->syncWithoutDetaching([
                $userId => ['role' => $role]
            ]);
        });
    }
}

