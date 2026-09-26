<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\GetWorkspacesByGroupQuery;
use App\Modules\Workload\Presentation\Http\Resources\WorkspaceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class GetWorkspacesController extends Controller
{
    public function __construct(
        private readonly GetWorkspacesByGroupQuery $query
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // Anti-IDOR: Dapatkan groupId dan userId dari user login, bukan dari input user
        $groupId = (string) $request->user()->org_unit_id;
        $userId = $request->user()->id;

        $limit = (int) $request->query('limit', 50);
        $cursor = $request->query('cursor');

        // CQRS: Memanggil Query object langsung dari Controller (Bypass Domain)
        $workspaces = $this->query->execute($groupId, $userId, $limit, $cursor);

        return response()->json(
            WorkspaceResource::collection($workspaces)->resolve(),
            200
        );
    }
}
