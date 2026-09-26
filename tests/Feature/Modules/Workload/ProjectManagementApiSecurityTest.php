<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup basic DB structure
    $this->levelId = Str::uuid()->toString();
    DB::table('org_levels')->insert([
        'id' => $this->levelId, 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true
    ]);

    $this->groupId = Str::uuid()->toString();
    DB::table('org_units')->insert([
        'id' => $this->groupId, 'org_level_id' => $this->levelId, 'name' => 'IT Group'
    ]);

    // Setup Workspace
    $this->workspaceId = Str::uuid()->toString();
    DB::table('workspaces')->insert([
        'id' => $this->workspaceId,
        'owner_group_id' => $this->groupId,
        'name' => 'Main Workspace',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Setup Project
    $this->projectId = Str::uuid()->toString();
    DB::table('projects')->insert([
        'id' => $this->projectId,
        'workspace_id' => $this->workspaceId,
        'key' => 'MAIN',
        'name' => 'Main Project',
        'description' => 'A project for testing',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Setup Users
    $this->normalUser = UserModel::factory()->create([
        'org_unit_id' => $this->groupId,
    ]);

    $this->superAdminRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
    ]);

    $this->superAdmin = UserModel::factory()->create([
        'org_unit_id' => $this->groupId,
    ]);

    DB::table('user_roles')->insert([
        'id' => Str::uuid()->toString(),
        'user_id' => $this->superAdmin->id,
        'role_id' => $this->superAdminRole->id,
    ]);
});

test('anti-idor: normal user cannot access project endpoints (403 Forbidden)', function () {
    $this->actingAs($this->normalUser);

    // POST /api/projects
    $this->postJson('/api/projects', [
        'workspace_id' => $this->workspaceId,
        'key' => 'NEW',
        'name' => 'New Project',
    ])->assertStatus(403);

    // GET /api/workspaces/{workspaceId}/projects
    $this->getJson("/api/workspaces/{$this->workspaceId}/projects")
        ->assertStatus(403);

    // PUT /api/projects/{id}
    $this->putJson("/api/projects/{$this->projectId}", [
        'name' => 'Hacked Project Name',
    ])->assertStatus(403);

    // DELETE /api/projects/{id}
    $this->deleteJson("/api/projects/{$this->projectId}")
        ->assertStatus(403);
});

test('superadmin can manage projects (CRUD) without leaking sensitive data', function () {
    $this->actingAs($this->superAdmin);

    // Create (POST /api/projects)
    $createResponse = $this->postJson('/api/projects', [
        'workspace_id' => $this->workspaceId,
        'key' => 'TEST',
        'name' => 'Test Project',
        'description' => 'Test description',
    ]);
    
    $createResponse->assertStatus(201);
    
    $createdData = $createResponse->json('data');
    $newProjectId = $createdData['id'] ?? null;
    $this->assertNotNull($newProjectId);
    
    // Check no sensitive leak on Create
    expect(array_key_exists('deleted_at', $createdData))->toBeFalse();

    // Read (GET /api/workspaces/{workspaceId}/projects)
    $listResponse = $this->getJson("/api/workspaces/{$this->workspaceId}/projects");
    $listResponse->assertStatus(200);
    $listResponse->assertJsonStructure([
        '*' => [
            'id',
            'workspace_id',
            'key',
            'name',
            'status',
        ],
    ]);
    
    $listData = $listResponse->json();
    $this->assertGreaterThanOrEqual(1, count($listData));
    expect(array_key_exists('deleted_at', $listData[0]))->toBeFalse();

    // Update (PUT /api/projects/{id})
    $updateResponse = $this->putJson("/api/projects/{$this->projectId}", [
        'name' => 'Updated Main Project',
        'description' => 'Updated description',
    ]);
    $updateResponse->assertStatus(200);

    // Check DB
    $this->assertDatabaseHas('projects', [
        'id' => $this->projectId,
        'name' => 'Updated Main Project',
    ]);

    // Delete (DELETE /api/projects/{id})
    $deleteResponse = $this->deleteJson("/api/projects/{$this->projectId}");
    $deleteResponse->assertStatus(200); // Or 204 depending on the implementation

    $this->assertDatabaseHas('projects', [
        'id' => $this->projectId,
        'status' => 'ARCHIVED',
    ]);
});
