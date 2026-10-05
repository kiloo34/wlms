<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\Entities;

use App\Modules\Collaboration\Domain\ValueObjects\AuditLogId;
use App\Modules\Collaboration\Domain\ValueObjects\TargetEntity;
use DateTimeImmutable;

final class AuditLog
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function __construct(
        public readonly AuditLogId $id,
        public readonly ?string $actorId,
        public readonly TargetEntity $target,
        public readonly string $event,
        public readonly ?array $oldValues,
        public readonly ?array $newValues,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?string $url,
        public readonly DateTimeImmutable $createdAt
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function create(
        AuditLogId $id,
        ?string $actorId,
        TargetEntity $target,
        string $event,
        ?array $oldValues,
        ?array $newValues,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $url,
        DateTimeImmutable $createdAt
    ): self {
        return new self(
            $id,
            $actorId,
            $target,
            $event,
            $oldValues,
            $newValues,
            $ipAddress,
            $userAgent,
            $url,
            $createdAt
        );
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function reconstruct(
        string $id,
        ?string $actorId,
        string $auditableType,
        ?string $auditableId,
        string $event,
        ?array $oldValues,
        ?array $newValues,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $url,
        DateTimeImmutable $createdAt
    ): self {
        return new self(
            new AuditLogId($id),
            $actorId,
            new TargetEntity($auditableType, $auditableId),
            $event,
            $oldValues,
            $newValues,
            $ipAddress,
            $userAgent,
            $url,
            $createdAt
        );
    }
}
