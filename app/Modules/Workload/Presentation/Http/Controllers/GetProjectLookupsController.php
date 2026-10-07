<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\UseCases\GetProjectLookupsQuery;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class GetProjectLookupsController extends Controller
{
    public function __construct(
        private readonly GetProjectLookupsQuery $query
    ) {}

    public function __invoke(Request $request, string $projectId): JsonResponse
    {
        // Pragmatic: directly fetch project to get workspaceId and workflowId
        $project = ProjectModel::findOrFail($projectId);
        
        $workspace = WorkspaceModel::findOrFail($project->workspace_id);
        if ($request->user()->cannot('view', $workspace)) {
            abort(403, 'Unauthorized action.');
        }

        $lookups = $this->query->getLookupsForProject($project->workspace_id, $project->workflow_id);

        return response()->json([
            'data' => $lookups,
        ], 200);
    }
}
