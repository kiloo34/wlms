<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Events;

use DateTimeImmutable;

/**
 * Domain Event: WorkspaceCreated
 *
 * Merepresentasikan fakta bahwa sebuah Workspace baru saja dibuat.
 * Payload event ini sepenuhnya immutable dan menggunakan tipe primitif.
 * TIDAK BOLEH mengandung object Eloquent.
 */
final class WorkspaceCreated
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $ownerGroupId,
        public readonly string $actorId,
        public readonly string $workspaceName,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}
