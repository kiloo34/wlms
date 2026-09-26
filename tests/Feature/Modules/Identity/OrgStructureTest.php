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

test('normal user cannot access org structure endpoints (Anti-IDOR 403)', function () {
    $this->actingAs($this->normalUser);

    $responseLevels = $this->getJson('/api/org-levels');
    $responseLevels->assertStatus(403);

    $responseUnits = $this->getJson('/api/org-units');
    $responseUnits->assertStatus(403);
});

test('superadmin can create and view org levels', function () {
    $this->actingAs($this->superAdmin);

    // Create
    $response = $this->postJson('/api/org-levels', [
        'name' => 'Regional Office',
        'slug' => 'regional-office',
        'depth' => 2,
        'is_leaf' => false,
        'can_own_workspace' => true,
        'is_active' => true,
    ]);
    
    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Regional Office');
    $response->assertJsonPath('data.depth', 2);

    // List
    $listResponse = $this->getJson('/api/org-levels');
    $listResponse->assertStatus(200);
    $this->assertGreaterThanOrEqual(2, count($listResponse->json('data'))); // Level 1 (seeded) + Regional Office
});

test('superadmin can create parent and child org units and verifies closure table', function () {
    $this->actingAs($this->superAdmin);

    // Create Parent Unit
    $parentResponse = $this->postJson('/api/org-units', [
        'org_level_id' => $this->orgLevelId,
        'name' => 'HQ',
        'code' => 'HQ-01',
        'is_active' => true,
    ]);
    
    $parentResponse->assertStatus(201);
    $parentId = $parentResponse->json('data.id');

    // Create Child Unit
    $childResponse = $this->postJson('/api/org-units', [
        'parent_id' => $parentId,
        'org_level_id' => $this->orgLevelId, // Assuming same level for simplicity, or we could create a new level
        'name' => 'IT Department',
        'code' => 'IT-01',
        'is_active' => true,
    ]);
    
    $childResponse->assertStatus(201);
    $childId = $childResponse->json('data.id');

    // Verify closure table for child unit
    $this->assertDatabaseHas('org_unit_closures', [
        'ancestor_id' => $parentId,
        'descendant_id' => $childId,
        'depth' => 1,
    ]);

    // Verify self-referencing closure
    $this->assertDatabaseHas('org_unit_closures', [
        'ancestor_id' => $childId,
        'descendant_id' => $childId,
        'depth' => 0,
    ]);
});
