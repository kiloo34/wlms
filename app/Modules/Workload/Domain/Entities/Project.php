<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Entities;

use App\Modules\Workload\Domain\Events\ProjectCreated;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Modules\Workload\Domain\ValueObjects\ProjectKey;
use App\Modules\Workload\Domain\ValueObjects\PriorityId;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;
use App\Shared\Domain\Traits\HasDomainEvents;
use DateTimeImmutable;

final class Project
{
    use HasDomainEvents;

    private function __construct(
        private readonly ProjectId $id,
        private readonly WorkspaceId $workspaceId,
        private readonly ProjectKey $key,
        private string $name,
        private ?string $description,
        private string $status,
        private ?string $leadId,
        private readonly DateTimeImmutable $createdAt,
        private ?WorkflowId $workflowId = null,
        private ?PriorityId $priorityId = null
    ) {}

    public static function create(
        ProjectId $id,
        WorkspaceId $workspaceId,
        ProjectKey $key,
        string $name,
        ?string $description,
        ?string $leadId,
        string $actorId,
        ?PriorityId $priorityId = null
    ): self {
        $project = new self(
            $id,
            $workspaceId,
            $key,
            $name,
            $description,
            'ACTIVE',
            $leadId,
            new DateTimeImmutable(),
            null,
            $priorityId
        );

        $project->recordEvent(new ProjectCreated(
            $id->value,
            $workspaceId->value,
            $actorId,
            $key->value,
            $name,
            new DateTimeImmutable()
        ));

        return $project;
    }

    public function archive(string $actorId): void
    {
        $this->status = 'ARCHIVED';
    }

    public function update(string $name, ?string $description, ?string $leadId, ?WorkflowId $workflowId, string $actorId, ?PriorityId $priorityId = null): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->leadId = $leadId;
        $this->priorityId = $priorityId;
        if ($workflowId !== null) {
            $this->workflowId = $workflowId;
        }
    }

    // Getters
    public function getId(): ProjectId { return $this->id; }
    public function getWorkspaceId(): WorkspaceId { return $this->workspaceId; }
    public function getKey(): ProjectKey { return $this->key; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getLeadId(): ?string { return $this->leadId; }
    public function getWorkflowId(): ?WorkflowId { return $this->workflowId; }
    public function getPriorityId(): ?PriorityId { return $this->priorityId; }
}
