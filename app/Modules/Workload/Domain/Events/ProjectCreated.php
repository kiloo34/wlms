<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Events;

use DateTimeImmutable;

final class ProjectCreated
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $workspaceId,
        public readonly string $actorId,
        public readonly string $key,
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt
    ) {}
}
