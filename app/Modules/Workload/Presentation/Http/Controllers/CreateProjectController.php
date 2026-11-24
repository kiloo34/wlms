<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\CreateProjectInput;
use App\Modules\Workload\Application\Services\WorkSchedule;
use App\Modules\Workload\Application\UseCases\CreateProjectUseCase;
use App\Modules\Workload\Presentation\Http\Requests\CreateProjectHttpRequest;
use Illuminate\Http\JsonResponse;

final class CreateProjectController
{
    public function __construct(
        private readonly CreateProjectUseCase $useCase
    ) {}

    public function __invoke(CreateProjectHttpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $leadId = isset($validated['lead_id']) && $validated['lead_id'] !== ''
            ? (string) $validated['lead_id']
            : $request->getActorId();

        $startDate = WorkSchedule::toDateString($validated['start_date'] ?? null);
        $endDate = WorkSchedule::toDateString($validated['end_date'] ?? null)
            ?? WorkSchedule::endDateFromDuration($startDate, isset($validated['duration_days']) ? (int) $validated['duration_days'] : null);

        $input = new CreateProjectInput(
            projectId: $request->getIdempotencyKey(),
            workspaceId: $validated['workspace_id'],
            key: $validated['key'],
            name: $validated['name'],
            description: $validated['description'] ?? null,
            leadId: $leadId,
            actorUserId: $request->getActorId(),
            priorityId: $validated['priority_id'] ?? null,
            startDate: $startDate,
            endDate: $endDate,
        );

        $output = $this->useCase->execute($input);

        return response()->json([
            'data' => [
                'id' => $output->id,
                'workspace_id' => $output->workspaceId,
                'key' => $output->key,
                'name' => $output->name,
                'status' => $output->status,
                'lead_id' => $output->leadId !== null ? (is_numeric($output->leadId) ? (int) $output->leadId : $output->leadId) : null,
                'priority_id' => $output->priorityId,
                'description' => $output->description,
                'start_date' => $output->startDate,
                'end_date' => $output->endDate,
                'duration_days' => WorkSchedule::durationDays($output->startDate, $output->endDate),
            ],
        ], 201);
    }
}
