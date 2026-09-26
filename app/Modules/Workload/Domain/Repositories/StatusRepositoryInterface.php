<?php
declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Status;
use App\Modules\Workload\Domain\ValueObjects\StatusId;

interface StatusRepositoryInterface
{
    public function save(Status $status): void;
    public function findById(StatusId $id): ?Status;
    public function delete(StatusId $id): void;
    /** @return Status[] */
    public function findAll(): array;
}

