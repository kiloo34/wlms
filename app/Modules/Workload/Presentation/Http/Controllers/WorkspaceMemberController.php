<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Queries\GetWorkspaceMembersQuery;
use App\Modules\Workload\Application\UseCases\AddWorkspaceMemberUseCase;
use App\Modules\Workload\Application\UseCases\RemoveWorkspaceMemberUseCase;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class WorkspaceMemberController extends Controller
{
    public function __construct(
        private readonly AddWorkspaceMemberUseCase $addWorkspaceMemberUseCase,
        private readonly RemoveWorkspaceMemberUseCase $removeWorkspaceMemberUseCase,
        private readonly GetWorkspaceMembersQuery $getWorkspaceMembersQuery
    ) {}

    public function index(Request $request, string $workspaceId): JsonResponse
    {
        $workspace = WorkspaceModel::findOrFail($workspaceId);
        if ($request->user()->cannot('view', $workspace)) {
            abort(403, 'Unauthorized action.');
        }

        $limit = (int) $request->query('limit', 50);
        $offset = (int) $request->query('offset', 0);

        $members = $this->getWorkspaceMembersQuery->execute($workspaceId, $limit, $offset);

        return response()->json([
            'data' => $members,
        ], 200);
    }

    public function store(Request $request, string $workspaceId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'role' => 'nullable|string|in:viewer,member,admin',
        ]);

        $workspace = WorkspaceModel::findOrFail($workspaceId);
        if ($request->user()->cannot('update', $workspace)) {
            abort(403, 'Unauthorized action.');
        }

        $this->addWorkspaceMemberUseCase->execute(
            $workspaceId,
            (int) $validated['user_id'],
            $validated['role'] ?? 'member'
        );

        return response()->json([
            'message' => 'Member added successfully',
        ], 201);
    }

    public function update(Request $request, string $workspaceId, string $userId): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'sometimes|string',
            'daily_capacity_hours' => 'sometimes|integer|min:0|max:24',
        ]);

        $workspace = WorkspaceModel::findOrFail($workspaceId);

        $updateData = [];
        if (isset($validated['role'])) {
            $updateData['role'] = $validated['role'];
        }
        if (isset($validated['daily_capacity_hours'])) {
            $updateData['daily_capacity_hours'] = $validated['daily_capacity_hours'];
        }

        if (! empty($updateData)) {
            $workspace->members()->updateExistingPivot($userId, $updateData);
        }

        return response()->json(['message' => 'Member updated successfully']);
    }

    public function destroy(Request $request, string $workspaceId, int $userId): JsonResponse
    {
        $workspace = WorkspaceModel::findOrFail($workspaceId);
        if ($request->user()->cannot('update', $workspace)) {
            abort(403, 'Unauthorized action.');
        }

        $this->removeWorkspaceMemberUseCase->execute($workspaceId, $userId);

        return response()->json([
            'message' => 'Member removed successfully',
        ], 200);
    }
}
