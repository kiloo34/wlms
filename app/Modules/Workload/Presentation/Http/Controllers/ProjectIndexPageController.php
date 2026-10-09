<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\UseCases\GetProjectLookupsQuery;
use App\Modules\Workload\Application\UseCases\GetWorkspaceLookupsQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Response;

final class ProjectIndexPageController extends Controller
{
    public function __construct(
        private readonly GetProjectLookupsQuery $getProjectLookupsQuery,
        private readonly GetWorkspaceLookupsQuery $getWorkspaceLookupsQuery
    ) {}

    public function __invoke(Request $request): Response
    {
        $workspaceId = $request->query('workspace_id');
        $lookups = $workspaceId
            ? $this->getWorkspaceLookupsQuery->execute((string) $workspaceId)
            : $this->getProjectLookupsQuery->getPrioritiesOnly();

        return inertia('Projects/Index', [
            'lookups' => $lookups,
        ]);
    }
}
