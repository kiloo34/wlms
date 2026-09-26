<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\ArchiveProjectInput;
use App\Modules\Workload\Application\UseCases\ArchiveProjectUseCase;
use App\Modules\Workload\Presentation\Http\Requests\ArchiveProjectHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ArchiveProjectController extends Controller
{
    public function __construct(
        private readonly ArchiveProjectUseCase $useCase
    ) {}

    public function __invoke(ArchiveProjectHttpRequest $request, string $id): JsonResponse
    {
        $input = new ArchiveProjectInput(
            projectId: $id,
            actorUserId: $request->getActorId()
        );

        $this->useCase->execute($input);

        return response()->json(['message' => 'Project archived successfully'], 200);
    }
}
