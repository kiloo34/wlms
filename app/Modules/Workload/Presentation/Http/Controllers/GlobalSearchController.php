<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\GlobalSearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GlobalSearchController
{
    public function __construct(private readonly GlobalSearchQuery $query) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        return response()->json([
            'data' => $this->query->execute(
                $user->id,
                $user->org_unit_id ? (string) $user->org_unit_id : null,
                (string) ($v['q'] ?? ''),
                (int) ($v['limit'] ?? 10),
            ),
        ]);
    }
}
