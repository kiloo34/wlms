<?php
declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Workload\Domain\Entities\Status;
use App\Modules\Workload\Domain\ValueObjects\StatusId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;

final class StatusMapper
{
    public static function toDomain(StatusModel $model): Status
    {
        return new Status(
            new StatusId($model->id),
            $model->name,
            $model->slug,
            $model->category,
            $model->color
        );
    }

    public static function toPersistence(Status $status): array
    {
        return [
            'id' => $status->getId()->value,
            'name' => $status->getName(),
            'slug' => $status->getSlug(),
            'category' => $status->getCategory(),
            'color' => $status->getColor(),
        ];
    }
}

