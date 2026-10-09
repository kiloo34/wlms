<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\ProjectAnalyticsInput;
use App\Modules\Workload\Application\Queries\GetProjectAnalyticsQuery;
use App\Modules\Workload\Presentation\Http\Requests\GetProjectAnalyticsHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class GetProjectAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetProjectAnalyticsQuery $query
    ) {}

    public function __invoke(GetProjectAnalyticsHttpRequest $request, string $id): JsonResponse
    {
        $projectId = $id !== '' ? $id : (string) ($request->route('id') ?? $request->route('projectId') ?? '');

        $input = new ProjectAnalyticsInput(
            projectId: $projectId,
            dateRange: (string) ($request->input('date_range') ?? '30d'),
            from: $request->input('from') ? (string) $request->input('from') : null,
            to: $request->input('to') ? (string) $request->input('to') : null,
            sprintId: $request->input('sprint_id') ? (string) $request->input('sprint_id') : null,
        );

        $output = $this->query->execute($input);

        return response()->json($output->toArray(), 200);
    }
}
