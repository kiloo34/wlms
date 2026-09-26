<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Services\WorkflowEngine;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;

interface WorkflowRepositoryInterface
{
    /** Loads the workflow assigned to a project along with all its transitions */
    public function getEngineForProject(ProjectId $projectId): WorkflowEngine;
    /** Gets the initial (entry) status ID defined in the project's workflow */
    public function getInitialStatusId(ProjectId $projectId): string;

    public function save(\App\Modules\Workload\Domain\Entities\Workflow $workflow): void;
    public function findById(\App\Modules\Workload\Domain\ValueObjects\WorkflowId $id): ?\App\Modules\Workload\Domain\Entities\Workflow;
    public function delete(\App\Modules\Workload\Domain\ValueObjects\WorkflowId $id): void;
    /** @return \App\Modules\Workload\Domain\Entities\Workflow[] */
    public function findAll(): array;
}

