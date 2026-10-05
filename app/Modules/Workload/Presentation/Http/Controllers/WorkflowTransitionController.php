<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowTransitionModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WorkflowTransitionController extends Controller
{
    public function store(Request $request, string $workflowId): JsonResponse
    {
        if (! request()->user()->can('manage-rbac')) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'from_status_id' => ['nullable', 'uuid'],
            'to_status_id' => ['required', 'uuid'],
        ]);

        $transition = DB::transaction(function () use ($workflowId, $validated) {
            $workflow = WorkflowModel::findOrFail($workflowId);

            return WorkflowTransitionModel::create([
                'id' => (string) Str::uuid(),
                'workflow_id' => $workflow->id,
                'name' => $validated['name'],
                'from_status_id' => $validated['from_status_id'],
                'to_status_id' => $validated['to_status_id'],
            ]);
        });

        return response()->json($transition, 201);
    }

    public function destroy(string $workflowId, string $transitionId): JsonResponse
    {
        if (! request()->user()->can('manage-rbac')) {
            abort(403);
        }
        DB::transaction(function () use ($workflowId, $transitionId) {
            $transition = WorkflowTransitionModel::where('workflow_id', $workflowId)
                ->where('id', $transitionId)
                ->firstOrFail();

            $transition->delete();
        });

        return response()->json(null, 204);
    }
}
