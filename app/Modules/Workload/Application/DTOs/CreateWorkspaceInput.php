<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class CreateWorkspaceInput
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $ownerGroupId,
        public readonly string $name,
        public readonly string $actorUserId // Siapa yang merequest (untuk Anti-IDOR Check)
    ) {}
}
