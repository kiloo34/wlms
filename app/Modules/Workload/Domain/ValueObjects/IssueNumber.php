<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\ValueObjects;
use InvalidArgumentException;
/** Represents a human-readable issue number like "TECH-001" */
final class IssueNumber
{
    public readonly string $display;
    public function __construct(
        public readonly string $projectKey,
        public readonly int $number
    ) {
        if ($number < 1) throw new InvalidArgumentException("Issue number must be positive.");
        $this->display = strtoupper($projectKey) . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
    public function getValue(): int { return $this->number; }
}
