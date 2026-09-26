<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowTransitionModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (!RoleModel::where('name', 'superadmin')->exists()) {
        RoleModel::create(['name' => 'superadmin']);
    }
});

it('denies unauthenticated users from mutating workflows', function () {
    $this->putJson('/api/workflows/123', [])->assertStatus(401);
    $this->deleteJson('/api/workflows/123')->assertStatus(401);
    $this->postJson('/api/workflows/123/transitions', [])->assertStatus(401);
    $this->deleteJson('/api/workflows/123/transitions/456')->assertStatus(401);
    $this->putJson('/api/projects/123', [])->assertStatus(401);
});

it('allows user to manage workflow transitions and update project workflow (Happy Path)', function () {
    // 1. Setup Data
    $user = UserModel::factory()->create();
    $role = RoleModel::where('name', 'superadmin')->first();
    UserRoleModel::create([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $workflow = WorkflowModel::create([
        'name' => 'New Workflow',
        'description' => 'Desc',
        'is_default' => false,
    ]);

    $statusTodo = StatusModel::create([
        'name' => 'To Do',
        'slug' => 'todo',
        'category' => 'TODO'
    ]);

    $statusDone = StatusModel::create([
        'name' => 'Done',
        'slug' => 'done',
        'category' => 'DONE'
    ]);

    $levelId = (string) \Illuminate\Support\Str::uuid();
    $levelId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('org_levels')->insert([
        'id' => $levelId, 'name' => 'Department', 'slug' => 'department', 'depth' => 1, 'can_own_workspace' => true
    ]);

    $groupId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('org_units')->insert([
        'id' => $groupId, 'org_level_id' => $levelId, 'name' => 'IT Group'
    ]);

    $workspaceId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('workspaces')->insert([
        'id' => $workspaceId,
        'owner_group_id' => $groupId,
        'name' => 'My Workspace',
        'status' => 'ACTIVE',
    ]);

    $project = ProjectModel::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'workspace_id' => $workspaceId,
        'name' => 'My Project',
        'key' => 'MP',
        'lead_id' => $user->id,
    ]);

    // 2. Action & Assertions
    $this->actingAs($user, 'sanctum');

    // Create a workflow transition
    $transitionResponse = $this->postJson("/api/workflows/{$workflow->id}/transitions", [
        'name' => 'Finish task',
        'from_status_id' => $statusTodo->id,
        'to_status_id' => $statusDone->id,
    ]);

    $transitionResponse->assertStatus(201);
    $transitionId = $transitionResponse->json('id');
    $this->assertNotNull($transitionId);
    $this->assertDatabaseHas('workflow_transitions', [
        'id' => $transitionId,
        'name' => 'Finish task',
    ]);

    // Delete the workflow transition
    $deleteTransitionResponse = $this->deleteJson("/api/workflows/{$workflow->id}/transitions/{$transitionId}");
    $deleteTransitionResponse->assertStatus(204);
    $this->assertDatabaseMissing('workflow_transitions', [
        'id' => $transitionId,
    ]);

    // Update Project with new workflow_id
    $updateProjectResponse = $this->putJson("/api/projects/{$project->id}", [
        'name' => 'Updated Project',
        'workflow_id' => $workflow->id,
    ]);
    
    $updateProjectResponse->assertStatus(200);
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'workflow_id' => $workflow->id,
    ]);
});
