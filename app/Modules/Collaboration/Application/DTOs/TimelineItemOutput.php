<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Application\DTOs;

final class TimelineItemOutput
{
    /**
     * @param  string  $type  "comment" or "audit_log"
     * @param  string  $createdAt  ISO 8601 string
     * @param  array<string, mixed>  $payload  Additional context (e.g. comment body, event type, old/new values)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly ?string $actorId,
        public readonly string $createdAt,
        public readonly array $payload
    ) {}
}
