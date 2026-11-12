<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Application\Queries\GetWorkspaceMembersQuery;
use App\Modules\Workload\Application\UseCases\AddWorkspaceMemberUseCase;
use App\Modules\Workload\Application\UseCases\RemoveWorkspaceMemberUseCase;
use App\Modules\Workload\Domain\Enums\WorkspaceMemberRole;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $limit = $request->has('limit') ? (int) $request->query('limit') : null;
        $offset = (int) $request->query('offset', 0);

        $members = $this->getWorkspaceMembersQuery->execute($workspaceId, $limit, $offset);

        return response()->json([
            'data' => $members,
        ], 200);
    }

    // Kandidat undangan: lookups.users hanya berisi member, jadi workspace kosong tidak punya kandidat.
    public function candidates(Request $request, string $workspaceId): JsonResponse
    {
        $workspace = WorkspaceModel::findOrFail($workspaceId);
        if ($request->user()->cannot('update', $workspace)) {
            abort(403, 'Unauthorized action.');
        }

        $users = UserModel::select('id', 'name', 'email')
            ->where('status', 'ACTIVE')
            ->whereNotIn('id', DB::table('workspace_members')->where('workspace_id', $workspaceId)->select('user_id'))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $users], 200);
    }

    public function store(Request $request, string $workspaceId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'role' => ['sometimes', 'filled', 'string', Rule::in(WorkspaceMemberRole::values())],
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

    public function update(Request $request, string $workspaceId, int $userId): JsonResponse
    {
        $workspace = WorkspaceModel::findOrFail($workspaceId);
        if ($request->user()->cannot('update', $workspace)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'role' => ['sometimes', 'filled', 'string', Rule::in(WorkspaceMemberRole::values())],
            'daily_capacity_hours' => ['sometimes', 'filled', 'integer', 'min:0', 'max:24'],
        ]);

        if (isset($validated['role']) && ! in_array($validated['role'], ['admin', 'owner'], true)) {
            $currentMember = DB::table('workspace_members')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $userId)
                ->first();

            if ($currentMember !== null && in_array($currentMember->role, ['admin', 'owner'], true)) {
                $otherAdminsCount = DB::table('workspace_members')
                    ->where('workspace_id', $workspaceId)
                    ->where('user_id', '!=', $userId)
                    ->whereIn('role', ['admin', 'owner'])
                    ->count();

                if ($otherAdminsCount === 0) {
                    throw ValidationException::withMessages([
                        'role' => 'Cannot demote the last remaining admin or owner in the workspace.',
                    ]);
                }
            }
        }

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
