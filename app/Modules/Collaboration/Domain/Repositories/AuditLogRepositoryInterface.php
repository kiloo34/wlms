<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Domain\Repositories;

use App\Modules\Collaboration\Domain\Entities\AuditLog;
use App\Modules\Collaboration\Domain\ValueObjects\TargetEntity;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $log): void;

    /**
     * @return AuditLog[]
     */
    public function findByTarget(TargetEntity $target): array;
}

