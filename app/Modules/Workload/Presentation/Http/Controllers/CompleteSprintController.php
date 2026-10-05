<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\UseCases\CompleteSprintUseCase;
use App\Modules\Workload\Domain\Events\SprintStateChanged;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class CompleteSprintController
{
    public function __construct(
        private readonly CompleteSprintUseCase $useCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        if (! ($request->user()?->hasRole('Workspace Owner') || $request->user()?->hasRole('Superadmin'))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            $moveToSprintId = $request->input('move_to_sprint_id');
            $actorId = (string) $request->user()->id;

            $this->useCase->execute($id, $actorId, $moveToSprintId);

            $sprint = SprintModel::select('id', 'project_id', 'name')->find($id);
            if ($sprint) {
                event(new SprintStateChanged(
                    sprintId: $sprint->id,
                    projectId: $sprint->project_id,
                    sprintName: $sprint->name,
                    newState: 'completed',
                    actorId: $actorId,
                    occurredAt: new DateTimeImmutable,
                ));
            }

            return response()->json(['message' => 'Sprint completed successfully']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
