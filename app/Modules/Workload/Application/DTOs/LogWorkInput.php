<?php

namespace App\Modules\Workload\Application\DTOs;

class LogWorkInput
{
    public function __construct(
        public readonly string $issueId,
        public readonly string $authorUserId,
        public readonly int $timeSpentSeconds,
        public readonly string $description,
        public readonly string $startedAt
    ) {
    }
}
