<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\Events;
use DateTimeImmutable;
final class IssueCreated
{
    public function __construct(
        public readonly string $issueId,
        public readonly string $projectId,
        public readonly string $issueNumber,
        public readonly string $title,
        public readonly int|string $reporterId,
        public readonly DateTimeImmutable $createdAt
    ) {}
}
