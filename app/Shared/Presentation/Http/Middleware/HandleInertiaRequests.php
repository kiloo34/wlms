<?php

namespace App\Shared\Presentation\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        $menus = [];
        $permissions = [];
        $roles = [];

        if ($user) {
            // Eager load roles, permissions, and menus for the user
            $user->load(['userRoles.role.permissions', 'userRoles.role.menus']);
            
            $uniqueMenus = [];
            
            foreach ($user->userRoles as $userRole) {
                if ($userRole->role) {
                    foreach ($userRole->role->permissions as $perm) {
                        $permissions[] = $perm->name;
                    }
                    if ($userRole->role) {
                        $roles[] = $userRole->role->name; // assuming permission has 'name'
                    }
                    foreach ($userRole->role->menus as $menu) {
                        // Hanya tampilkan Top-Level Menu di Sidebar Utama
                        if ($menu->parent_id === null) {
                            // Store by ID to ensure uniqueness across multiple roles
                            // Map database columns to what the React frontend expects
                            $uniqueMenus[$menu->id] = [
                                'title' => $menu->label,
                                'url' => $menu->route,
                                'icon_name' => $menu->icon,
                                'order' => $menu->sort_order ?? 0,
                            ];
                        }
                    }
                }
            }

            // Convert to indexed array and optionally sort by order
            $menus = array_values($uniqueMenus);
            usort($menus, function($a, $b) {
                return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            });
            
            $permissions = array_unique($permissions);
            $roles = array_unique($roles);

            // Cek menggunakan Gate yang sudah didefinisikan
            $isSuperAdmin = \Illuminate\Support\Facades\Gate::allows('manage-rbac');

            if ($isSuperAdmin) {
                // If superadmin has NO menus explicitly assigned, fallback to default full access
                if (empty($menus)) {
                    $menus = [
                        ['title' => 'Workspaces', 'url' => '/workspaces', 'icon_name' => 'workspaces'],
                        ['title' => 'Projects', 'url' => '/projects', 'icon_name' => 'folder'],
                        ['title' => 'Issues', 'url' => '/issues', 'icon_name' => 'issues'],
                        ['title' => 'Users', 'url' => '/users', 'icon_name' => 'users'],
                        ['title' => 'Settings', 'url' => '/settings', 'icon_name' => 'settings'],
                    ];
                }
                // '*' artinya memiliki semua hak akses
                $permissions = ['*'];
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'menus' => $menus,
                    'permissions' => array_values($permissions),
                    'roles' => array_values($roles),
                ]) : null,
            ],
            
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            'translations' => function () {
                $path = base_path('lang/' . app()->getLocale() . '.json');
                return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
            }
        ];

    }
}
