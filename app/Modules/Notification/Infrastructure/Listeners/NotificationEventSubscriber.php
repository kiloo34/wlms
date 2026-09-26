<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Collaboration\Domain\Events\CommentAdded;
use App\Modules\Notification\Domain\Events\NewNotification;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use App\Modules\Workload\Domain\Events\IssueAssigned;
use App\Modules\Workload\Domain\Events\IssueTransitioned;
use App\Modules\Workload\Domain\Events\SprintStateChanged;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NotificationEventSubscriber
{
    public function handleIssueAssigned(IssueAssigned $event): void
    {
        if ($event->assigneeId === null) {
            return;
        }

        $userId = $this->resolveUserId($event->assigneeId);
        if (!$userId) return;

        $this->createAndBroadcast(
            userId: (int) $event->assigneeId,
            userId: $userId,
            type: 'issue.assigned',
            data: [
                'issue_id'     => $event->issueId,
                'issue_number' => $event->issueNumber,
                'actor_id'     => $event->actorId,
            ]
        );
    }

    public function handleCommentAdded(CommentAdded $event): void
    {
        $issue = IssueModel::select('reporter_id')->find($event->issueId);

        if ($issue === null || $issue->reporter_id === null) {
            return;
        }

        // Don't notify the author if they commented on their own issue
        if ((string) $issue->reporter_id === $event->authorId) {
            return;
        }

        $userId = $this->resolveUserId($issue->reporter_id);
        if (!$userId) return;

        $this->createAndBroadcast(
            userId: (int) $issue->reporter_id,
            userId: $userId,
            type: 'comment.added',
            data: [
                'issue_id'     => $event->issueId,
                'comment_id'   => $event->commentId,
                'author_id'    => $event->authorId,
                'body_preview' => mb_substr($event->body, 0, 50),
            ]
        );
    }

    public function handleIssueTransitioned(IssueTransitioned $event): void
    {
        $issue = IssueModel::select('assignee_id')->find($event->issueId);

        if ($issue === null || $issue->assignee_id === null) {
            return;
        }

        // Don't notify the actor if they are the assignee
        if ((string) $issue->assignee_id === $event->actorId) {
            return;
        }

        $userId = $this->resolveUserId($issue->assignee_id);
        if (!$userId) return;

        $this->createAndBroadcast(
            userId: (int) $issue->assignee_id,
            userId: $userId,
            type: 'issue.transitioned',
            data: [
                'issue_id'       => $event->issueId,
                'from_status_id' => $event->fromStatusId,
                'to_status_id'   => $event->toStatusId,
                'actor_id'       => $event->actorId,
            ]
        );
    }

    public function handleSprintStateChanged(SprintStateChanged $event): void
    {
        // Resolve workspace_id → members via Sprint → Project → Workspace
        $sprint = SprintModel::with('project.workspace.members:id')->find($event->sprintId);

        if ($sprint === null || $sprint->project === null || $sprint->project->workspace === null) {
            return;
        }

        $memberIds = $sprint->project->workspace->members->pluck('id');

        foreach ($memberIds as $userId) {
            // Don't notify the actor
            if ((string) $userId === $event->actorId) {
                continue;
            }

            $resolvedUserId = $this->resolveUserId($userId);
            if (!$resolvedUserId) continue;

            $this->createAndBroadcast(
                userId: (int) $userId,
                userId: $resolvedUserId,
                type: 'sprint.state_changed',
                data: [
                    'sprint_id'   => $event->sprintId,
                    'project_id'  => $event->projectId,
                    'sprint_name' => $event->sprintName,
                    'new_state'   => $event->newState,
                    'actor_id'    => $event->actorId,
                ]
            );
        }
    }

    private function resolveUserId(string|int $id): ?int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }
        $user = \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel::where('uuid', $id)->first(['id']);
        return $user?->id;
    }

    private function createAndBroadcast(int $userId, string $type, array $data): void
    {
        DB::transaction(function () use ($userId, $type, $data): void {
            $notification = NotificationModel::create([
                'id'      => Str::uuid()->toString(),
                'user_id' => $userId,
                'type'    => $type,
                'data'    => $data,
            ]);

            broadcast(new NewNotification(
                id: $notification->id,
                userId: $userId,
                type: $type,
                data: $data,
                createdAt: $notification->created_at->toIso8601String(),
            ));
        });
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            IssueAssigned::class        => 'handleIssueAssigned',
            CommentAdded::class         => 'handleCommentAdded',
            IssueTransitioned::class    => 'handleIssueTransitioned',
            SprintStateChanged::class   => 'handleSprintStateChanged',
        ];
    }
}

