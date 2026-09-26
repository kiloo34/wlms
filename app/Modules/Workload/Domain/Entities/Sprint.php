<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Entities;

use App\Modules\Workload\Domain\Events\SprintCreated;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use App\Modules\Workload\Domain\ValueObjects\ProjectId;
use App\Shared\Domain\Traits\HasDomainEvents;
use DateTimeImmutable;
use InvalidArgumentException;

final class Sprint
{
    use HasDomainEvents;

    private function __construct(
        private readonly SprintId $id,
        private readonly ProjectId $projectId,
        private string $name,
        private ?string $goal,
        private string $state,
        private ?DateTimeImmutable $startDate,
        private ?DateTimeImmutable $endDate,
        private int $committedPoints,
        private int $completedPoints,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public static function create(
        SprintId $id,
        ProjectId $projectId,
        string $name,
        ?string $goal,
        string $actorId
    ): self {
        $sprint = new self(
            $id,
            $projectId,
            $name,
            $goal,
            'PENDING', // Valid states: PENDING, ACTIVE, COMPLETED
            null,
            null,
            0,
            0,
            new DateTimeImmutable()
        );

        $sprint->recordEvent(new SprintCreated(
            $id->value,
            $projectId->value,
            $actorId,
            $name,
            new DateTimeImmutable()
        ));

        return $sprint;
    }

    public function start(DateTimeImmutable $startDate, ?DateTimeImmutable $endDate = null): void
    {
        if ($this->state !== 'PENDING') {
            throw new InvalidArgumentException("Only PENDING sprints can be started.");
        }

        $this->startDate = $startDate;
        if ($endDate) {
            $this->endDate = $endDate;
        }
        $this->state = 'ACTIVE';
    }

    public function complete(DateTimeImmutable $endDate): void
    {
        if ($this->state !== 'ACTIVE') {
            throw new InvalidArgumentException("Only ACTIVE sprints can be completed.");
        }

        $this->state = 'COMPLETED';
        $this->endDate = $endDate;
    }

    // Getters
    public function getId(): SprintId { return $this->id; }
    public function getProjectId(): ProjectId { return $this->projectId; }
    public function getName(): string { return $this->name; }
    public function getGoal(): ?string { return $this->goal; }
    public function getState(): string { return $this->state; }
    public function getStartDate(): ?DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): ?DateTimeImmutable { return $this->endDate; }
    public function getCommittedPoints(): int { return $this->committedPoints; }
    public function getCompletedPoints(): int { return $this->completedPoints; }
}
