<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Dynamic schema compatibility for in-memory SQLite
    if (DB::getDriverName() === 'sqlite') {
        if (! Schema::hasTable('org_levels')) {
            Schema::create('org_levels', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->integer('level_order')->default(1);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('org_units')) {
            Schema::create('org_units', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('code')->nullable();
                $table->uuid('level_id')->nullable();
                $table->uuid('parent_id')->nullable();
                $table->timestamps();
            });
        }
    }
});

it('allows authorized superadmin to create dynamic status for kanban list', function () {
    $user = UserModel::factory()->create();

    // Assign Superadmin role
    $role = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Superadmin',
        'display_name' => 'Superadmin',
    ]);
    UserRoleModel::create([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $response = $this->actingAs($user)->postJson('/api/statuses', [
        'name' => 'Testing Review',
        'slug' => 'testing-review',
        'category' => 'IN_PROGRESS',
        'color' => '#10b981',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('name', 'Testing Review');
    $response->assertJsonPath('category', 'IN_PROGRESS');

    expect(StatusModel::where('slug', 'testing-review')->exists())->toBeTrue();
});

it('returns description and dates in get projects api for overview views', function () {
    $user = UserModel::factory()->create();
    $role = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Superadmin',
        'display_name' => 'Superadmin',
    ]);
    UserRoleModel::create([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $workspace = WorkspaceModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Test Workspace',
        'owner_group_id' => (string) Str::uuid(),
        'status' => 'ACTIVE',
    ]);

    ProjectModel::create([
        'id' => (string) Str::uuid(),
        'workspace_id' => $workspace->id,
        'key' => 'TEST',
        'name' => 'Test Project With Details',
        'description' => 'Detailed description for testing',
        'status' => 'ACTIVE',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
    ]);

    $response = $this->actingAs($user)->getJson("/api/workspaces/{$workspace->id}/projects");

    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveCount(1);
    expect($data[0]['description'])->toBe('Detailed description for testing');
    expect($data[0]['start_date'])->toBe('2026-10-01');
    expect($data[0]['end_date'])->toBe('2026-10-31');
});

