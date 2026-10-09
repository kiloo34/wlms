<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

use JsonSerializable;

final class ProjectAnalyticsOutput implements JsonSerializable
{
    /**
     * @param array<string, mixed> $project
     * @param array<string, mixed> $summary
     * @param array<string, mixed> $leadTime
     * @param array<string, mixed> $cycleTime
     * @param array<int, array<string, mixed>> $cumulativeFlow
     * @param array<int, array<string, mixed>> $issueTypes
     * @param array<int, array<string, mixed>> $priorities
     * @param array<string, mixed> $sprintMetrics
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public readonly array $project,
        public readonly array $summary,
        public readonly array $leadTime,
        public readonly array $cycleTime,
        public readonly array $cumulativeFlow,
        public readonly array $issueTypes,
        public readonly array $priorities,
        public readonly array $sprintMetrics,
        public readonly array $filters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'project' => $this->project,
            'summary' => $this->summary,
            'lead_time' => $this->leadTime,
            'cycle_time' => $this->cycleTime,
            'cumulative_flow' => $this->cumulativeFlow,
            'issue_types' => $this->issueTypes,
            'priorities' => $this->priorities,
            'sprint_metrics' => $this->sprintMetrics,
            'filters' => $this->filters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

