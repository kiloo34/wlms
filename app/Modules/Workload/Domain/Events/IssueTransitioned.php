<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\Events;
use DateTimeImmutable;
final class IssueTransitioned
{
    public function __construct(
        public readonly string $issueId,
        public readonly string $actorId,
        public readonly ?string $fromStatusId,
        public readonly string $toStatusId,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}

