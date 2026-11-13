<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mendaftarkan menu "Dashboard Catalog" dan permission dashboards:* ke production,
 * lalu memetakannya ke role Superadmin. Role lain diberi akses lewat Pengaturan RBAC.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip saat automated test agar tidak mengganggu isolasi fixture test
        if (app()->environment('testing')) {
            return;
        }

        $superadmin = DB::table('roles')->where('name', 'Superadmin')->first();

        $permissions = [
            ['name' => 'dashboards:view', 'description' => 'View Tableau dashboard catalog'],
            ['name' => 'dashboards:create', 'description' => 'Register new Tableau dashboards'],
            ['name' => 'dashboards:edit', 'description' => 'Edit Tableau dashboard catalog entries'],
            ['name' => 'dashboards:delete', 'description' => 'Delete Tableau dashboard catalog entries'],
            ['name' => 'dashboards:manage', 'description' => 'Full access to manage Tableau dashboard catalog'],
        ];

        foreach ($permissions as $perm) {
            $existing = DB::table('permissions')->where('name', $perm['name'])->first();
            $permId = $existing?->id;

            if (! $existing) {
                $permId = Str::uuid()->toString();
                DB::table('permissions')->insert([
                    'id' => $permId,
                    'name' => $perm['name'],
                    'description' => $perm['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($superadmin && $permId) {
                $hasMapping = DB::table('role_permissions')
                    ->where('role_id', $superadmin->id)
                    ->where('permission_id', $permId)
                    ->exists();

                if (! $hasMapping) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $superadmin->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        $menu = DB::table('menus')->where('key', 'dashboards')->first();
        $menuId = $menu?->id;

        if (! $menu) {
            $menuId = Str::uuid()->toString();
            DB::table('menus')->insert([
                'id' => $menuId,
                'parent_id' => null,
                'label' => 'Dashboard Catalog',
                'key' => 'dashboards',
                'route' => '/dashboards',
                'icon' => 'gauge',
                'sort_order' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($superadmin && $menuId) {
            $hasMenu = DB::table('role_menus')
                ->where('role_id', $superadmin->id)
                ->where('menu_id', $menuId)
                ->exists();

            if (! $hasMenu) {
                DB::table('role_menus')->insert([
                    'role_id' => $superadmin->id,
                    'menu_id' => $menuId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Menu & permission dibiarkan agar mapping role aktif tidak ikut terhapus
    }
};
