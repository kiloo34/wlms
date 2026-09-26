<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\UpdateProjectInput;
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

        $input = new UpdateProjectInput(
            projectId: $id,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            leadId: $validated['lead_id'] ?? null,
            workflowId: $validated['workflow_id'] ?? null,
            actorUserId: $request->getActorId(),
            priorityId: $validated['priority_id'] ?? null
        );

        $this->useCase->execute($input);

        return response()->json(['message' => 'Project updated successfully'], 200);
    }
}
