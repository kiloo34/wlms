<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class WorkspaceAnalyticsInput
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $dateRange = '30d',
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {}
}

