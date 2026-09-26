<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class ArchiveProjectInput
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $actorUserId
    ) {}
}
