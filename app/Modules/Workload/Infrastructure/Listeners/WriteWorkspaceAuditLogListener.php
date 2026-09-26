<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Listeners;

use App\Modules\Workload\Domain\Events\WorkspaceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WriteWorkspaceAuditLogListener implements ShouldQueue
{
    /**
     * Menangani side-effect saat Workspace dibuat secara asynchronous.
     */
    public function handle(WorkspaceCreated $event): void
    {
        DB::table('audit_logs')->insert([
            'id' => Str::uuid()->toString(),
            'actor_id' => $event->actorId,
            'auditable_type' => 'workspace',
            'auditable_id' => $event->workspaceId,
            'event' => 'created',
            'new_values' => json_encode(['name' => $event->workspaceName]),
            'ip_address' => request()->ip() ?? '127.0.0.1', // request helper bisa null kalau diproses oleh queue worker CLI
            'created_at' => now(),
        ]);
    }
}
