<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Workload\Domain\Entities\Sprint;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use DateTimeImmutable;
use ReflectionClass;

final class SprintMapper
{
    public static function toDomain(SprintModel $model): Sprint
    {
        $reflection = new ReflectionClass(Sprint::class);
        $sprint = $reflection->newInstanceWithoutConstructor();

        $propertyId = $reflection->getProperty('id');
        $propertyId->setValue($sprint, new SprintId($model->id));

        $propertyProjectId = $reflection->getProperty('projectId');
        $propertyProjectId->setValue($sprint, new ProjectId($model->project_id));

        $propertyName = $reflection->getProperty('name');
        $propertyName->setValue($sprint, $model->name);

        $propertyGoal = $reflection->getProperty('goal');
        $propertyGoal->setValue($sprint, $model->goal);

        $propertyState = $reflection->getProperty('state');
        $propertyState->setValue($sprint, $model->state);

        $propertyStartDate = $reflection->getProperty('startDate');
        $propertyStartDate->setValue($sprint, $model->start_date ? new DateTimeImmutable($model->start_date->toDateTimeString()) : null);

        $propertyEndDate = $reflection->getProperty('endDate');
        $propertyEndDate->setValue($sprint, $model->end_date ? new DateTimeImmutable($model->end_date->toDateTimeString()) : null);

        $propertyCommitted = $reflection->getProperty('committedPoints');
        $propertyCommitted->setValue($sprint, (int) $model->committed_points);

        $propertyCompleted = $reflection->getProperty('completedPoints');
        $propertyCompleted->setValue($sprint, (int) $model->completed_points);

        $propertyCreatedAt = $reflection->getProperty('createdAt');
        $propertyCreatedAt->setValue($sprint, new DateTimeImmutable($model->created_at->toDateTimeString()));

        return $sprint;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Sprint $sprint): array
    {
        return [
            'id' => $sprint->getId()->value,
            'project_id' => $sprint->getProjectId()->value,
            'name' => $sprint->getName(),
            'goal' => $sprint->getGoal(),
            'state' => $sprint->getState(),
            'start_date' => $sprint->getStartDate()?->format('Y-m-d H:i:s'),
            'end_date' => $sprint->getEndDate()?->format('Y-m-d H:i:s'),
            'committed_points' => $sprint->getCommittedPoints(),
            'completed_points' => $sprint->getCompletedPoints(),
        ];
    }
}
