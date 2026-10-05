<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\Repositories\SprintRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
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
        if (! $sprint) {
            throw new InvalidArgumentException('Sprint not found');
        }

        $sprint->complete(new DateTimeImmutable);
        $this->sprintRepository->save($sprint);

        $incompleteIssues = IssueModel::query()
            ->where('sprint_id', $sprintId)
            ->whereHas('status', function ($query) {
                $query->where('category', '!=', 'DONE');
            })
            ->get();

        /** @var IssueRepositoryInterface $issueRepo */
        $issueRepo = app(IssueRepositoryInterface::class);

        foreach ($incompleteIssues as $issueModel) {
            $issueEntity = $issueRepo->findById(new IssueId($issueModel->id));
            if ($issueEntity) {
                $newSprintId = $moveToSprintId ? new SprintId($moveToSprintId) : null;
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
