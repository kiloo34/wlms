<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Application\UseCases;

use App\Modules\Collaboration\Application\DTOs\TimelineItemOutput;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Illuminate\Support\Collection;

final class GetIssueTimelineQuery
{
    /**
     * @return TimelineItemOutput[]
     */
    public function execute(string $issueId): array
    {
        // 1. Fetch Comments
        $comments = CommentModel::where('issue_id', $issueId)->get();
        
        // 2. Fetch Audit Logs for this issue
        $auditLogs = AuditLogModel::where('auditable_type', 'issue')
            ->where('auditable_id', $issueId)
            ->get();
            
        // 3. Map to TimelineItemOutput
        $timeline = new Collection();
        
        foreach ($comments as $comment) {
            $timeline->push(new TimelineItemOutput(
                'comment',
                $comment->id,
                $comment->author_id,
                $comment->created_at->format('Y-m-d\TH:i:sP'),
                [
                    'body' => $comment->body,
                    'is_edited' => (bool) $comment->is_edited,
                    'parent_id' => $comment->parent_id,
                ]
            ));
        }
        
        foreach ($auditLogs as $log) {
            $timeline->push(new TimelineItemOutput(
                'audit_log',
                $log->id,
                $log->actor_id,
                $log->created_at->format('Y-m-d\TH:i:sP'),
                [
                    'event' => $log->event,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                ]
            ));
        }
        
        // 4. Sort by date ascending (oldest first)
        return $timeline->sortBy('createdAt')->values()->all();
    }
}

