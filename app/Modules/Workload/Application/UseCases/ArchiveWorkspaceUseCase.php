<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\ArchiveWorkspaceInput;
use App\Modules\Workload\Domain\Repositories\WorkspaceRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ArchiveWorkspaceUseCase
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $repository
    ) {}

    public function execute(ArchiveWorkspaceInput $input): void
    {
        DB::transaction(function () use ($input) {
            $workspaceId = new WorkspaceId($input->workspaceId);
            $workspace = $this->repository->findById($workspaceId);

            if (!$workspace) {
                throw new InvalidArgumentException("Workspace not found");
            }

            $workspace->archive($input->actorUserId);
            
            $this->repository->save($workspace);
        });
    }
}
