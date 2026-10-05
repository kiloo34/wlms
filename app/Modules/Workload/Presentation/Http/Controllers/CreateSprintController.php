<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\CreateSprintInput;
use App\Modules\Workload\Application\UseCases\CreateSprintUseCase;
use App\Modules\Workload\Presentation\Http\Requests\CreateSprintHttpRequest;
use Illuminate\Http\JsonResponse;

final class CreateSprintController
{
    public function __construct(
        private readonly CreateSprintUseCase $useCase
    ) {}

    public function __invoke(CreateSprintHttpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $input = new CreateSprintInput(
            sprintId: $request->getIdempotencyKey(),
            projectId: $validated['project_id'],
            name: $validated['name'],
            goal: $validated['goal'] ?? null,
            actorUserId: $request->getActorId()
        );

        $output = $this->useCase->execute($input);

        return response()->json([
            'data' => [
                'id' => $output->id,
                'project_id' => $output->projectId,
                'name' => $output->name,
                'state' => $output->state,
            ],
        ], 201);
    }
}
