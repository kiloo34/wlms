<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Repositories;

use App\Modules\Workload\Domain\Entities\Project;
use App\Modules\Workload\Domain\Repositories\ProjectRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\ProjectKey;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers\ProjectMapper;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use Illuminate\Support\Facades\DB;

final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function save(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $data = ProjectMapper::toPersistence($project);

            ProjectModel::query()->updateOrCreate(
                ['id' => $project->getId()->value],
                $data
            );

            foreach ($project->flushEvents() as $event) {
                event($event);
            }
        });
    }

    public function findById(ProjectId $id): ?Project
    {
        $model = ProjectModel::query()->find($id->value);
        return $model ? ProjectMapper::toDomain($model) : null;
    }

    public function existsByKey(ProjectKey $key): bool
    {
        return ProjectModel::query()->where('key', $key->value)->exists();
    }
}
