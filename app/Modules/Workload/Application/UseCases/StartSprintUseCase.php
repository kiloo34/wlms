<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Domain\Repositories\SprintRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\SprintId;
use DateTimeImmutable;
use InvalidArgumentException;

final class StartSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $sprintRepository
    ) {}

    public function execute(string $sprintId): void
    {
        $sprint = $this->sprintRepository->findById(new SprintId($sprintId));
        if (! $sprint) {
            throw new InvalidArgumentException('Sprint not found');
        }

        if ($this->sprintRepository->hasActiveSprint($sprint->getProjectId())) {
            throw new InvalidArgumentException('Another sprint is already active in this project');
        }

        $sprint->start(new DateTimeImmutable);
        $this->sprintRepository->save($sprint);
    }
}
