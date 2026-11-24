<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\ReferenceDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sumber saran "@" (project & dashboard katalog) dan detail kamus tiap rujukan.
 */
final class ReferenceController
{
    public function __construct(private readonly ReferenceDirectoryQuery $query) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'between:1,30'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', Rule::in(ReferenceDirectoryQuery::TYPES)],
        ]);

        return response()->json([
            'data' => $this->query->search(
                $user->id,
                $user->org_unit_id ? (string) $user->org_unit_id : null,
                (string) ($v['q'] ?? ''),
                (int) ($v['limit'] ?? 8),
                array_values($v['types'] ?? ReferenceDirectoryQuery::TYPES),
            ),
        ]);
    }

    public function show(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $data = $this->query->find(
            $user->id,
            $user->org_unit_id ? (string) $user->org_unit_id : null,
            $type,
            $id,
        );

        if ($data === null) {
            abort(404, 'Reference not found.');
        }

        return response()->json(['data' => $data]);
    }
}
