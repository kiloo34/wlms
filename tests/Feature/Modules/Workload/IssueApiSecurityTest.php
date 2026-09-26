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

    // Insert Initial Transition
    DB::table('workflow_transitions')->insert([
        'id' => Str::uuid()->toString(),
        'workflow_id' => $this->workflowId,
        'from_status_id' => null,
        'to_status_id' => $this->statusId,
        'name' => 'Create',
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

    // 7. Setup an Existing Issue for GET/PUT/DELETE
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

});

test('anti-idor: normal user cannot access issue endpoints (403 Forbidden)', function () {
    $this->actingAs($this->normalUser);

    // GET /api/projects/{project_id}/issues
    $this->getJson("/api/projects/{$this->projectId}/issues")
        ->assertStatus(403);

    // POST /api/projects/{project_id}/issues
    $this->postJson("/api/projects/{$this->projectId}/issues", [
        'title' => 'Hacked Task',
        'issue_type_id' => $this->issueTypeId,
        'priority_id' => $this->priorityId,
    ])->assertStatus(403);

    // PUT /api/issues/{id}
    $this->putJson("/api/issues/{$this->issueId}", [
        'title' => 'Hacked Task Edited',
        'priority_id' => $this->priorityId,
    ])->assertStatus(403);

    // DELETE /api/issues/{id}
    $this->deleteJson("/api/issues/{$this->issueId}")
        ->assertStatus(403);
});

test('superadmin can manage issues (CRUD) and get 200/201 response', function () {
    $this->actingAs($this->superAdmin);

    // Create (POST /api/projects/{project_id}/issues)
    $createResponse = $this->postJson("/api/projects/{$this->projectId}/issues", [
        'project_id' => $this->projectId,
        'title' => 'Superadmin Task',
        'description' => 'Description from superadmin',
        'issue_type_id' => $this->issueTypeId,
        'priority_id' => $this->priorityId,
    ]);
    $createResponse->assertStatus(201);
    
    $createdData = $createResponse->json('data');
    $newIssueId = $createdData['id'] ?? null;
    $this->assertNotNull($newIssueId);
    $this->assertEquals('Superadmin Task', $createdData['title']);

    // Read (GET /api/projects/{project_id}/issues)
    $listResponse = $this->getJson("/api/projects/{$this->projectId}/issues");
    $listResponse->assertStatus(200);
    
    $listData = $listResponse->json('data');
    $this->assertGreaterThanOrEqual(2, count($listData)); // Initial + New

    // Read Detail (GET /api/issues/{id})
    $detailResponse = $this->getJson("/api/issues/{$this->issueId}");
    $detailResponse->assertStatus(200);
    $this->assertEquals('Initial Issue', $detailResponse->json('data.title'));

    // Update (PUT /api/issues/{id})
    $updateResponse = $this->putJson("/api/issues/{$this->issueId}", [
        'title' => 'Updated Issue Title',
        'description' => 'Updated description',
        'issue_type_id' => $this->issueTypeId,
        'status_id' => $this->statusId,
        'priority_id' => $this->priorityId,
    ]);
    $updateResponse->assertStatus(200);
    $this->assertEquals('Updated Issue Title', $updateResponse->json('data.title'));

    // Delete (DELETE /api/issues/{id})
    $deleteResponse = $this->deleteJson("/api/issues/{$this->issueId}");
    $deleteResponse->assertStatus(204);

    $this->assertSoftDeleted('issues', [
        'id' => $this->issueId,
    ]);
});

test('validation: missing required fields returns 422 Unprocessable Entity', function () {
    $this->actingAs($this->superAdmin);

    // Missing title and issue_type_id, priority_id
    $response = $this->postJson("/api/projects/{$this->projectId}/issues", [
        'description' => 'No title, no type, no priority',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title', 'issue_type_id', 'priority_id']);
});


