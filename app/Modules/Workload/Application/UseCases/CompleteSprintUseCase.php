<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Domain\Repositories\SprintRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use DateTimeImmutable;
use InvalidArgumentException;

final class CompleteSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $sprintRepository
    ) {}

    public function execute(string $sprintId, string $actorId, ?string $moveToSprintId = null): void
    {
        $sprint = $this->sprintRepository->findById(new SprintId($sprintId));
        if (!$sprint) {
            throw new InvalidArgumentException("Sprint not found");
        }

        $sprint->complete(new DateTimeImmutable());
        $this->sprintRepository->save($sprint);

        $incompleteIssues = \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel::query()
            ->where('sprint_id', $sprintId)
            ->whereHas('status', function ($query) {
                $query->where('category', '!=', 'DONE');
            })
            ->get();

        /** @var \App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface $issueRepo */
        $issueRepo = app(\App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface::class);

        foreach ($incompleteIssues as $issueModel) {
            $issueEntity = $issueRepo->findById(new \App\Modules\Workload\Domain\ValueObjects\IssueId($issueModel->id));
            if ($issueEntity) {
                $newSprintId = $moveToSprintId ? new \App\Modules\Workload\Domain\ValueObjects\SprintId($moveToSprintId) : null;
                $issueEntity->updateDetails(
                    $issueEntity->getTitle(),
                    $issueEntity->getDescription(),
                    $issueEntity->getPriorityId(),
                    $newSprintId,
                    $issueEntity->getOriginalEstimateSeconds()
                );
                
                // The user wants to retain assignee even if moving to backlog
                // if ($newSprintId === null && $issueEntity->getAssigneeId()) {
                //     $issueEntity->assign(null, $actorId);
                // }

                $issueRepo->save($issueEntity);
            }
        }
    }
}
