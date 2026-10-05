<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\ValueObjects;

/** Immutable value object representing a single allowed transition in a workflow */
final class WorkflowTransition
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $fromStatusId, // null = initial state
        public readonly string $toStatusId,
        public readonly string $name
    ) {}

    public function canTransitionFrom(?string $currentStatusId): bool
    {
        return $this->fromStatusId === $currentStatusId;
    }
}
