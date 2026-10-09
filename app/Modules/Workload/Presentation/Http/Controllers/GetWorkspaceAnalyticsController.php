<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\WorkspaceAnalyticsInput;
use App\Modules\Workload\Application\Queries\GetWorkspaceAnalyticsQuery;
use App\Modules\Workload\Presentation\Http\Requests\GetWorkspaceAnalyticsHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class GetWorkspaceAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetWorkspaceAnalyticsQuery $query
    ) {}

    public function __invoke(GetWorkspaceAnalyticsHttpRequest $request, string $id): JsonResponse
    {
        $workspaceId = $id !== '' ? $id : (string) ($request->route('id') ?? $request->route('workspaceId') ?? '');

        $input = new WorkspaceAnalyticsInput(
            workspaceId: $workspaceId,
            dateRange: (string) ($request->validated('date_range') ?? '30d'),
            from: $request->validated('from') ? (string) $request->validated('from') : null,
            to: $request->validated('to') ? (string) $request->validated('to') : null,
        );

        $output = $this->query->execute($input);

        return response()->json($output->toArray(), 200);
    }
}
