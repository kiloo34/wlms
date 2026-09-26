<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

use App\Modules\Workload\Domain\Entities\Sprint;

final class SprintOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $name,
        public readonly string $state
    ) {}

    public static function fromDomain(Sprint $sprint): self
    {
        return new self(
            $sprint->getId()->value,
            $sprint->getProjectId()->value,
            $sprint->getName(),
            $sprint->getState()
        );
    }
}
