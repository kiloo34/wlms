<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\ValueObjects;

use InvalidArgumentException;

final class AuditLogId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('AuditLogId cannot be empty.');
        }
    }
}
