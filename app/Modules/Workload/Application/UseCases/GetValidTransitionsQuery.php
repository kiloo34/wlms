<?php
declare(strict_types=1);
namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\Repositories\WorkflowRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use Exception;

/**
 * CQRS Read: Returns valid transitions from the issue's current status.
 * Used by Frontend to render only allowed drag targets / buttons.
 */
final class GetValidTransitionsQuery
{
    public function __construct(
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly WorkflowRepositoryInterface $workflowRepository
    ) {}

    public function execute(string $issueId): array
    {
        $issue = $this->issueRepository->findById(new IssueId($issueId));
        if (!$issue) throw new Exception("Issue not found.");

        $engine = $this->workflowRepository->getEngineForProject($issue->getProjectId());
        $transitions = $engine->getValidTransitionsFrom($issue->getStatusId()->value);

        return array_map(fn($t) => [
            'to_status_id' => $t->toStatusId,
            'name' => $t->name,
        ], $transitions);
    }
}
