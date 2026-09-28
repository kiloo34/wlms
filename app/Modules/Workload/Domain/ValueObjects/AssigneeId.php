<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\ValueObjects;
use InvalidArgumentException;
final class AssigneeId
{
    public readonly int $value;

    public function __construct(int|string $value)
    {
        $intValue = (int) $value;
        if ($intValue <= 0) {
            throw new InvalidArgumentException("AssigneeId must be a positive integer.");
        }
        $this->value = $intValue;
    }
}
