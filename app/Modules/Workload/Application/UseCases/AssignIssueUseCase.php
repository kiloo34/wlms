<?php
declare(strict_types=1);
namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\AssignIssueInput;
use App\Modules\Workload\Application\DTOs\IssueOutput;
use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\AssigneeId;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use Exception;

final class AssignIssueUseCase
{
    public function __construct(
        private readonly IssueRepositoryInterface $issueRepository
    ) {}

    public function execute(AssignIssueInput $input): IssueOutput
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($input) {
            $issue = $this->issueRepository->findById(new IssueId($input->issueId));
            if (!$issue) {
                throw new Exception("Issue not found.");
            }

            $issue->assign($input->assigneeId ? new AssigneeId($input->assigneeId) : null, $input->actorUserId);
            $this->issueRepository->save($issue);

            return IssueOutput::fromDomain($issue);
        });
    }
}
