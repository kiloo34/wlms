<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\UpdateWorkspaceInput;
use App\Modules\Workload\Application\UseCases\UpdateWorkspaceUseCase;
use App\Modules\Workload\Presentation\Http\Requests\UpdateWorkspaceHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class UpdateWorkspaceController extends Controller
{
    public function __construct(
        private readonly UpdateWorkspaceUseCase $useCase
    ) {}

    public function __invoke(UpdateWorkspaceHttpRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $input = new UpdateWorkspaceInput(
            workspaceId: $id,
            name: $validated['name'],
            settings: $validated['settings'] ?? null,
            actorUserId: $request->getActorId()
        );

        $this->useCase->execute($input);

        return response()->json(['message' => 'Workspace updated successfully'], 200);
    }
}
