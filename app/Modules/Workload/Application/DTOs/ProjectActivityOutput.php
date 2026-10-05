<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class ProjectActivityOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $issueId,
        public readonly string $issueTitle,
        public readonly string $actorId,
        public readonly string $actorName,
        public readonly string $fieldChanged,
        public readonly ?string $oldValue,
        public readonly ?string $newValue,
        public readonly string $createdAt
    ) {}
}
