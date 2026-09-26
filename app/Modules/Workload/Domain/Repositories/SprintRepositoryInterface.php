<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Sprint;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;

interface SprintRepositoryInterface
{
    public function save(Sprint $sprint): void;
    public function findById(SprintId $id): ?Sprint;
    public function hasActiveSprint(ProjectId $projectId): bool;
}
