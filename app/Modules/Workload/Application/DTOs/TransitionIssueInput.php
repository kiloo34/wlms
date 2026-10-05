<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class TransitionIssueInput
{
    public function __construct(
        public readonly string $issueId,
        public readonly string $toStatusId,
        public readonly string $actorUserId
    ) {}
}
