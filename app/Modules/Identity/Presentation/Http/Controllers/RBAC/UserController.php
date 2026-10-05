<?php

namespace App\Modules\Identity\Presentation\Http\Controllers\RBAC;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = UserModel::select([
            'id',
            'uuid',
            'name',
            'email',
            'org_unit_id',
            'created_at',
            'updated_at',
        ])
            ->with([
                'orgUnit:id,name',
                'userRoles.role:id,name',
            ])
            ->get()
            ->map(fn (UserModel $user) => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'org_unit_id' => $user->org_unit_id,
                'org_unit_name' => $user->orgUnit?->name,
                'roles' => $user->userRoles
                    ->map(fn (UserRoleModel $ur) => $ur->role?->name)
                    ->filter()
                    ->values(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'org_unit_id' => 'nullable|exists:org_units,id',
        ]);

        $user = DB::transaction(function () use ($validated) {
            return UserModel::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'org_unit_id' => $validated['org_unit_id'] ?? null,
            ]);
        });

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = UserModel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$user->id,
            'org_unit_id' => 'nullable|exists:org_units,id',
        ]);

        $user->update($validated);

        return response()->json(['data' => $user]);
    }
}
