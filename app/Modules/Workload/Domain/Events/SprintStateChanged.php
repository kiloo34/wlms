<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Events;

use DateTimeImmutable;

final class SprintStateChanged
{
    public function __construct(
        public readonly string $sprintId,
        public readonly string $projectId,
        public readonly string $sprintName,
        public readonly string $newState,
        public readonly int|string $actorId,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
