<?php

namespace App\Modules\Identity\Application\UseCases\RBAC;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Illuminate\Support\Facades\DB;

class UpdateRolePermissionsCommand
{
    /**
     * @param  array<int, string>  $permissionIds
     */
    public function execute(string $roleId, array $permissionIds): void
    {
        DB::transaction(function () use ($roleId, $permissionIds) {
            $role = RoleModel::findOrFail($roleId);
            $role->permissions()->sync($permissionIds);
        });
    }
}
