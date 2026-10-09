<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class ProjectAnalyticsInput
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $dateRange = '30d',
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?string $sprintId = null,
    ) {}
}

