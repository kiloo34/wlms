<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\ValueObjects;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class WorkspaceId
{
    public function __construct(public readonly string $value)
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid Workspace ID format.');
        }
    }
}
