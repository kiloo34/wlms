<?php

namespace App\Modules\Identity\Application\UseCases\RBAC;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Illuminate\Support\Facades\DB;

class UpdateRoleMenusCommand
{
    /**
     * @param  array<int, string>  $menuIds
     */
    public function execute(string $roleId, array $menuIds): void
    {
        // Pastikan role exist, jika tidak akan throw exception (ModelNotFoundException)
        RoleModel::findOrFail($roleId);

        DB::transaction(function () use ($roleId, $menuIds) {
            // Hapus relasi lama
            DB::table('role_menus')->where('role_id', $roleId)->delete();

            // Insert relasi baru
            $insertData = array_map(function ($menuId) use ($roleId) {
                return [
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                ];
            }, $menuIds);

            if (! empty($insertData)) {
                DB::table('role_menus')->insert($insertData);
            }
        });
    }
}