it('bulk assigns multiple issues atomically via single endpoint', function () {
    $admin = UserModel::factory()->create();
    $assignee = UserModel::factory()->create();
    $role = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Superadmin',
        'display_name' => 'Superadmin',
    ]);
    UserRoleModel::create([
        'user_id' => $admin->id,
        'role_id' => $role->id,
    ]);

    $workspace = WorkspaceModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Bulk Workspace',
        'owner_group_id' => (string) Str::uuid(),
        'status' => 'ACTIVE',
    ]);
    $project = ProjectModel::create([
        'id' => (string) Str::uuid(),
        'workspace_id' => $workspace->id,
        'key' => 'BLK',
        'name' => 'Bulk Project',
        'status' => 'ACTIVE',
    ]);
    $status = StatusModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'To Do',
        'slug' => 'to-do-bulk',
        'category' => 'TODO',
    ]);
    $typeId = (string) Str::uuid();
    DB::table('issue_types')->insert([
        'id' => $typeId,
        'name' => 'Task',
        'slug' => 'task',
        'icon' => 'check',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $priorityId = (string) Str::uuid();
    DB::table('priorities')->insert([
        'id' => $priorityId,
        'name' => 'Medium',
        'slug' => 'medium',
        'level' => 2,
        'color' => '#fbbf24',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $issue1Id = (string) Str::uuid();
    $issue2Id = (string) Str::uuid();
    foreach ([[$issue1Id, 1], [$issue2Id, 2]] as [$id, $num]) {
        DB::table('issues')->insert([
            'id' => $id,
            'project_id' => $project->id,
            'issue_type_id' => $typeId,
            'status_id' => $status->id,
            'priority_id' => $priorityId,
            'number' => $num,
            'title' => "Issue $num",
            'reporter_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $response = $this->actingAs($admin)->postJson('/api/issues/bulk-update', [
        'issue_ids' => [$issue1Id, $issue2Id],
        'assignee_id' => (string) $assignee->id,
    ]);

    $response->assertStatus(200);
    expect(DB::table('issues')->whereIn('id', [$issue1Id, $issue2Id])->pluck('assignee_id')->unique()->all())
        ->toBe([(int) $assignee->id]);
});

it('returns more than 15 issues when limit=500 is passed to workspace issues endpoint', function () {
    $groupId = (string) Str::uuid();
    $user = UserModel::factory()->create(['org_unit_id' => $groupId]);
    $workspace = WorkspaceModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pagination Workspace',
        'owner_group_id' => $groupId,
        'status' => 'ACTIVE',
    ]);
    $project = ProjectModel::create([
        'id' => (string) Str::uuid(),
        'workspace_id' => $workspace->id,
        'key' => 'PAG',
        'name' => 'Pagination Project',
        'status' => 'ACTIVE',
    ]);
    $status = StatusModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'To Do',
        'slug' => 'to-do-pag',
        'category' => 'TODO',
    ]);
    $typeId = (string) Str::uuid();
    DB::table('issue_types')->insert([
        'id' => $typeId,
        'name' => 'Task',
        'slug' => 'task-pag',
        'icon' => 'check',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $priorityId = (string) Str::uuid();
    DB::table('priorities')->insert([
        'id' => $priorityId,
        'name' => 'Medium',
        'slug' => 'medium-pag',
        'level' => 2,
        'color' => '#fbbf24',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rows = [];
    for ($i = 1; $i <= 20; $i++) {
        $rows[] = [
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'issue_type_id' => $typeId,
            'status_id' => $status->id,
            'priority_id' => $priorityId,
            'number' => $i,
            'title' => "Issue $i",
            'reporter_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    DB::table('issues')->insert($rows);

    $response = $this->actingAs($user)->getJson("/api/workspaces/{$workspace->id}/issues?limit=500");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(20);
});

it('saves assignee_id on issue creation when provided and enforces context-aware RBAC on workspace issues and project page', function () {
    $ownerGroupId = (string) Str::uuid();
    $externalGroupId = (string) Str::uuid();

    $creator = UserModel::factory()->create(['org_unit_id' => $ownerGroupId]);
    $assignee = UserModel::factory()->create(['org_unit_id' => $ownerGroupId]);
    $outsider = UserModel::factory()->create(['org_unit_id' => $externalGroupId]);

    $role = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Superadmin',
        'display_name' => 'Superadmin',
    ]);
    UserRoleModel::create([
        'user_id' => $creator->id,
        'role_id' => $role->id,
    ]);

    $workspace = WorkspaceModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Division A Workspace',
        'owner_group_id' => $ownerGroupId,
        'status' => 'ACTIVE',
    ]);
    $workflowId = (string) Str::uuid();
    DB::table('workflows')->insert([
        'id' => $workflowId,
        'name' => 'Default Workflow',
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $project = ProjectModel::create([
        'id' => (string) Str::uuid(),
        'workspace_id' => $workspace->id,
        'workflow_id' => $workflowId,
        'key' => 'DVA',
        'name' => 'Division A Secret Project',
        'status' => 'ACTIVE',
    ]);
    $status = StatusModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'To Do',
        'slug' => 'to-do-dva',
        'category' => 'TODO',
    ]);
    DB::table('workflow_transitions')->insert([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflowId,
        'from_status_id' => null,
        'to_status_id' => $status->id,
        'name' => 'Create',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $typeId = (string) Str::uuid();
    DB::table('issue_types')->insert([
        'id' => $typeId,
        'name' => 'Task',
        'slug' => 'task-dva',
        'icon' => 'check',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $priorityId = (string) Str::uuid();
    DB::table('priorities')->insert([
        'id' => $priorityId,
        'name' => 'High',
        'slug' => 'high-dva',
        'level' => 3,
        'color' => '#ef4444',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $createResponse = $this->actingAs($creator)->postJson('/api/issues', [
        'project_id' => $project->id,
        'title' => 'Assigned on Create',
        'issue_type_id' => $typeId,
        'priority_id' => $priorityId,
        'assignee_id' => (string) $assignee->id,
    ]);

    $createResponse->assertStatus(201);
    expect($createResponse->json('data.assignee.id'))->toBe((int) $assignee->id);

    // Outsider from another division cannot see Division A's issues or open Division A's project page
    $outsiderIssues = $this->actingAs($outsider)->getJson("/api/workspaces/{$workspace->id}/issues");
    $outsiderIssues->assertStatus(200);
    expect($outsiderIssues->json('data'))->toHaveCount(0);

    $this->actingAs($outsider)->get("/projects/{$project->id}")->assertStatus(403);
});

it('allows user with Superadmin role and non-default email to pass WorkspacePolicy', function () {
    $superadmin = UserModel::factory()->create(['email' => 'custom-admin@company.id']);
    $role = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Superadmin',
        'display_name' => 'Superadmin',
    ]);
    UserRoleModel::create([
        'user_id' => $superadmin->id,
        'role_id' => $role->id,
    ]);

    $workspace = WorkspaceModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'External Division Workspace',
        'owner_group_id' => (string) Str::uuid(),
        'status' => 'ACTIVE',
    ]);

    $this->actingAs($superadmin)
        ->getJson("/api/workspaces/{$workspace->id}/members")
        ->assertStatus(200);
});

it('enforces context-scoped permissions without global leakage and protects Superadmin role from deletion', function () {
    $superadmin = UserModel::factory()->create();
    $member = UserModel::factory()->create();

    $superadminRole = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
    ]);
    UserRoleModel::create([
        'user_id' => $superadmin->id,
        'role_id' => $superadminRole->id,
    ]);

    $projectRole = RoleModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'Project Manager',
        'scope' => 'PROJECT',
    ]);
    $permissionId = (string) Str::uuid();
    DB::table('permissions')->insert([
        'id' => $permissionId,
        'name' => 'sprints:manage',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('role_permissions')->insert([
        'role_id' => $projectRole->id,
        'permission_id' => $permissionId,
    ]);

    $projectId = (string) Str::uuid();

    $assignResponse = $this->actingAs($superadmin)->postJson('/api/rbac/user-roles/assign', [
        'user_id' => $member->id,
        'role_id' => $projectRole->id,
        'context_type' => 'project',
        'context_id' => $projectId,
    ]);
    $assignResponse->assertStatus(200);

    $stored = DB::table('user_roles')
        ->where('user_id', $member->id)
        ->where('role_id', $projectRole->id)
        ->first();

    expect($stored->context_type)->toBe('PROJECT');
    expect($member->fresh()->hasPermission('sprints:manage', 'PROJECT', $projectId))->toBeTrue();
    expect($member->fresh()->hasPermission('sprints:manage'))->toBeFalse();

    $deleteResponse = $this->actingAs($superadmin)->deleteJson("/api/rbac/roles/{$superadminRole->id}");
    $deleteResponse->assertStatus(422);

    // Backfill migration test: lowercase row normalized + duplicate lowercase row deduplicated
    $workspaceId = (string) Str::uuid();
    DB::table('user_roles')->insert([
        [
            'id' => (string) Str::uuid(),
            'user_id' => $member->id,
            'role_id' => $projectRole->id,
            'context_type' => 'workspace',
            'context_id' => $workspaceId,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'user_id' => $member->id,
            'role_id' => $projectRole->id,
            'context_type' => 'project',
            'context_id' => $projectId,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $migration = require base_path('database/migrations/2026_10_13_123500_normalize_user_roles_context_type_case.php');
    $migration->up();

    expect(
        DB::table('user_roles')
            ->where('user_id', $member->id)
            ->where('context_id', $workspaceId)
            ->value('context_type')
    )->toBe('WORKSPACE');

    expect(
        DB::table('user_roles')
            ->where('user_id', $member->id)
            ->where('context_id', $projectId)
            ->count()
    )->toBe(1);
});

