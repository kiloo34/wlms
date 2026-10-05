<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Listeners;

use App\Modules\Workload\Domain\Events\IssueAssigned;
use Illuminate\Support\Facades\Log;

final class NotifyAssigneeMailListener
{
    public function handle(IssueAssigned $event): void
    {
        // TODO: Dispatch Mailable to assignee email
        // Mail::to($assigneeEmail)->queue(new IssueAssignedMail($event));
        Log::info("MAIL_NOTIFICATION: Issue {$event->issueNumber} assigned to {$event->assigneeId}");
    }
}
