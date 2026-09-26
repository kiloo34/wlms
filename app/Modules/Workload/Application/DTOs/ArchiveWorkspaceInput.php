<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class ArchiveWorkspaceInput
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $actorUserId
    ) {}
}
