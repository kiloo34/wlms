<?php

namespace App\Modules\Identity\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. SEED PERMISSIONS
        // ==========================================
        $permissions = [
            ['name' => 'users:view', 'description' => 'View users list'],
            ['name' => 'users:manage', 'description' => 'Create, update, delete users'],
            ['name' => 'roles:view', 'description' => 'View roles list'],
            ['name' => 'roles:manage', 'description' => 'Create, update, delete roles, assign access'],
            ['name' => 'workspaces:view', 'description' => 'View workspaces'],
            ['name' => 'workspaces:manage', 'description' => 'Manage workspaces'],
            ['name' => 'projects:view', 'description' => 'View projects'],
            ['name' => 'projects:manage', 'description' => 'Manage projects'],
            ['name' => 'issues:view', 'description' => 'View issues'],
            ['name' => 'issues:manage', 'description' => 'Manage issues'],
        ];

        $permissionIds = [];
        foreach ($permissions as $perm) {
            $existing = DB::table('permissions')->where('name', $perm['name'])->first();
            if (! $existing) {
                $id = Str::uuid()->toString();
                DB::table('permissions')->insert([
                    'id' => $id,
                    'name' => $perm['name'],
                    'description' => $perm['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $permissionIds[$perm['name']] = $id;
            } else {
                $permissionIds[$perm['name']] = $existing->id;
            }
        }

        // ==========================================
        // 2. SEED MENUS
        // ==========================================
        $menus = [
            [
                'label' => 'Dashboard',
                'key' => 'dashboard',
                'route' => '/dashboard',
                'icon' => 'layout-dashboard',
                'sort_order' => 10,
                'children' => [],
            ],
            [
                'label' => 'Workspaces',
                'key' => 'workspaces',
                'route' => '/workspaces',
                'icon' => 'briefcase',
                'sort_order' => 20,
                'children' => [],
            ],
            [
                'label' => 'Projects',
                'key' => 'projects',
                'route' => '/projects',
                'icon' => 'folder-kanban',
                'sort_order' => 30,
                'children' => [],
            ],
            [
                'label' => 'Issues',
                'key' => 'issues',
                'route' => '/issues',
                'icon' => 'ticket',
                'sort_order' => 40,
                'children' => [],
            ],
            [
                'label' => 'Settings',
                'key' => 'settings',
                'route' => '/settings',
                'icon' => 'settings',
                'sort_order' => 100,
                'children' => [
                    [
                        'label' => 'Profile',
                        'key' => 'settings.profile',
                        'route' => '/settings/profile',
                        'icon' => 'user',
                        'sort_order' => 1,
                    ],
                    [
                        'label' => 'Security',
                        'key' => 'settings.security',
                        'route' => '/settings/security',
                        'icon' => 'shield',
                        'sort_order' => 2,
                    ],
                    [
                        'label' => 'RBAC (Roles)',
                        'key' => 'settings.rbac',
                        'route' => '/settings/rbac',
                        'icon' => 'key',
                        'sort_order' => 3,
                    ],
                ],
            ],
        ];

        $menuIds = [];

        foreach ($menus as $menu) {
            $existing = DB::table('menus')->where('key', $menu['key'])->first();
            if (! $existing) {
                $parentId = Str::uuid()->toString();
                DB::table('menus')->insert([
                    'id' => $parentId,
                    'parent_id' => null,
                    'label' => $menu['label'],
                    'key' => $menu['key'],
                    'route' => $menu['route'],
                    'icon' => $menu['icon'],
                    'sort_order' => $menu['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $menuIds[] = $parentId;
            } else {
                $parentId = $existing->id;
                $menuIds[] = $parentId;
            }

            // Seed children
            if (! empty($menu['children'])) {
                foreach ($menu['children'] as $child) {
                    $existingChild = DB::table('menus')->where('key', $child['key'])->first();
                    if (! $existingChild) {
                        $childId = Str::uuid()->toString();
                        DB::table('menus')->insert([
                            'id' => $childId,
                            'parent_id' => $parentId,
                            'label' => $child['label'],
                            'key' => $child['key'],
                            'route' => $child['route'],
                            'icon' => $child['icon'],
                            'sort_order' => $child['sort_order'],
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $menuIds[] = $childId;
                    } else {
                        $menuIds[] = $existingChild->id;
                    }
                }
            }
        }

        // ==========================================
        // 3. MAP EVERYTHING TO SUPERADMIN
        // ==========================================
        $superadmin = DB::table('roles')->where('name', 'Superadmin')->first();
        if ($superadmin) {
            // Assign all permissions
            foreach ($permissionIds as $permId) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $superadmin->id)
                    ->where('permission_id', $permId)
                    ->exists();
                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $superadmin->id,
                        'permission_id' => $permId,
                    ]);
                }
            }

            // Assign all menus
            foreach ($menuIds as $mId) {
                $exists = DB::table('role_menus')
                    ->where('role_id', $superadmin->id)
                    ->where('menu_id', $mId)
                    ->exists();
                if (! $exists) {
                    DB::table('role_menus')->insert([
                        'role_id' => $superadmin->id,
                        'menu_id' => $mId,
                    ]);
                }
            }
            $this->command->info('Mapped all permissions and menus to Superadmin role.');
        }

        $this->command->info('Menus and Permissions seeded successfully!');
    }
}
