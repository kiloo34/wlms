<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Listeners;

use App\Modules\Workload\Domain\Events\IssueAssigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WriteInAppNotificationListener
{
    public function handle(IssueAssigned $event): void
    {
        // Write in-app notification record for real-time bell icon
        DB::table('notifications')->insert([
            'id'              => (string) Str::uuid(),
            'type'            => 'issue_assigned',
            'notifiable_type' => 'user',
            'notifiable_id'   => $event->assigneeId,
            'data'            => json_encode([
                'issue_number' => $event->issueNumber,
                'issue_id'     => $event->issueId,
                'actor_id'     => $event->actorId,
            ]),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
