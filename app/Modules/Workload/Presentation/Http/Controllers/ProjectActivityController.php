<?php

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\GetProjectActivityQuery;
use App\Modules\Workload\Presentation\Http\Resources\ProjectActivityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectActivityController
{
    public function __construct(private readonly GetProjectActivityQuery $query)
    {
    }

    public function index(Request $request, string $projectId): AnonymousResourceCollection
    {
        $limit = (int) $request->query('limit', 50);
        $cursor = $request->query('cursor');

        $activities = $this->query->execute($projectId, $limit, $cursor);

        return ProjectActivityResource::collection($activities);
    }
}
