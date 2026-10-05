<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\StatusDTO;
use App\Modules\Workload\Domain\Entities\Status;
use App\Modules\Workload\Domain\Repositories\StatusRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\StatusId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManageStatusUseCase
{
    public function __construct(private StatusRepositoryInterface $repository) {}

    public function create(StatusDTO $dto): Status
    {
        return DB::transaction(function () use ($dto) {
            $status = Status::create(
                new StatusId((string) Str::uuid()),
                $dto->name,
                $dto->slug,
                $dto->category,
                $dto->color
            );
            $this->repository->save($status);

            return $status;
        });
    }

    public function update(string $id, StatusDTO $dto): Status
    {
        return DB::transaction(function () use ($id, $dto) {
            $status = $this->repository->findById(new StatusId($id));
            if (! $status) {
                throw new \Exception('Status not found');
            }

            $status->update($dto->name, $dto->slug, $dto->category, $dto->color);
            $this->repository->save($status);

            return $status;
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $this->repository->delete(new StatusId($id));
        });
    }

    /**
     * @return Status[]
     */
    public function getAll(): array
    {
        return $this->repository->findAll();
    }
}
