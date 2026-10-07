<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\GetProjectsInput;
use App\Modules\Workload\Application\UseCases\GetProjectsUseCase;
use App\Modules\Workload\Presentation\Http\Requests\GetProjectsHttpRequest;
use App\Modules\Workload\Presentation\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class GetProjectsController extends Controller
{
    public function __construct(
        private readonly GetProjectsUseCase $useCase
    ) {}

    public function __invoke(GetProjectsHttpRequest $request, string $workspaceId): JsonResponse
    {
        $validated = $request->validated();

        $input = new GetProjectsInput(
            actorUserId: (string) $request->user()?->id,
            workspaceId: $workspaceId,
            limit: (int) ($validated['limit'] ?? 50),
            cursor: $validated['cursor'] ?? null
        );

        $projects = $this->useCase->execute($input);

        return response()->json(
            ProjectResource::collection($projects)->resolve(),
            200
        );
    }
}
