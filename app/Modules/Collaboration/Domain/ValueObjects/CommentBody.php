<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\ValueObjects;

use InvalidArgumentException;

final class CommentBody
{
    public function __construct(public readonly string $value)
    {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('Comment body cannot be empty.');
        }

        if (mb_strlen($trimmed) > 10000) {
            throw new InvalidArgumentException('Comment body is too long. Max 10,000 characters allowed.');
        }
    }
}
