<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Application\DTOs\CreateIssueInput;
use App\Modules\Workload\Application\UseCases\CreateIssueUseCase;
use App\Modules\Workload\Application\UseCases\DeleteIssueUseCase;
use App\Modules\Workload\Application\UseCases\UpdateIssueUseCase;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Presentation\Http\Requests\CreateIssueHttpRequest;
use App\Modules\Workload\Presentation\Http\Requests\DeleteIssueHttpRequest;
use App\Modules\Workload\Presentation\Http\Requests\UpdateIssueHttpRequest;
use App\Modules\Workload\Presentation\Http\Resources\IssueResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IssueController
{
    public function __construct(
        private readonly CreateIssueUseCase $createUseCase,
        // private readonly UpdateIssueUseCase $updateUseCase,
        private readonly DeleteIssueUseCase $deleteUseCase,
    ) {}

    public function index(Request $request, string $projectId): JsonResponse
    {
        /** @var UserModel|null $user */
        $user = $request->user();
        if (! $user || ! $user->hasPermission('issues:view')) {
            abort(403, 'Unauthorized.');
        }

        $query = IssueModel::query()
            ->with(['assignee', 'reporter', 'status'])
            ->where('project_id', $projectId);

        if ($request->has('status_id')) {
            $query->where('status_id', $request->query('status_id'));
        }

        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->query('assignee_id'));
        }

        $issues = $query->paginate(15);

        return response()->json(IssueResource::collection($issues)->response()->getData(true));
    }

    public function store(CreateIssueHttpRequest $request, string $projectId): JsonResponse
    {
        $v = $request->validated();

        $output = $this->createUseCase->execute(new CreateIssueInput(
            issueId: $request->getIdempotencyKey(),
            projectId: $projectId,
            title: $v['title'],
            description: $v['description'] ?? null,
            issueTypeId: $v['issue_type_id'],
            priorityId: $v['priority_id'],
            sprintId: $v['sprint_id'] ?? null,
            reporterUserId: $request->getActorId(),
            originalEstimateSeconds: $v['original_estimate_seconds'] ?? null
        ));

        // Load eloquent model to return with Resource
        $issue = IssueModel::with(['assignee', 'reporter', 'status'])->find($output->id);

        return response()->json(['data' => new IssueResource($issue)], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var UserModel|null $user */
        $user = $request->user();
        if (! $user || ! $user->hasPermission('issues:view')) {
            abort(403, 'Unauthorized.');
        }

        $issue = IssueModel::with(['assignee', 'reporter', 'status'])->findOrFail($id);

        return response()->json(['data' => new IssueResource($issue)]);
    }

    public function update(UpdateIssueHttpRequest $request, string $id): JsonResponse
    {
        $v = $request->validated();

        // Bypass Domain Events for direct edits (admin/manager edit)
        $issueModel = IssueModel::findOrFail($id);
        $issueModel->update([
            'title' => $v['title'],
            'description' => $v['description'] ?? null,
            'issue_type_id' => $v['issue_type_id'],
            'status_id' => $v['status_id'],
            'priority_id' => $v['priority_id'],
            'assignee_id' => $v['assignee_id'] ?? null,
            'sprint_id' => $v['sprint_id'] ?? null,
            'original_estimate_seconds' => $v['original_estimate_seconds'] ?? null,
            'remaining_estimate_seconds' => array_key_exists('remaining_estimate_seconds', $v) ? $v['remaining_estimate_seconds'] : $issueModel->remaining_estimate_seconds,
        ]);

        // Refresh model with relations
        $issue = IssueModel::with(['assignee', 'reporter', 'status'])->findOrFail($id);

        return response()->json(['data' => new IssueResource($issue)]);
    }

    public function destroy(DeleteIssueHttpRequest $request, string $id): JsonResponse
    {
        $this->deleteUseCase->execute($id);

        return response()->json(null, 204);
    }
}
