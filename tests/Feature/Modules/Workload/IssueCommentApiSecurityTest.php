<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Setup basic DB structure
    $this->levelId = Str::uuid()->toString();
    DB::table('org_levels')->insert([
        'id' => $this->levelId, 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true
    ]);

    $this->groupId = Str::uuid()->toString();
    DB::table('org_units')->insert([
        'id' => $this->groupId, 'org_level_id' => $this->levelId, 'name' => 'IT Group'
    ]);

    // 2. Setup Workspace
    $this->workspaceId = Str::uuid()->toString();
    DB::table('workspaces')->insert([
        'id' => $this->workspaceId,
        'owner_group_id' => $this->groupId,
        'name' => 'Main Workspace',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 3. Setup Workflow
    $this->workflowId = Str::uuid()->toString();
    DB::table('workflows')->insert([
        'id' => $this->workflowId,
        'name' => 'Default Workflow',
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 4. Setup Project (Using Workflow)
    $this->projectId = Str::uuid()->toString();
    DB::table('projects')->insert([
        'id' => $this->projectId,
        'workspace_id' => $this->workspaceId,
        'workflow_id' => $this->workflowId,
        'key' => 'MAIN',
        'name' => 'Main Project',
        'description' => 'A project for testing',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // 5. Setup IssueType, Priority, Status (Dependencies for Issue)
    $this->issueTypeId = Str::uuid()->toString();
    DB::table('issue_types')->insert([
        'id' => $this->issueTypeId,
        'name' => 'Task',
        'slug' => 'task',
        'icon' => 'task-icon',
        'color' => '#ffffff',
        'sort_order' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $this->priorityId = Str::uuid()->toString();
    DB::table('priorities')->insert([
        'id' => $this->priorityId,
        'name' => 'High',
        'slug' => 'high',
        'color' => '#ff0000',
        'level' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->statusId = Str::uuid()->toString();
    DB::table('statuses')->insert([
        'id' => $this->statusId,
        'name' => 'To Do',
        'slug' => 'todo',
        'category' => 'TODO',
        'color' => '#ffffff',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 6. Setup Users
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

    // 7. Setup an Existing Issue for GET/POST comments
    $this->issueId = Str::uuid()->toString();
    DB::table('issues')->insert([
        'id' => $this->issueId,
        'project_id' => $this->projectId,
        'status_id' => $this->statusId,
        'issue_type_id' => $this->issueTypeId,
        'priority_id' => $this->priorityId,
        'reporter_id' => $this->superAdmin->id,
        'number' => 1,
        'title' => 'Initial Issue',
        'description' => 'Initial Description',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 8. Setup an Existing Comment
    $this->commentId = Str::uuid()->toString();
    DB::table('issue_comments')->insert([
        'id' => $this->commentId,
        'issue_id' => $this->issueId,
        'author_id' => $this->superAdmin->id,
        'body' => 'This is an initial comment on the issue.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

});

test('anti-idor: normal user cannot manage issue comments (403 Forbidden)', function () {
    $this->actingAs($this->normalUser);

    // GET /api/issues/{id}/comments
    $this->getJson("/api/issues/{$this->issueId}/comments")
        ->assertStatus(403);

    // POST /api/issues/{id}/comments
    $this->postJson("/api/issues/{$this->issueId}/comments", [
        'body' => 'Hacked comment attempt'
    ])->assertStatus(403);
});

test('superadmin can get and add comments on an issue', function () {
    $this->actingAs($this->superAdmin);

    // Read Comments (GET /api/issues/{id}/comments)
    $listResponse = $this->getJson("/api/issues/{$this->issueId}/comments");
    $listResponse->assertStatus(200);
    
    $listData = $listResponse->json('data');
    $this->assertIsArray($listData);
    $this->assertGreaterThanOrEqual(1, count($listData));
    
    // Create Comment (POST /api/issues/{id}/comments)
    $createResponse = $this->postJson("/api/issues/{$this->issueId}/comments", [
        'body' => 'New comment by superadmin'
    ]);
    
    $createResponse->assertStatus(201);
    
    $createdData = $createResponse->json('data');
    $this->assertNotNull($createdData['id'] ?? null);
    $this->assertEquals('New comment by superadmin', $createdData['body']);

    // Ensure database has the newly created comment
    $this->assertDatabaseHas('issue_comments', [
        'id' => $createdData['id'],
        'issue_id' => $this->issueId,
        'author_id' => $this->superAdmin->id,
        'body' => 'New comment by superadmin',
    ]);
});

test('validation: missing body returns 422 Unprocessable Entity', function () {
    $this->actingAs($this->superAdmin);

    // Missing body
    $response = $this->postJson("/api/issues/{$this->issueId}/comments", []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['body']);
});

test('unauthenticated user cannot access issue comments', function () {
    $this->getJson("/api/issues/{$this->issueId}/comments")
        ->assertStatus(401);

    $this->postJson("/api/issues/{$this->issueId}/comments", [
        'body' => 'Anonymous comment'
    ])->assertStatus(401);
});
