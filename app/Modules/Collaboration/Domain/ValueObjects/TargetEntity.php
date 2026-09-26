<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Domain\ValueObjects;

use InvalidArgumentException;

final class TargetEntity
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $id = null
    ) {
        if (empty($type)) {
            throw new InvalidArgumentException("TargetEntity type cannot be empty.");
        }
    }
}

