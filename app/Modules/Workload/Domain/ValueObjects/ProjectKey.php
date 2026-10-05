<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\ValueObjects;

use InvalidArgumentException;

final class ProjectKey
{
    public function __construct(public readonly string $value)
    {
        if (empty($value) || strlen($value) > 10) {
            throw new InvalidArgumentException('ProjectKey must be 1-10 characters long.');
        }
    }
}
