<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Modules\Workload\Application\UseCases\GetProjectLookupsQuery;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use Illuminate\Http\Request;

final class ShowProjectPageController extends Controller
{
    public function __construct(
        private readonly GetProjectLookupsQuery $getProjectLookupsQuery
    ) {}

    public function __invoke(Request $request, string $id)
    {
        // Pragmatic CQRS: Read model bypasses Domain Layer
        $projectModel = ProjectModel::findOrFail($id);
        
        $lookups = $this->getProjectLookupsQuery->getLookupsForProject(
            $projectModel->workspace_id,
            $projectModel->workflow_id
        );

        return inertia('Projects/Show', [
            'project' => [
                'id' => (string) $projectModel->id,
                'workspace_id' => (string) $projectModel->workspace_id,
                'name' => $projectModel->name,
                'key' => $projectModel->key,
                'description' => $projectModel->description,
                'priority_id' => $projectModel->priority_id,
                'created_at' => $projectModel->created_at?->toIso8601String(),
                'updated_at' => $projectModel->updated_at?->toIso8601String(),
            ],
            'lookups' => $lookups
        ]);
    }
}
