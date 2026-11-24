<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Direktori user aktif untuk memilih owner project / owner tugas.
 */
final class PeopleDirectoryController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        $people = DB::table('users')
            ->leftJoin('org_units', 'org_units.id', '=', 'users.org_unit_id')
            ->where('users.status', 'ACTIVE')
            ->orderBy('users.name')
            ->limit(2000)
            ->get(['users.id', 'users.name', 'users.email', 'users.employee_id', 'org_units.name as org_unit_name'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'email' => (string) $row->email,
                'employee_id' => $row->employee_id,
                'org_unit_name' => $row->org_unit_name,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $people]);
    }
}
