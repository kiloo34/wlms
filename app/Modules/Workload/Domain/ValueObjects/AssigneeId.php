<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\ValueObjects;
use InvalidArgumentException;
final class AssigneeId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new InvalidArgumentException("AssigneeId cannot be empty.");
        if (empty($value)) {
            throw new InvalidArgumentException("AssigneeId cannot be empty.");
        }
        if (!ctype_digit($value)) {
            throw new InvalidArgumentException("AssigneeId must be a valid numeric user ID, got: '{$value}'.");
        }
    }
}
