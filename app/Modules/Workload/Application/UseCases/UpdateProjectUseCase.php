<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\UpdateProjectInput;
use App\Modules\Workload\Domain\Repositories\ProjectRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\PriorityId;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository
    ) {}

    public function execute(UpdateProjectInput $input): void
    {
        DB::transaction(function () use ($input) {
            $projectId = new ProjectId($input->projectId);
            $project = $this->repository->findById($projectId);

            if (! $project) {
                throw new InvalidArgumentException('Project not found');
            }

            $project->update(
                $input->name,
                $input->description,
                $input->leadId,
                $input->workflowId ? new WorkflowId($input->workflowId) : null,
                $input->actorUserId,
                $input->priorityId ? new PriorityId($input->priorityId) : null
            );

            $this->repository->save($project);
        });
    }
}
