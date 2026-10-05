<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorklogModel;
use Illuminate\Http\JsonResponse;

final class GetIssueWorklogsController
{
    public function __invoke(string $id): JsonResponse
    {
        $worklogs = WorklogModel::with('author')
            ->where('issue_id', $id)
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json([
            'data' => $worklogs,
        ]);
    }
}
