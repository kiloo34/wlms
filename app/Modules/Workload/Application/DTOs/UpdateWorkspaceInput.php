<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class UpdateWorkspaceInput
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $name,
        public readonly ?array $settings,
        public readonly string $actorUserId
    ) {}
}
