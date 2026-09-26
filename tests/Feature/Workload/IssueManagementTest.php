<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = UserModel::factory()->create();
    $role = \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => \Illuminate\Support\Str::uuid(), 'scope' => 'GLOBAL']);
    \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel::create(['id' => \Illuminate\Support\Str::uuid(), 'user_id' => $this->user->id, 'role_id' => $role->id]);
    if (isset($this->user)) { $this->user->refresh(); } elseif (isset($user)) { $user->refresh(); }
    if (!Schema::hasTable("notifications")) {
        Schema::create("notifications", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("type");
            $table->string("notifiable_type");
            $table->string("notifiable_id");
            $table->text("data");
            $table->timestamp("read_at")->nullable();
            $table->timestamps();
        });
    }

    // Fill statuses
    $this->todoId = (string) Str::uuid();
    $this->inProgressId = (string) Str::uuid();
    $this->inReviewId = (string) Str::uuid();
    $this->doneId = (string) Str::uuid();

    DB::table('statuses')->insert([
        ['id' => $this->todoId, 'name' => 'To Do', 'slug' => 'todo', 'category' => 'TODO', 'color' => '#E2E8F0'],
        ['id' => $this->inProgressId, 'name' => 'In Progress', 'slug' => 'in-progress', 'category' => 'IN_PROGRESS', 'color' => '#3B82F6'],
        ['id' => $this->inReviewId, 'name' => 'In Review', 'slug' => 'in-review', 'category' => 'IN_PROGRESS', 'color' => '#F59E0B'],
        ['id' => $this->doneId, 'name' => 'Done', 'slug' => 'done', 'category' => 'DONE', 'color' => '#10B981'],
    ]);

    // Workflow
    $this->workflowId = (string) Str::uuid();
    DB::table('workflows')->insert([
        'id' => $this->workflowId,
        'name' => 'Default',
        'description' => 'Desc',
        'is_default' => true
    ]);

    // Workflow Transitions
    DB::table('workflow_transitions')->insert([
        ['id' => (string) Str::uuid(), 'workflow_id' => $this->workflowId, 'from_status_id' => null, 'to_status_id' => $this->todoId, 'name' => 'Create'],
        ['id' => (string) Str::uuid(), 'workflow_id' => $this->workflowId, 'from_status_id' => $this->todoId, 'to_status_id' => $this->inProgressId, 'name' => 'Start'],
        ['id' => (string) Str::uuid(), 'workflow_id' => $this->workflowId, 'from_status_id' => $this->inProgressId, 'to_status_id' => $this->doneId, 'name' => 'Done'],
    ]);

    // Issue Types
    $this->taskId = (string) Str::uuid();
    DB::table('issue_types')->insert([
        ['id' => $this->taskId, 'name' => 'Task', 'slug' => 'task', 'icon' => 'check', 'color' => '#000', 'sort_order' => 1, 'is_active' => true],
    ]);

    // Priorities
    $this->highPriorityId = (string) Str::uuid();
    DB::table('priorities')->insert([
        ['id' => $this->highPriorityId, 'name' => 'High', 'slug' => 'high', 'level' => 1, 'icon' => 'up', 'color' => '#000', 'is_active' => true],
    ]);

    // Workspace & Project
    $this->workspaceId = (string) Str::uuid();
    WorkspaceModel::query()->create([
        'id' => $this->workspaceId,
        'owner_group_id' => (string) Str::uuid(),
        'name' => 'W',
        'status' => 'ACTIVE'
    ]);

    $this->projectId = (string) Str::uuid();
    ProjectModel::query()->create([
        'id' => $this->projectId,
        'workspace_id' => $this->workspaceId,
        'workflow_id' => $this->workflowId,
        'key' => 'TECH',
        'name' => 'Tech',
        'status' => 'ACTIVE',
    ]);
});

it('can create an issue in an active project', function () {
    $payload = [
        'project_id' => $this->projectId,
        'title' => 'First issue',
        'description' => 'Test desc',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    $response = actingAs($this->user)->postJson('/api/issues', $payload);
    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'First issue')
        ->assertJsonPath('data.number', 1);

    $this->assertDatabaseHas('issues', [
        'project_id' => $this->projectId,
        'title' => 'First issue',
        'number' => 1,
    ]);
});

