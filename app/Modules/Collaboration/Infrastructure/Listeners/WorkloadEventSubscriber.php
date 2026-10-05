<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Infrastructure\Listeners;

use App\Modules\Collaboration\Application\DTOs\AuditLogInput;
use App\Modules\Collaboration\Application\UseCases\LogAuditEventUseCase;
use App\Modules\Workload\Domain\Events\IssueAssigned;
use App\Modules\Workload\Domain\Events\IssueCreated;
use App\Modules\Workload\Domain\Events\IssueTransitioned;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class WorkloadEventSubscriber
{
    public function __construct(
        private readonly LogAuditEventUseCase $logAuditUseCase
    ) {}

    public function handleIssueCreated(IssueCreated $event): void
    {
        $this->logAuditUseCase->execute(new AuditLogInput(
            auditLogId: Str::uuid()->toString(),
            auditableType: 'issue',
            auditableId: $event->issueId,
            event: 'created',
            actorId: (string) $event->reporterId,
            oldValues: null,
            newValues: [
                'title' => $event->title,
                'issue_number' => $event->issueNumber,
                'project_id' => $event->projectId,
            ]
        ));
    }

    public function handleIssueTransitioned(IssueTransitioned $event): void
    {
        $this->logAuditUseCase->execute(new AuditLogInput(
            auditLogId: Str::uuid()->toString(),
            auditableType: 'issue',
            auditableId: $event->issueId,
            event: 'transitioned',
            actorId: (string) $event->actorId,
            oldValues: ['status_id' => $event->fromStatusId],
            newValues: ['status_id' => $event->toStatusId]
        ));
    }

    public function handleIssueAssigned(IssueAssigned $event): void
    {
        $this->logAuditUseCase->execute(new AuditLogInput(
            auditLogId: Str::uuid()->toString(),
            auditableType: 'issue',
            auditableId: $event->issueId,
            event: 'assigned',
            actorId: (string) $event->actorId,
            oldValues: null,
            newValues: ['assignee_id' => $event->assigneeId]
        ));
    }

    /**
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            IssueCreated::class => 'handleIssueCreated',
            IssueTransitioned::class => 'handleIssueTransitioned',
            IssueAssigned::class => 'handleIssueAssigned',
        ];
    }
}
