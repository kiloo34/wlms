<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Workspace;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;

interface WorkspaceRepositoryInterface
{
    /**
     * Menyimpan mutasi Workspace ke database.
     */
    public function save(Workspace $workspace): void;

    public function findById(WorkspaceId $id): ?Workspace;

    public function countByGroupId(string $groupId): int;

    /**
     * @return Workspace[]
     */
    public function findAllByGroupId(string $groupId, int $limit = 50, int $offset = 0): array;
}
