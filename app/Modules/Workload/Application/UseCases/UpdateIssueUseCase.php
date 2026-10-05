<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\IssueOutput;
use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use App\Modules\Workload\Domain\ValueObjects\PriorityId;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use Exception;
use Illuminate\Support\Facades\DB;

final class UpdateIssueUseCase
{
    public function __construct(
        private readonly IssueRepositoryInterface $issueRepository
    ) {}

    public function execute(
        string $issueId,
        string $title,
        ?string $description,
        string $priorityId,
        ?string $sprintId
    ): IssueOutput {
        return DB::transaction(function () use ($issueId, $title, $description, $priorityId, $sprintId) {
            $issue = $this->issueRepository->findById(new IssueId($issueId));
            if (! $issue) {
                throw new Exception('Issue not found.');
            }

            $issue->updateDetails(
                $title,
                $description,
                new PriorityId($priorityId),
                $sprintId ? new SprintId($sprintId) : null
            );

            $this->issueRepository->save($issue);

            return IssueOutput::fromDomain($issue);
        });
    }
}
