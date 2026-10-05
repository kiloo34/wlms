<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\AssignIssueInput;
use App\Modules\Workload\Application\UseCases\AssignIssueUseCase;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Presentation\Http\Requests\AssignIssueHttpRequest;
use App\Modules\Workload\Presentation\Http\Resources\IssueResource;
use Illuminate\Http\JsonResponse;

final class AssignIssueController
{
    public function __construct(private readonly AssignIssueUseCase $useCase) {}

    public function __invoke(AssignIssueHttpRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! ($user->hasRole('Workspace Owner') || $user->hasRole('Superadmin'))) {
            abort(403, 'Unauthorized. Only Workspace Owners can assign issues for now.');
        }

        $output = $this->useCase->execute(new AssignIssueInput(
            issueId: $id,
            assigneeId: $request->validated('assignee_id') !== null ? (string) $request->validated('assignee_id') : null,
            actorUserId: $request->getActorId()
        ));
        $issue = IssueModel::with(['assignee', 'reporter', 'status'])->find($output->id);

        return response()->json(['data' => new IssueResource($issue)]);
    }
}
