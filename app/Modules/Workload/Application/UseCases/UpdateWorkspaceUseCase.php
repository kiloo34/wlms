<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\UpdateWorkspaceInput;
use App\Modules\Workload\Domain\Repositories\WorkspaceRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateWorkspaceUseCase
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $repository
    ) {}

    public function execute(UpdateWorkspaceInput $input): void
    {
        DB::transaction(function () use ($input) {
            $workspaceId = new WorkspaceId($input->workspaceId);
            $workspace = $this->repository->findById($workspaceId);

            if (!$workspace) {
                throw new InvalidArgumentException("Workspace not found");
            }

            $workspace->updateDetails($input->name, $input->settings, $input->actorUserId);
            
            $this->repository->save($workspace);
        });
    }
}
