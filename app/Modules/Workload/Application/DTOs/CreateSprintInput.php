<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class CreateSprintInput
{
    public function __construct(
        public readonly string $sprintId,
        public readonly string $projectId,
        public readonly string $name,
        public readonly ?string $goal,
        public readonly string $actorUserId
    ) {}
}
