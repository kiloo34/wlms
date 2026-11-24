<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\GetOwnerWorkQuery;
use App\Modules\Workload\Application\Queries\GetProjectReportQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Laporan seluruh project dalam satu layar + rekap & detail owner.
 */
final class ProjectReportController
{
    public function __construct(
        private readonly GetProjectReportQuery $reportQuery,
        private readonly GetOwnerWorkQuery $ownerQuery,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $v = $request->validate([
            'workspace_id' => ['nullable', 'uuid'],
            'include_archived' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'data' => $this->reportQuery->execute(
                $user->id,
                $user->org_unit_id ? (string) $user->org_unit_id : null,
                $v['workspace_id'] ?? null,
                (bool) ($v['include_archived'] ?? false),
            ),
        ]);
    }

    public function owner(Request $request, int $ownerId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $data = $this->ownerQuery->execute(
            $user->id,
            $user->org_unit_id ? (string) $user->org_unit_id : null,
            $ownerId,
        );

        if ($data === null) {
            abort(404, 'Owner not found.');
        }

        return response()->json(['data' => $data]);
    }
}
