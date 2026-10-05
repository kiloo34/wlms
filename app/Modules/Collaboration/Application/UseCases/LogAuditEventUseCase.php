<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Application\UseCases;

use App\Modules\Collaboration\Application\DTOs\AuditLogInput;
use App\Modules\Collaboration\Domain\Entities\AuditLog;
use App\Modules\Collaboration\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Collaboration\Domain\ValueObjects\AuditLogId;
use App\Modules\Collaboration\Domain\ValueObjects\TargetEntity;
use DateTimeImmutable;

final class LogAuditEventUseCase
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository
    ) {}

    public function execute(AuditLogInput $input): void
    {
        $log = AuditLog::create(
            new AuditLogId($input->auditLogId),
            $input->actorId,
            new TargetEntity($input->auditableType, $input->auditableId),
            $input->event,
            $input->oldValues,
            $input->newValues,
            $input->ipAddress,
            $input->userAgent,
            $input->url,
            new DateTimeImmutable
        );

        $this->repository->save($log);
    }
}
