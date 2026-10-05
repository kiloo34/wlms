<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Workflow;
use App\Modules\Workload\Domain\Services\WorkflowEngine;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;

interface WorkflowRepositoryInterface
{
    /** Loads the workflow assigned to a project along with all its transitions */
    public function getEngineForProject(ProjectId $projectId): WorkflowEngine;

    /** Gets the initial (entry) status ID defined in the project's workflow */
    public function getInitialStatusId(ProjectId $projectId): string;

    public function save(Workflow $workflow): void;

    public function findById(WorkflowId $id): ?Workflow;

    public function delete(WorkflowId $id): void;

    /** @return Workflow[] */
    public function findAll(): array;
}
