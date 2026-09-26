<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\CreateProjectInput;
use App\Modules\Workload\Application\DTOs\ProjectOutput;
use App\Modules\Workload\Domain\Entities\Project;
use App\Modules\Workload\Domain\Repositories\ProjectRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\ProjectKey;
use App\Modules\Workload\Domain\ValueObjects\PriorityId;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use Exception;

final class CreateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository
    ) {}

    public function execute(CreateProjectInput $input): ProjectOutput
    {
        // Simulasi validasi Anti-IDOR: pastikan actorUserId bisa membuat project di workspaceId
        $this->ensureUserCanCreateProject($input->actorUserId, $input->workspaceId);

        $key = new ProjectKey($input->key);

        if ($this->repository->existsByKey($key)) {
            throw new Exception("Project key already exists.");
        }

        $project = Project::create(
            new ProjectId($input->projectId),
            new WorkspaceId($input->workspaceId),
            $key,
            $input->name,
            $input->description,
            $input->leadId,
            $input->actorUserId,
            $input->priorityId ? new PriorityId($input->priorityId) : null,
        );

        $this->repository->save($project);

        return ProjectOutput::fromDomain($project);
    }

    private function ensureUserCanCreateProject(string $userId, string $workspaceId): void
    {
        // Logic to be implemented via Policy/Closure Table
    }
}
