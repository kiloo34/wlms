<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

use JsonSerializable;

final class WorkspaceAnalyticsOutput implements JsonSerializable
{
    /**
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $projects
     * @param array<int, array<string, mixed>> $throughput
     * @param array<int, array<string, mixed>> $memberWorkload
     * @param array<string, string> $filters
     */
    public function __construct(
        public readonly array $summary,
        public readonly array $projects,
        public readonly array $throughput,
        public readonly array $memberWorkload,
        public readonly array $filters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'projects' => $this->projects,
            'throughput' => $this->throughput,
            'member_workload' => $this->memberWorkload,
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

