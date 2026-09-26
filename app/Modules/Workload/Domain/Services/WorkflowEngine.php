<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\Services;
use App\Modules\Workload\Domain\Exceptions\InvalidTransitionException;
use App\Modules\Workload\Domain\ValueObjects\WorkflowTransition;
/**
 * Domain Service: validates whether a status transition is allowed
 * within a given workflow configuration.
 */
final class WorkflowEngine
{
    /** @param WorkflowTransition[] $transitions */
    public function __construct(private readonly array $transitions) {}
    public function assertValidTransition(?string $fromStatusId, string $toStatusId): void
    {
        $allowed = array_filter(
            $this->transitions,
            fn(WorkflowTransition $t) => $t->fromStatusId === $fromStatusId && $t->toStatusId === $toStatusId
        );
        if (empty($allowed)) {
            throw new InvalidTransitionException(
                "Transition from '{$fromStatusId}' to '{$toStatusId}' is not permitted by this workflow.",
                $fromStatusId,
                $toStatusId
            );
        }
    }
    /** @return WorkflowTransition[] */
    public function getValidTransitionsFrom(?string $currentStatusId): array
    {
        return array_values(array_filter(
            $this->transitions,
            fn(WorkflowTransition $t) => $t->fromStatusId === $currentStatusId
        ));
    }
}
