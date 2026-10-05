<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\StatusDTO;
use App\Modules\Workload\Application\UseCases\ManageStatusUseCase;
use App\Modules\Workload\Presentation\Http\Requests\StoreStatusRequest;
use App\Modules\Workload\Presentation\Http\Requests\UpdateStatusRequest;
use App\Modules\Workload\Presentation\Http\Resources\StatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class StatusController extends Controller
{
    public function __construct(private ManageStatusUseCase $useCase) {}

    public function index(): JsonResponse
    {
        $statuses = $this->useCase->getAll();

        return response()->json(StatusResource::collection($statuses));
    }

    public function store(StoreStatusRequest $request): JsonResponse
    {
        $dto = new StatusDTO(
            $request->validated('name'),
            $request->validated('slug'),
            $request->validated('category'),
            $request->validated('color')
        );

        $status = $this->useCase->create($dto);

        return response()->json(new StatusResource($status), 201);
    }

    public function update(UpdateStatusRequest $request, string $id): JsonResponse
    {
        $dto = new StatusDTO(
            $request->validated('name'),
            $request->validated('slug'),
            $request->validated('category'),
            $request->validated('color')
        );

        $status = $this->useCase->update($id, $dto);

        return response()->json(new StatusResource($status));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        // Simple auth check similar to requests
        if (! $request->user()->can('manage-rbac')) {
            abort(403);
        }
        $this->useCase->delete($id);

        return response()->json(null, 204);
    }
}
