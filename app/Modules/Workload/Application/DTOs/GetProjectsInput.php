<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class GetProjectsInput
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly int $limit = 50,
        public readonly ?string $cursor = null
    ) {}
}
