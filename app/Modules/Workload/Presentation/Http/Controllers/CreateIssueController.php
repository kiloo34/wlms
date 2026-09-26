<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Controllers;
use App\Modules\Workload\Application\DTOs\CreateIssueInput;
use App\Modules\Workload\Application\UseCases\CreateIssueUseCase;
use App\Modules\Workload\Presentation\Http\Requests\CreateIssueHttpRequest;
use Illuminate\Http\JsonResponse;
final class CreateIssueController
{
    public function __construct(private readonly CreateIssueUseCase $useCase) {}
    public function __invoke(CreateIssueHttpRequest $request): JsonResponse
    {
        $v = $request->validated();
        $output = $this->useCase->execute(new CreateIssueInput(
            issueId: $request->getIdempotencyKey(),
            projectId: $request->input('project_id') ?? $v['project_id'] ?? '',
            title: $v['title'],
            description: $v['description'] ?? null,
            issueTypeId: $v['issue_type_id'],
            priorityId: $v['priority_id'],
            sprintId: $v['sprint_id'] ?? null,
            reporterUserId: $request->getActorId(),
            originalEstimateSeconds: $v['original_estimate_seconds'] ?? null
        ));
        $issue = \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel::with(['assignee', 'reporter', 'status'])->find($output->id);
        return response()->json(['data' => new \App\Modules\Workload\Presentation\Http\Resources\IssueResource($issue)], 201);
    }
}
