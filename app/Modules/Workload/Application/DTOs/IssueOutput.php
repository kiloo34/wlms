<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

use App\Modules\Workload\Domain\Entities\Issue;

final class IssueOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $issueNumber,
        public readonly string $title,
        public readonly string $statusId,
        public readonly ?int $storyPoints
    ) {}

    public static function fromDomain(Issue $issue): self
    {
        return new self(
            $issue->getId()->value,
            $issue->getProjectId()->value,
            $issue->getNumber()->display,
            $issue->getTitle(),
            $issue->getStatusId()->value,
            $issue->getStoryPoints()
        );
    }
}
