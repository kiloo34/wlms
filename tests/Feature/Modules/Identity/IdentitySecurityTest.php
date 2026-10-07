<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
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
    
    $this->otherOrgUnitId = Str::uuid()->toString();
    DB::table('org_units')->insert([
        'id' => $this->otherOrgUnitId,
        'org_level_id' => $this->orgLevelId,
        'name' => 'Other Unit',
    ]);

    $this->superAdminRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
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
    
    $this->otherUser = UserModel::factory()->create([
        'org_unit_id' => $this->otherOrgUnitId,
    ]);
});

test('guest cannot access any identity endpoints', function () {
    $endpoints = [
        ['method' => 'GET', 'url' => '/api/rbac/roles'],
        ['method' => 'GET', 'url' => '/api/rbac/users'],
        ['method' => 'GET', 'url' => '/api/org-units'],
        ['method' => 'GET', 'url' => '/api/org-levels'],
        ['method' => 'POST', 'url' => '/api/rbac/roles'],
        ['method' => 'POST', 'url' => '/api/rbac/users'],
    ];

    foreach ($endpoints as $endpoint) {
        $response = $this->json($endpoint['method'], $endpoint['url']);
        $response->assertStatus(401);
    }
});

test('user cannot update other user details (IDOR)', function () {
    $this->actingAs($this->normalUser);
    
    $response = $this->putJson('/api/rbac/users/' . $this->otherUser->id, [
        'name' => 'Hacked Name',
    ]);
    
    $response->assertStatus(403);
});

test('cross-division access is prevented for normal users', function () {
    $this->actingAs($this->normalUser);

    $response = $this->getJson('/api/rbac/users');
    $response->assertStatus(403);
});

test('rbac edge cases - normal user cannot assign roles to another user', function () {
    $this->actingAs($this->normalUser);
    
    $response = $this->postJson('/api/rbac/user-roles/assign', [
        'user_id' => $this->otherUser->id,
        'role_id' => $this->superAdminRole->id,
    ]);
    
    $response->assertStatus(403);
});

test('rbac edge cases - normal user cannot create roles', function () {
    $this->actingAs($this->normalUser);
    
    $response = $this->postJson('/api/rbac/roles', [
        'name' => 'Hacker Role',
        'scope' => 'GLOBAL'
    ]);
    
    $response->assertStatus(403);
});

test('rbac edge cases - superadmin can assign roles across divisions', function () {
    $this->actingAs($this->superAdmin);
    
    $response = $this->postJson('/api/rbac/user-roles/assign', [
        'user_id' => $this->otherUser->id,
        'role_id' => $this->superAdminRole->id,
    ]);
    
    $response->assertSuccessful();
});

test('rbac edge cases - superadmin can view users across divisions', function () {
    $this->actingAs($this->superAdmin);
    
    $response = $this->getJson('/api/rbac/users');
    
    $response->assertSuccessful();
    $response->assertJsonFragment(['id' => $this->otherUser->id]);
});

test('rbac edge cases - superadmin can update other users', function () {
    $this->actingAs($this->superAdmin);
    
    $response = $this->putJson('/api/rbac/users/' . $this->otherUser->id, [
        'name' => 'Updated Name',
    ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('users', [
        'id' => $this->otherUser->id,
        'name' => 'Updated Name'
    ]);
});
