<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Collaboration\Domain\Entities\AuditLog;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class AuditLogMapper
{
    public static function toDomain(AuditLogModel $model): AuditLog
    {
        return AuditLog::reconstruct(
            $model->id,
            $model->actor_id !== null ? (string) $model->actor_id : null,
            $model->auditable_type,
            $model->auditable_id,
            $model->event,
            $model->old_values,
            $model->new_values,
            $model->ip_address,
            $model->user_agent,
            $model->url,
            new DateTimeImmutable($model->created_at->toIso8601String())
        );
    }

    public static function toEloquent(AuditLog $entity): AuditLogModel
    {
        $model = new AuditLogModel;
        $model->id = $entity->id->value;
        /** @var int<0, max>|null $actorId */
        $actorId = $entity->actorId !== null ? max(0, (int) $entity->actorId) : null;
        $model->actor_id = $actorId;
        $model->auditable_type = $entity->target->type;
        $model->auditable_id = $entity->target->id;
        $model->event = $entity->event;
        $model->old_values = $entity->oldValues;
        $model->new_values = $entity->newValues;
        $model->ip_address = $entity->ipAddress;
        $model->user_agent = $entity->userAgent;
        $model->url = $entity->url;
        $model->created_at = CarbonImmutable::instance($entity->createdAt);

        return $model;
    }
}