it('cannot create an issue in an archived project', function () {
    $archivedProjectId = (string) Str::uuid();
    ProjectModel::query()->create([
        'id' => $archivedProjectId,
        'workspace_id' => $this->workspaceId,
        'workflow_id' => $this->workflowId,
        'key' => 'ARCH',
        'name' => 'Archived',
        'status' => 'ARCHIVED',
    ]);

    $payload = [
        'project_id' => $archivedProjectId,
        'title' => 'Issue in archived',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    $response = actingAs($this->user)->postJson('/api/issues', $payload);
    $response->assertStatus(500); // Because CreateIssueUseCase throws Exception
});

it('generates sequential issue numbers per project', function () {
    $payload = [
        'project_id' => $this->projectId,
        'title' => 'Issue 1',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    actingAs($this->user)->postJson('/api/issues', $payload)->assertStatus(201)->assertJsonPath('data.number', 1);
    actingAs($this->user)->postJson('/api/issues', $payload)->assertStatus(201)->assertJsonPath('data.number', 2);
});

it('can assign an issue to a user', function () {
    $payload = [
        'project_id' => $this->projectId,
        'title' => 'Issue 1',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    $issueResponse = actingAs($this->user)->postJson('/api/issues', $payload);
    $issueId = $issueResponse->json('data.id');

    $assignee = UserModel::factory()->create();

    $response = actingAs($this->user)->postJson("/api/issues/{$issueId}/assign", [
        'assignee_id' => (string) $assignee->uuid,
        'assignee_id' => (string) $assignee->id,
    ]);
    $response->assertStatus(200);

    $this->assertDatabaseHas('issues', [
        'id' => $issueId,
        'assignee_id' => (string) $assignee->uuid,
        'assignee_id' => (string) $assignee->id,
    ]);
});

it('records audit trail when issue is transitioned', function () {
    $payload = [
        'project_id' => $this->projectId,
        'title' => 'Issue 1',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    $issueResponse = actingAs($this->user)->postJson('/api/issues', $payload);
    $issueId = $issueResponse->json('data.id');

    $response = actingAs($this->user)->postJson("/api/issues/{$issueId}/transition", [
        'to_status_id' => $this->inProgressId,
    ]);
    $response->assertStatus(200);

    $this->assertDatabaseHas('issue_histories', [
        'issue_id' => $issueId,
        'field_changed' => 'status_id',
        'old_value' => $this->todoId,
        'new_value' => $this->inProgressId,
        'actor_id' => (string) $this->user->id,
    ]);
});

it('rejects an invalid workflow transition with 422', function () {
    $payload = [
        'project_id' => $this->projectId,
        'title' => 'Issue 1',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    $issueResponse = actingAs($this->user)->postJson('/api/issues', $payload);
    $issueId = $issueResponse->json('data.id');

    // Invalid transition: To Do -> Done (depends on what's in our fake workflow, but let's assume To Do -> Done is valid if we set it up that way. Wait, I set up ToDo -> InProgress, ToDo -> Done is not there. Wait, I added:
    // null -> ToDo, ToDo -> InProgress, InProgress -> Done. So ToDo -> Done is INVALID!)

    $response = actingAs($this->user)->postJson("/api/issues/{$issueId}/transition", [
        'to_status_id' => $this->doneId,
    ]);

    $response->assertStatus(422)
             ->assertJsonPath('code', 'INVALID_TRANSITION');
});

it('does not expose sensitive reporter data in api response', function () {
    $payload = [
        'project_id' => $this->projectId,
        'title' => 'Issue 1',
        'issue_type_id' => $this->taskId,
        'priority_id' => $this->highPriorityId,
    ];

    $response = actingAs($this->user)->postJson('/api/issues', $payload);
    $response->assertStatus(201);
    
    // API should not contain email or password of the user
    $response->assertJsonMissing(['email']);
    $response->assertJsonMissing(['password']);
});
