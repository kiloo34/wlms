<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Repositories;

use App\Modules\Workload\Domain\Entities\Workflow;
use App\Modules\Workload\Domain\Repositories\WorkflowRepositoryInterface;
use App\Modules\Workload\Domain\Services\WorkflowEngine;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;
use App\Modules\Workload\Domain\ValueObjects\WorkflowTransition;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers\WorkflowMapper;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowTransitionModel;
use Exception;
use Illuminate\Support\Facades\DB;

final class EloquentWorkflowRepository implements WorkflowRepositoryInterface
{
    public function getEngineForProject(ProjectId $projectId): WorkflowEngine
    {
        $project = ProjectModel::query()->find($projectId->value);
        if (! $project) {
            throw new Exception('Project not found.');
        }

        // Use project's assigned workflow or fall back to the default
        $workflowId = $project->workflow_id;
        if (! $workflowId) {
            $defaultWorkflow = WorkflowModel::query()->where('is_default', true)->first();
            if (! $defaultWorkflow) {
                throw new Exception('No default workflow configured.');
            }
            $workflowId = $defaultWorkflow->id;
        }

        $rawTransitions = WorkflowTransitionModel::query()
            ->where('workflow_id', $workflowId)
            ->get();

        $transitions = $rawTransitions->map(fn ($t) => new WorkflowTransition(
            $t->id,
            $t->from_status_id,
            $t->to_status_id,
            $t->name
        ))->toArray();

        return new WorkflowEngine($transitions);
    }

    public function getInitialStatusId(ProjectId $projectId): string
    {
        $engine = $this->getEngineForProject($projectId);
        // Initial transitions have from_status_id = NULL
        $initial = $engine->getValidTransitionsFrom(null);
        if (empty($initial)) {
            throw new Exception('No initial status defined in workflow.');
        }

        return $initial[0]->toStatusId;
    }

    public function save(Workflow $workflow): void
    {
        DB::transaction(function () use ($workflow) {
            if ($workflow->isDefault()) {
                WorkflowModel::query()->update(['is_default' => false]);
            }
            WorkflowModel::query()->updateOrCreate(
                ['id' => $workflow->getId()->value],
                WorkflowMapper::toPersistence($workflow)
            );
        });
    }

    public function findById(WorkflowId $id): ?Workflow
    {
        $model = WorkflowModel::query()->find($id->value);

        return $model ? WorkflowMapper::toDomain($model) : null;
    }

    public function delete(WorkflowId $id): void
    {
        DB::transaction(function () use ($id) {
            $model = WorkflowModel::query()->withCount('transitions')->find($id->value);
            if ($model) {
                if ($model->transitions_count > 0) {
                    $model->delete(); // Soft delete
                } else {
                    $model->forceDelete(); // Hard delete
                }
            }
        });
    }

    public function findAll(): array
    {
        $models = WorkflowModel::query()->get();

        return $models->map(fn (WorkflowModel $model) => WorkflowMapper::toDomain($model))->all();
    }
}
