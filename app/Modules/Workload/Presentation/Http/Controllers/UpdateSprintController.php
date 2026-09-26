<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdateSprintController
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || ! ($user->hasRole('Workspace Owner') || $user->hasRole('Superadmin'))) {
            abort(403, 'Unauthorized.');
        }

        $v = $request->validate([
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $sprint = SprintModel::findOrFail($id);
        $sprint->update([
            'name' => $v['name'],
            'goal' => $v['goal'] ?? null,
            'start_date' => $v['start_date'] ?? null,
            'end_date' => $v['end_date'] ?? null,
        ]);

        return response()->json([
            'id' => $sprint->id,
            'name' => $sprint->name,
            'goal' => $sprint->goal,
            'state' => $sprint->state,
            'start_date' => $sprint->start_date?->toIso8601String(),
            'end_date' => $sprint->end_date?->toIso8601String(),
        ]);
    }
}
