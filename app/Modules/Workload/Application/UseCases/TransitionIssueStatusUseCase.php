<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\IssueOutput;
use App\Modules\Workload\Application\DTOs\TransitionIssueInput;
use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\Repositories\WorkflowRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use App\Modules\Workload\Domain\ValueObjects\StatusId;
use Exception;
use Illuminate\Support\Facades\DB;

final class TransitionIssueStatusUseCase
{
    public function __construct(
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function execute(TransitionIssueInput $input): IssueOutput
    {
        return DB::transaction(function () use ($input) {
            $issue = $this->issueRepository->findById(new IssueId($input->issueId));
            if (! $issue) {
                throw new Exception('Issue not found.');
            }

            // Load workflow engine with all valid transitions for this project
            $engine = $this->workflowRepository->getEngineForProject($issue->getProjectId());

            // Domain entity validates against WorkflowEngine — throws InvalidTransitionException if denied
            $issue->transition(new StatusId($input->toStatusId), $engine, $input->actorUserId);

            $this->issueRepository->save($issue);

            return IssueOutput::fromDomain($issue);
        });
    }
}
