<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\UpdateProjectInput;
use App\Modules\Workload\Application\Services\WorkSchedule;
use App\Modules\Workload\Application\UseCases\UpdateProjectUseCase;
use App\Modules\Workload\Presentation\Http\Requests\UpdateProjectHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class UpdateProjectController extends Controller
{
    public function __construct(
        private readonly UpdateProjectUseCase $useCase
    ) {}

    public function __invoke(UpdateProjectHttpRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $scheduleProvided = array_key_exists('start_date', $validated)
            || array_key_exists('end_date', $validated)
            || array_key_exists('duration_days', $validated);
        $startDate = WorkSchedule::toDateString($validated['start_date'] ?? null);
        $endDate = WorkSchedule::toDateString($validated['end_date'] ?? null)
            ?? WorkSchedule::endDateFromDuration($startDate, isset($validated['duration_days']) ? (int) $validated['duration_days'] : null);

        $input = new UpdateProjectInput(
            projectId: $id,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            leadId: isset($validated['lead_id']) && $validated['lead_id'] !== '' ? (string) $validated['lead_id'] : null,
            workflowId: $validated['workflow_id'] ?? null,
            actorUserId: $request->getActorId(),
            priorityId: $validated['priority_id'] ?? null,
            leadProvided: array_key_exists('lead_id', $validated),
            scheduleProvided: $scheduleProvided,
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->useCase->execute($input);

        return response()->json(['message' => 'Project updated successfully'], 200);
    }
}
