<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Workload\Domain\Entities\Workflow;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel;

final class WorkflowMapper
{
    public static function toDomain(WorkflowModel $model): Workflow
    {
        return new Workflow(
            new WorkflowId($model->id),
            $model->name,
            $model->description,
            (bool) $model->is_default
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Workflow $workflow): array
    {
        return [
            'id' => $workflow->getId()->value,
            'name' => $workflow->getName(),
            'description' => $workflow->getDescription(),
            'is_default' => $workflow->isDefault(),
        ];
    }
}
