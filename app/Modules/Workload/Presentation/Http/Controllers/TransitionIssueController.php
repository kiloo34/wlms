<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\TransitionIssueInput;
use App\Modules\Workload\Application\UseCases\TransitionIssueStatusUseCase;
use App\Modules\Workload\Domain\Exceptions\InvalidTransitionException;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;
use App\Modules\Workload\Presentation\Http\Requests\TransitionIssueHttpRequest;
use App\Modules\Workload\Presentation\Http\Resources\IssueResource;
use Illuminate\Http\JsonResponse;

final class TransitionIssueController
{
    public function __construct(private readonly TransitionIssueStatusUseCase $useCase) {}

    public function __invoke(TransitionIssueHttpRequest $request, string $id): JsonResponse
    {
        try {
            $output = $this->useCase->execute(new TransitionIssueInput(
                issueId: $id,
                toStatusId: $request->validated('to_status_id'),
                actorUserId: $request->getActorId()
            ));
            $issue = IssueModel::with(['assignee', 'reporter', 'status'])->find($output->id);

            return response()->json(['data' => new IssueResource($issue)]);
        } catch (InvalidTransitionException $e) {
            $fromName = $e->fromStatusId ? (StatusModel::find($e->fromStatusId)->name ?? 'Unknown') : 'Start';
            $toName = $e->toStatusId ? (StatusModel::find($e->toStatusId)->name ?? 'Unknown') : 'Unknown';

            return response()->json([
                'message' => "Transisi tiket dari status '$fromName' ke '$toName' tidak diizinkan oleh sistem Workflow Anda.",
                'code' => 'INVALID_TRANSITION',
            ], 422);
        }
    }
}
