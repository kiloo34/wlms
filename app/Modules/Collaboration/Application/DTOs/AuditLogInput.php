<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Application\DTOs;

final class AuditLogInput
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function __construct(
        public readonly string $auditLogId,
        public readonly string $auditableType,
        public readonly ?string $auditableId,
        public readonly string $event,
        public readonly ?string $actorId = null,
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $url = null
    ) {}
}
