<?php

namespace App\Modules\Identity\Infrastructure\Database\Seeders;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RbacSimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Org Level and Org Unit exist
        $orgLevelId = '01923abc-0000-1234-1234-123456789abc';
        if (DB::table('org_levels')->where('id', $orgLevelId)->count() === 0) {
            DB::table('org_levels')->insert([
                'id' => $orgLevelId,
                'slug' => 'group',
                'name' => 'Group',
                'depth' => 1,
                'can_own_workspace' => true,
            ]);
        }

        $orgUnitId = '01923abc-1111-1234-1234-123456789abc';
        if (DB::table('org_units')->where('id', $orgUnitId)->count() === 0) {
            DB::table('org_units')->insert([
                'id' => $orgUnitId,
                'org_level_id' => $orgLevelId,
                'name' => 'Engineering Group',
            ]);
        }

        // 2. Define Roles
        $roles = [
            'superadmin' => ['name' => 'Superadmin', 'scope' => 'global'],
            'workspace_owner' => ['name' => 'Workspace Owner', 'scope' => 'workspace'],
            'project_lead' => ['name' => 'Project Lead', 'scope' => 'project'],
            'developer' => ['name' => 'Developer', 'scope' => 'project'],
            'guest' => ['name' => 'Guest', 'scope' => 'project'],
        ];

        $roleIds = [];
        foreach ($roles as $key => $roleData) {
            $roleId = Str::uuid()->toString();
            $existing = DB::table('roles')->where('name', $roleData['name'])->first();

            if (! $existing) {
                DB::table('roles')->insert([
                    'id' => $roleId,
                    'name' => $roleData['name'],
                    'scope' => $roleData['scope'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $roleIds[$key] = $roleId;
            } else {
                $roleIds[$key] = $existing->id;
            }
        }

        // 3. Define Users
        $usersToSeed = [
            [
                'name' => 'John (Superadmin)',
                'email' => 'superadmin@wlms.com',
                'role_key' => 'superadmin',
            ],
            [
                'name' => 'Sarah (Workspace Owner)',
                'email' => 'owner@wlms.com',
                'role_key' => 'workspace_owner',
            ],
            [
                'name' => 'Mike (Project Lead)',
                'email' => 'lead@wlms.com',
                'role_key' => 'project_lead',
            ],
            [
                'name' => 'Alex (Developer)',
                'email' => 'dev@wlms.com',
                'role_key' => 'developer',
            ],
            [
                'name' => 'Emma (Guest)',
                'email' => 'guest@wlms.com',
                'role_key' => 'guest',
            ],
        ];

        foreach ($usersToSeed as $userData) {
            $user = UserModel::where('email', $userData['email'])->first();

            if (! $user) {
                // If the user doesn't exist, create it
                $user = UserModel::factory()->create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password'), // default password for testing
                    'org_unit_id' => $orgUnitId,
                    'uuid' => Str::uuid()->toString(),
                ]);
            }

            // Assign Role dynamically (For simulation, we assign them globally with null context,
            // except in real scenarios where scope dictates context_type)
            $hasRole = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $roleIds[$userData['role_key']])
                ->exists();

            if (! $hasRole) {
                DB::table('user_roles')->insert([
                    'id' => Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'role_id' => $roleIds[$userData['role_key']],
                    'context_type' => null, // null for global simulation
                    'context_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('RBAC Simulation Users seeded successfully!');
        $this->command->table(
            ['Name', 'Email', 'Role', 'Password'],
            array_map(function ($u) {
                return [$u['name'], $u['email'], $u['role_key'], 'password'];
            }, $usersToSeed)
        );
    }
}
