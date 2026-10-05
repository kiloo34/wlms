<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\UseCases\GetWorkspaceIssuesQuery;
use App\Modules\Workload\Presentation\Http\Resources\IssueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GetWorkspaceIssuesController
{
    public function __construct(private readonly GetWorkspaceIssuesQuery $query) {}

    public function __invoke(Request $request, string $workspaceId): AnonymousResourceCollection
    {
        $filters = $request->only([
            'status_category',
            'assignee_id',
            'sprint_id',
            'project_id',
            'type_id',
            'priority_id',
            'search',
            'limit',
        ]);

        $issues = $this->query->execute($workspaceId, $filters);

        return IssueResource::collection($issues);
    }
}
