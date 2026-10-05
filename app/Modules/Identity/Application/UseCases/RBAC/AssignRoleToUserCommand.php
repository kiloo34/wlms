<?php

namespace App\Modules\Identity\Application\UseCases\RBAC;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignRoleToUserCommand
{
    public function execute(string $userId, string $roleId, ?string $contextType = null, ?string $contextId = null): void
    {
        DB::transaction(function () use ($userId, $roleId, $contextType, $contextId) {
            // Check if already assigned
            $exists = DB::table('user_roles')
                ->where('user_id', $userId)
                ->where('role_id', $roleId)
                ->where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->exists();

            if (! $exists) {
                DB::table('user_roles')->insert([
                    'id' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
