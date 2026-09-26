<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\ArchiveWorkspaceInput;
use App\Modules\Workload\Application\UseCases\ArchiveWorkspaceUseCase;
use App\Modules\Workload\Presentation\Http\Requests\ArchiveWorkspaceHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ArchiveWorkspaceController extends Controller
{
    public function __construct(
        private readonly ArchiveWorkspaceUseCase $useCase
    ) {}

    public function __invoke(ArchiveWorkspaceHttpRequest $request, string $id): JsonResponse
    {
        $input = new ArchiveWorkspaceInput(
            workspaceId: $id,
            actorUserId: $request->getActorId()
        );

        $this->useCase->execute($input);

        return response()->json(['message' => 'Workspace archived successfully'], 200);
    }
}
