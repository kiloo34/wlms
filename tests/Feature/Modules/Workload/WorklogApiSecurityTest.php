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

    // 7. Setup an Existing Issue
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
        'assignee_id' => $this->normalUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('unauthenticated user cannot submit worklog', function () {
    $response = $this->postJson("/api/issues/{$this->issueId}/worklogs", [
        'time_spent_seconds' => 3600,
        'description' => 'Worked on something',
        'started_at' => now()->toISOString(),
    ]);

    $response->assertStatus(401);
});

test('authenticated user can submit worklog to valid issue', function () {
    $this->actingAs($this->normalUser);

    $response = $this->postJson("/api/issues/{$this->issueId}/worklogs", [
        'time_spent_seconds' => 3600,
        'description' => 'Worked on something',
        'started_at' => now()->toISOString(),
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'message' => 'Work logged successfully',
    ]);

    $this->assertDatabaseHas('worklogs', [
        'issue_id' => $this->issueId,
        'author_id' => $this->normalUser->id,
        'time_spent_seconds' => 3600,
        'description' => 'Worked on something',
    ]);
});

test('worklog payload validation requires time_spent_seconds, description, and started_at', function () {
    $this->actingAs($this->normalUser);

    // Missing all fields
    $response = $this->postJson("/api/issues/{$this->issueId}/worklogs", []);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['time_spent_seconds', 'description', 'started_at']);

    // Invalid types
    $responseInvalid = $this->postJson("/api/issues/{$this->issueId}/worklogs", [
        'time_spent_seconds' => 'not-an-integer',
        'description' => '', // Empty string is not valid for required
        'started_at' => 'not-a-date',
    ]);
    
    $responseInvalid->assertStatus(422)
             ->assertJsonValidationErrors(['time_spent_seconds', 'description', 'started_at']);
});

