<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Issue;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;

interface IssueRepositoryInterface
{
    public function save(Issue $issue): void;
    public function findById(IssueId $id): ?Issue;
    public function delete(IssueId $id): void;
    /** Returns next sequential number for a project's issues */
    public function nextNumberForProject(ProjectId $projectId): int;
}

