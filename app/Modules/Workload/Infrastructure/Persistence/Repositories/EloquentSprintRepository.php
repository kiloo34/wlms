<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Repositories;

use App\Modules\Workload\Domain\Entities\Sprint;
use App\Modules\Workload\Domain\Repositories\SprintRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers\SprintMapper;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use Illuminate\Support\Facades\DB;

final class EloquentSprintRepository implements SprintRepositoryInterface
{
    public function save(Sprint $sprint): void
    {
        DB::transaction(function () use ($sprint) {
            $data = SprintMapper::toPersistence($sprint);

            SprintModel::query()->updateOrCreate(
                ['id' => $sprint->getId()->value],
                $data
            );

            foreach ($sprint->flushEvents() as $event) {
                event($event);
            }
        });
    }

    public function findById(SprintId $id): ?Sprint
    {
        $model = SprintModel::query()->find($id->value);
        return $model ? SprintMapper::toDomain($model) : null;
    }

    public function hasActiveSprint(\App\Modules\Workload\Domain\ValueObjects\ProjectId $projectId): bool
    {
        return SprintModel::query()
            ->where('project_id', $projectId->value)
            ->where('state', 'ACTIVE')
            ->exists();
    }
}
