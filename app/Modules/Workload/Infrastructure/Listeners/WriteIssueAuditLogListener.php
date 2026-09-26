<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Listeners;

use App\Modules\Workload\Domain\Events\IssueTransitioned;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueHistoryModel;
use Illuminate\Support\Str;

final class WriteIssueAuditLogListener
{
    public function handle(IssueTransitioned|\App\Modules\Workload\Domain\Events\IssueAssigned $event): void
    {
        if ($event instanceof IssueTransitioned) {
            IssueHistoryModel::query()->create([
                'id'            => (string) Str::uuid(),
                'issue_id'      => $event->issueId,
                'actor_id'      => $event->actorId,
                'field_changed' => 'status_id',
                'old_value'     => $event->fromStatusId,
                'new_value'     => $event->toStatusId,
                'created_at'    => $event->occurredAt->format('Y-m-d H:i:s'),
            ]);
        } elseif ($event instanceof \App\Modules\Workload\Domain\Events\IssueAssigned) {
            IssueHistoryModel::query()->create([
                'id'            => (string) Str::uuid(),
                'issue_id'      => $event->issueId,
                'actor_id'      => $event->actorId,
                'field_changed' => 'assignee_id',
                'old_value'     => null, // Domain event doesn't store old_value, but we can just say 'null' for now.
                'new_value'     => $event->assigneeId,
                'created_at'    => $event->occurredAt->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
