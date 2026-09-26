<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Basic setup needed for user creation due to SQLite strict constraints
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

    $this->superAdminRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
    ]);

    $this->normalRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Developer',
        'scope' => 'PROJECT',
    ]);

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
});

test('normal user cannot assign or revoke roles (Anti-IDOR 403)', function () {
    $this->actingAs($this->normalUser);

    $response = $this->postJson('/api/rbac/user-roles/assign', [
        'user_id' => $this->normalUser->id,
        'role_id' => $this->normalRole->id,
    ]);
    
    $response->assertStatus(403);

    $revokeResponse = $this->postJson('/api/rbac/user-roles/revoke', [
        'user_id' => $this->superAdmin->id,
        'role_id' => $this->superAdminRole->id,
    ]);

    $revokeResponse->assertStatus(403);
});

test('superadmin can assign a role to a user successfully', function () {
    $this->actingAs($this->superAdmin);

    $response = $this->postJson('/api/rbac/user-roles/assign', [
        'user_id' => $this->normalUser->id,
        'role_id' => $this->normalRole->id,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Role assigned to user successfully']);

    $this->assertDatabaseHas('user_roles', [
        'user_id' => $this->normalUser->id,
        'role_id' => $this->normalRole->id,
    ]);
});

