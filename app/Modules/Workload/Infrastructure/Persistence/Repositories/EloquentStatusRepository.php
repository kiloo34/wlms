<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Repositories;

use App\Modules\Workload\Domain\Entities\Status;
use App\Modules\Workload\Domain\Repositories\StatusRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\StatusId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers\StatusMapper;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;
use Illuminate\Support\Facades\DB;

final class EloquentStatusRepository implements StatusRepositoryInterface
{
    public function save(Status $status): void
    {
        DB::transaction(function () use ($status) {
            StatusModel::query()->updateOrCreate(
                ['id' => $status->getId()->value],
                StatusMapper::toPersistence($status)
            );
        });
    }

    public function findById(StatusId $id): ?Status
    {
        $model = StatusModel::query()->find($id->value);

        return $model ? StatusMapper::toDomain($model) : null;
    }

    public function delete(StatusId $id): void
    {
        DB::transaction(function () use ($id) {
            StatusModel::query()->where('id', $id->value)->delete();
        });
    }

    public function findAll(): array
    {
        $models = StatusModel::query()->get();

        return $models->map(fn (StatusModel $model) => StatusMapper::toDomain($model))->all();
    }
}
