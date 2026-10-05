<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can create a sprint in a project', function () {
    $user = UserModel::factory()->create();
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id]);
    if (isset($this->user)) {
        $this->user->refresh();
    } elseif (isset($user)) {
        $user->refresh();
    }
    $workspaceId = (string) Str::uuid();
    $projectId = (string) Str::uuid();

    WorkspaceModel::query()->create([
        'id' => $workspaceId,
        'owner_group_id' => (string) Str::uuid(),
        'name' => 'Tech Dept',
        'status' => 'ACTIVE',
    ]);

    ProjectModel::query()->create([
        'id' => $projectId,
        'workspace_id' => $workspaceId,
        'key' => 'TECH',
        'name' => 'Backend',
        'status' => 'ACTIVE',
    ]);

    $response = actingAs($user)->postJson('/api/sprints', [
        'project_id' => $projectId,
        'name' => 'Sprint 1',
        'goal' => 'Finish the refactor',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Sprint 1')
        ->assertJsonPath('data.project_id', $projectId)
        ->assertJsonPath('data.state', 'PENDING');

    $this->assertDatabaseHas('sprints', [
        'name' => 'Sprint 1',
        'project_id' => $projectId,
    ]);
});

it('cannot create sprint in an archived project', function () {
    $user = UserModel::factory()->create();
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id]);
    if (isset($this->user)) {
        $this->user->refresh();
    } elseif (isset($user)) {
        $user->refresh();
    }
    $workspaceId = (string) Str::uuid();
    $projectId = (string) Str::uuid();

    WorkspaceModel::query()->create([
        'id' => $workspaceId,
        'owner_group_id' => (string) Str::uuid(),
        'name' => 'Tech Dept',
        'status' => 'ACTIVE',
    ]);

    ProjectModel::query()->create([
        'id' => $projectId,
        'workspace_id' => $workspaceId,
        'key' => 'TECH',
        'name' => 'Backend',
        'status' => 'ARCHIVED',
    ]);

    $response = actingAs($user)->postJson('/api/sprints', [
        'project_id' => $projectId,
        'name' => 'Sprint 1',
    ]);

    $response->assertStatus(500); // Because it throws a generic Exception in the Use Case right now
});
