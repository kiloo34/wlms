<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Workload\Domain\Entities\Project;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\ProjectKey;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;
use App\Modules\Workload\Domain\ValueObjects\PriorityId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use DateTimeImmutable;
use ReflectionClass;

final class ProjectMapper
{
    public static function toDomain(ProjectModel $model): Project
    {
        $reflection = new ReflectionClass(Project::class);
        $project = $reflection->newInstanceWithoutConstructor();

        $propertyId = $reflection->getProperty('id');
        $propertyId->setValue($project, new ProjectId($model->id));

        $propertyWorkspaceId = $reflection->getProperty('workspaceId');
        $propertyWorkspaceId->setValue($project, new WorkspaceId($model->workspace_id));

        $propertyKey = $reflection->getProperty('key');
        $propertyKey->setValue($project, new ProjectKey($model->key));

        $propertyName = $reflection->getProperty('name');
        $propertyName->setValue($project, $model->name);

        $propertyDesc = $reflection->getProperty('description');
        $propertyDesc->setValue($project, $model->description);

        $propertyStatus = $reflection->getProperty('status');
        $propertyStatus->setValue($project, $model->status);

        $propertyLeadId = $reflection->getProperty('leadId');
        $propertyLeadId->setValue($project, $model->lead_id);

        $propertyCreatedAt = $reflection->getProperty('createdAt');
        $propertyCreatedAt->setValue($project, new DateTimeImmutable($model->created_at->toDateTimeString()));

        $propertyWorkflowId = $reflection->getProperty('workflowId');
        if ($model->workflow_id) {
            $propertyWorkflowId->setValue($project, new WorkflowId($model->workflow_id));
        } else {
            $propertyWorkflowId->setValue($project, null);
        }

        $propertyPriorityId = $reflection->getProperty('priorityId');
        if ($model->priority_id) {
            $propertyPriorityId->setValue($project, new PriorityId($model->priority_id));
        } else {
            $propertyPriorityId->setValue($project, null);
        }

        return $project;
    }

    public static function toPersistence(Project $project): array
    {
        return [
            'id' => $project->getId()->value,
            'workspace_id' => $project->getWorkspaceId()->value,
            'key' => $project->getKey()->value,
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'status' => $project->getStatus(),
            'lead_id' => $project->getLeadId(),
            'workflow_id' => $project->getWorkflowId()?->value,
            'priority_id' => $project->getPriorityId()?->value,
        ];
    }
}
