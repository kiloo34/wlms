<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\GetProjectLookupsQuery;
use App\Modules\Workload\Application\Queries\GetWorkspaceLookupsQuery;
use App\Modules\Workload\Application\Queries\GetWorkspacesByGroupQuery;
use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use Illuminate\Http\Request;
use Inertia\Response;

final class GlobalIssuesPageController
{
    public function __construct(
        private readonly GetWorkspacesByGroupQuery $workspacesQuery,
        private readonly GetWorkspaceLookupsQuery $workspaceLookupsQuery,
        private readonly GetProjectLookupsQuery $projectLookupsQuery
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $workspaceId = $request->query('workspace_id');

        if ((! is_string($workspaceId) || $workspaceId === '') && $user) {
            $workspaces = $this->workspacesQuery->execute(
                (string) ($user->org_unit_id ?? ''),
                $user->id,
                50,
                null,
                WorkloadAccessChecker::isSuperAdmin($user->id)
            );
            $workspaceId = $workspaces[0]->id ?? null;
        }

        $resolvedWorkspaceId = is_string($workspaceId) && $workspaceId !== '' ? $workspaceId : null;

        $lookups = $resolvedWorkspaceId !== null
            ? $this->workspaceLookupsQuery->execute($resolvedWorkspaceId)
            : $this->projectLookupsQuery->getPrioritiesOnly();

        return inertia('Issues/Index', [
            'workspace_id' => $resolvedWorkspaceId,
            'lookups' => $lookups,
        ]);
    }
}
