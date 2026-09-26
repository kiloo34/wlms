<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\CreateSprintInput;
use App\Modules\Workload\Application\DTOs\SprintOutput;
use App\Modules\Workload\Domain\Entities\Sprint;
use App\Modules\Workload\Domain\Repositories\SprintRepositoryInterface;
use App\Modules\Workload\Domain\Repositories\ProjectRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use Exception;

final class CreateSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $sprintRepository,
        private readonly ProjectRepositoryInterface $projectRepository
    ) {}

    public function execute(CreateSprintInput $input): SprintOutput
    {
        // Simulasi validasi Anti-IDOR
        $this->ensureUserCanCreateSprint($input->actorUserId, $input->projectId);

        $projectId = new ProjectId($input->projectId);
        $project = $this->projectRepository->findById($projectId);

        if (!$project) {
            throw new Exception("Project not found.");
        }

        if ($project->getStatus() === 'ARCHIVED') {
            throw new Exception("Cannot create sprint in an archived project.");
        }

        $sprint = Sprint::create(
            new SprintId($input->sprintId),
            $projectId,
            $input->name,
            $input->goal,
            $input->actorUserId
        );

        $this->sprintRepository->save($sprint);

        return SprintOutput::fromDomain($sprint);
    }

    private function ensureUserCanCreateSprint(string $userId, string $projectId): void
    {
        // Logic to be implemented via Policy/Closure Table
    }
}
