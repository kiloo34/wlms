<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\ValueObjects;

use InvalidArgumentException;

final class CommentId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('CommentId cannot be empty.');
        }
    }
}
