<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\WorkflowDTO;
use App\Modules\Workload\Application\UseCases\ManageWorkflowUseCase;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel;
use App\Modules\Workload\Presentation\Http\Requests\StoreWorkflowRequest;
use App\Modules\Workload\Presentation\Http\Resources\WorkflowResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class WorkflowController extends Controller
{
    public function __construct(private ManageWorkflowUseCase $useCase) {}

    public function index(): JsonResponse
    {
        $workflows = $this->useCase->getAll();

        return response()->json(WorkflowResource::collection($workflows));
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $dto = new WorkflowDTO(
            $request->validated('name'),
            $request->validated('description'),
            (bool) $request->validated('is_default', false)
        );

        $workflow = $this->useCase->create($dto);

        return response()->json(new WorkflowResource($workflow), 201);
    }

    public function show(string $id): JsonResponse
    {
        if (! request()->user()->can('manage-rbac')) {
            abort(403);
        }
        $workflow = WorkflowModel::with('transitions')->findOrFail($id);

        return response()->json($workflow);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! request()->user()->can('manage-rbac')) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $dto = new WorkflowDTO(
            $validated['name'],
            $validated['description'] ?? null,
            (bool) ($validated['is_default'] ?? false)
        );

        $workflow = $this->useCase->update($id, $dto);

        return response()->json(new WorkflowResource($workflow));
    }

    public function destroy(string $id): JsonResponse
    {
        if (! request()->user()->can('manage-rbac')) {
            abort(403);
        }
        $this->useCase->delete($id);

        return response()->json(null, 204);
    }
}
