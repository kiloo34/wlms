<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\ValueObjects;
use InvalidArgumentException;
final class PriorityId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new InvalidArgumentException("PriorityId cannot be empty.");
    }
}
