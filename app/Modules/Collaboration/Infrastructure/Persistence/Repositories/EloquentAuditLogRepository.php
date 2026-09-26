<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Infrastructure\Persistence\Repositories;

use App\Modules\Collaboration\Domain\Entities\AuditLog;
use App\Modules\Collaboration\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Collaboration\Domain\ValueObjects\TargetEntity;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Mappers\AuditLogMapper;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;

final class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function save(AuditLog $log): void
    {
        $model = AuditLogMapper::toEloquent($log);
        $model->save();
    }

    public function findByTarget(TargetEntity $target): array
    {
        $query = AuditLogModel::where('auditable_type', $target->type);
        
        if ($target->id !== null) {
            $query->where('auditable_id', $target->id);
        } else {
            $query->whereNull('auditable_id');
        }

        $models = $query->orderBy('created_at', 'desc')->get();

        return $models->map(fn(AuditLogModel $model) => AuditLogMapper::toDomain($model))->all();
    }
}

