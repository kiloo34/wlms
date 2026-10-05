<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\ArchiveProjectInput;
use App\Modules\Workload\Domain\Repositories\ProjectRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ArchiveProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository
    ) {}

    public function execute(ArchiveProjectInput $input): void
    {
        DB::transaction(function () use ($input) {
            $projectId = new ProjectId($input->projectId);
            $project = $this->repository->findById($projectId);

            if (! $project) {
                throw new InvalidArgumentException('Project not found');
            }

            $project->archive($input->actorUserId);

            $this->repository->save($project);
        });
    }
}
