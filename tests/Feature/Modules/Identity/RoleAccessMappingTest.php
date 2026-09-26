<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup org level, unit, user
    $this->orgLevelId = Str::uuid()->toString();
    DB::table('org_levels')->insert([
        'id' => $this->orgLevelId,
        'slug' => 'level-1',
        'name' => 'Level 1',
        'depth' => 1,
        'can_own_workspace' => true,
    ]);

    $this->orgUnitId = Str::uuid()->toString();
    DB::table('org_units')->insert([
        'id' => $this->orgUnitId,
        'org_level_id' => $this->orgLevelId,
        'name' => 'Test Unit',
    ]);

    // Roles
    $this->superAdminRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
    ]);

    $this->normalRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Normal Role',
        'scope' => 'PROJECT',
    ]);

    $this->targetRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Target Role',
        'scope' => 'PROJECT',
    ]);

    // Users
    $this->superAdmin = UserModel::factory()->create([
        'org_unit_id' => $this->orgUnitId,
    ]);

    DB::table('user_roles')->insert([
        'id' => Str::uuid()->toString(),
        'user_id' => $this->superAdmin->id,
        'role_id' => $this->superAdminRole->id,
    ]);

    $this->normalUser = UserModel::factory()->create([
        'org_unit_id' => $this->orgUnitId,
    ]);

    DB::table('user_roles')->insert([
        'id' => Str::uuid()->toString(),
        'user_id' => $this->normalUser->id,
        'role_id' => $this->normalRole->id,
    ]);

    // Seed 2 Permissions
    $this->perm1Id = Str::uuid()->toString();
    $this->perm2Id = Str::uuid()->toString();
    DB::table('permissions')->insert([
        ['id' => $this->perm1Id, 'name' => 'users.read', 'description' => 'Read Users', 'created_at' => now(), 'updated_at' => now()],
        ['id' => $this->perm2Id, 'name' => 'users.write', 'description' => 'Write Users', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Seed 2 Menus
    $this->menu1Id = Str::uuid()->toString();
    $this->menu2Id = Str::uuid()->toString();
    DB::table('menus')->insert([
        ['id' => $this->menu1Id, 'label' => 'Dashboard', 'key' => 'dashboard', 'route' => '/dashboard', 'sort_order' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => $this->menu2Id, 'label' => 'Settings', 'key' => 'settings', 'route' => '/settings', 'sort_order' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

test('normal user cannot access mapping endpoints (Anti-IDOR 403)', function () {
    $this->actingAs($this->normalUser);

    // GET /api/rbac/menus
    $response = $this->getJson('/api/rbac/menus');
    $response->assertStatus(403);

    // PUT /api/rbac/roles/{id}/permissions
    $response = $this->putJson('/api/rbac/roles/' . $this->targetRole->id . '/permissions', [
        'permission_ids' => [$this->perm1Id]
    ]);
    $response->assertStatus(403);

    // PUT /api/rbac/roles/{id}/menus
    $response = $this->putJson('/api/rbac/roles/' . $this->targetRole->id . '/menus', [
        'menu_ids' => [$this->menu1Id]
    ]);
    $response->assertStatus(403);
});

test('superadmin can map permissions and menus successfully (Normal Case)', function () {
    $this->actingAs($this->superAdmin);

    // Sync Permissions
    $responsePerm = $this->putJson('/api/rbac/roles/' . $this->targetRole->id . '/permissions', [
        'permission_ids' => [$this->perm1Id, $this->perm2Id]
    ]);
    $responsePerm->assertStatus(200);
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $this->targetRole->id,
        'permission_id' => $this->perm1Id,
    ]);
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $this->targetRole->id,
        'permission_id' => $this->perm2Id,
    ]);

    // Sync Menus
    $responseMenu = $this->putJson('/api/rbac/roles/' . $this->targetRole->id . '/menus', [
        'menu_ids' => [$this->menu1Id, $this->menu2Id]
    ]);
    $responseMenu->assertStatus(200);
    $this->assertDatabaseHas('role_menus', [
        'role_id' => $this->targetRole->id,
        'menu_id' => $this->menu1Id,
    ]);
    $this->assertDatabaseHas('role_menus', [
        'role_id' => $this->targetRole->id,
        'menu_id' => $this->menu2Id,
    ]);

    // Get Menus
    $responseMenus = $this->getJson('/api/rbac/menus');
    $responseMenus->assertStatus(200);
    $responseMenus->assertJsonCount(2, 'data');
});

test('syncing with invalid menu_ids returns 422 Error', function () {
    $this->actingAs($this->superAdmin);

    $fakeMenuId = Str::uuid()->toString();
    $response = $this->putJson('/api/rbac/roles/' . $this->targetRole->id . '/menus', [
        'menu_ids' => [$fakeMenuId]
    ]);
    $response->assertStatus(422);
});
