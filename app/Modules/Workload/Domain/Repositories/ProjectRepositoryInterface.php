<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Project;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\ProjectKey;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    public function findById(ProjectId $id): ?Project;

    public function existsByKey(ProjectKey $key): bool;
}
