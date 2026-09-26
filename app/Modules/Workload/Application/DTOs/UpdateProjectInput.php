<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class UpdateProjectInput
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $leadId,
        public readonly ?string $workflowId,
        public readonly string $actorUserId,
        public readonly ?string $priorityId = null,
    ) {}
}
