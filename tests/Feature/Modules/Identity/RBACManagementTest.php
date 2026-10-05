<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

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

test('normal user cannot access rbac endpoints (Anti-IDOR 403)', function () {
    $this->actingAs($this->normalUser);

    $response = $this->getJson('/api/rbac/roles');
    $response->assertStatus(403);
});

test('superadmin can create and view roles', function () {
    $this->actingAs($this->superAdmin);

    // Create
    $response = $this->postJson('/api/rbac/roles', [
        'name' => 'QA Engineer',
        'scope' => 'PROJECT',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'QA Engineer');

    // List
    $listResponse = $this->getJson('/api/rbac/roles');
    $listResponse->assertStatus(200);
    $this->assertGreaterThanOrEqual(3, count($listResponse->json('data'))); // Superadmin, Developer, QA Engineer
});
