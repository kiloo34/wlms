<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Workload\Domain\Entities\Workspace;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use DateTimeImmutable;

final class WorkspaceMapper
{
    /**
     * Hidrasi: mengubah baris Database (Model Eloquent) menjadi Domain Entity.
     * Menggunakan Reflection untuk mem-bypass constructor private/protected
     * dan mempertahankan murninya state Domain Entity.
     */
    public static function toDomain(WorkspaceModel $model): Workspace
    {
        $reflection = new \ReflectionClass(Workspace::class);
        $workspace = $reflection->newInstanceWithoutConstructor();

        // Bind properties manually
        $propId = $reflection->getProperty('id');
        $propId->setAccessible(true);
        $propId->setValue($workspace, new WorkspaceId($model->id));

        $propOwner = $reflection->getProperty('ownerGroupId');
        $propOwner->setAccessible(true);
        $propOwner->setValue($workspace, $model->owner_group_id);

        $propName = $reflection->getProperty('name');
        $propName->setAccessible(true);
        $propName->setValue($workspace, $model->name);

        $propStatus = $reflection->getProperty('status');
        $propStatus->setAccessible(true);
        $propStatus->setValue($workspace, $model->status);

        $propSettings = $reflection->getProperty('settings');
        $propSettings->setAccessible(true);
        $propSettings->setValue($workspace, $model->settings);

        $propCreatedAt = $reflection->getProperty('createdAt');
        $propCreatedAt->setAccessible(true);
        $propCreatedAt->setValue($workspace, $model->created_at ?: new DateTimeImmutable);

        return $workspace;
    }

    /**
     * Serialisasi: mengubah Domain Entity menjadi array untuk di-insert/update ke Database.
     */
    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Workspace $entity): array
    {
        return [
            'id' => $entity->getId()->value,
            'owner_group_id' => $entity->getOwnerGroupId(),
            'name' => $entity->getName(),
            'status' => $entity->getStatus(),
            'settings' => $entity->getSettings() !== null ? json_encode($entity->getSettings()) : null,
            // description dan atribut opsional lainnya ditambahkan di sini bila ada
        ];
    }
}
