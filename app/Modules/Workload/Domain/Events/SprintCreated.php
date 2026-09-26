<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Events;

use DateTimeImmutable;

final class SprintCreated
{
    public function __construct(
        public readonly string $sprintId,
        public readonly string $projectId,
        public readonly string $actorId,
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt
    ) {}
}
