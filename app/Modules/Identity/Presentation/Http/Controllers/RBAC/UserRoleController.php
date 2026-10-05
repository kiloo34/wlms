<?php

namespace App\Modules\Identity\Presentation\Http\Controllers\RBAC;

use App\Modules\Identity\Application\UseCases\RBAC\AssignRoleToUserCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class UserRoleController extends Controller
{
    public function assign(Request $request, AssignRoleToUserCommand $command): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'context_type' => 'nullable|string|in:workspace,project',
            'context_id' => 'nullable|string',
        ]);

        $command->execute(
            $validated['user_id'],
            $validated['role_id'],
            $validated['context_type'] ?? null,
            $validated['context_id'] ?? null
        );

        return response()->json(['message' => 'Role assigned to user successfully']);
    }

    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        DB::table('user_roles')
            ->where('user_id', $validated['user_id'])
            ->where('role_id', $validated['role_id'])
            ->delete();

        return response()->json(['message' => 'Role revoked successfully'], 204);
    }
}
