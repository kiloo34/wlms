<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

use App\Modules\Workload\Domain\Entities\Workspace;

final class WorkspaceOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $ownerGroupId,
        // Kita tidak expose deleted_at atau field internal lainnya
    ) {}

    public static function fromDomain(Workspace $workspace): self
    {
        return new self(
            $workspace->getId()->value,
            $workspace->getName(),
            $workspace->getStatus(),
            $workspace->getOwnerGroupId()
        );
    }
}
