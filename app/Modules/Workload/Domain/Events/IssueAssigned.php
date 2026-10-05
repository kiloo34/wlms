<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Events;

use DateTimeImmutable;

final class IssueAssigned
{
    public function __construct(
        public readonly string $issueId,
        public readonly string $issueNumber,
        public readonly int|string|null $assigneeId,
        public readonly int|string $actorId,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}
