<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Support\Facades\DB;

final class RemoveWorkspaceMemberUseCase
{
    public function execute(string $workspaceId, int $userId): void
    {
        DB::transaction(function () use ($workspaceId, $userId) {
            $workspace = WorkspaceModel::findOrFail($workspaceId);

            $workspace->members()->detach($userId);
        });
    }
}
