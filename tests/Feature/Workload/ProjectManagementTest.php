<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Database\Seeders\WorkloadLookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can create a project in a workspace', function () {
    $user = UserModel::factory()->create();
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id]);
    if (isset($this->user)) {
        $this->user->refresh();
    } elseif (isset($user)) {
        $user->refresh();
    }
    $workspaceId = (string) Str::uuid();

    WorkspaceModel::query()->create([
        'id' => $workspaceId,
        'owner_group_id' => (string) Str::uuid(),
        'name' => 'Tech Dept',
        'status' => 'ACTIVE',
    ]);

    \Illuminate\Support\Facades\DB::table('workspace_members')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $user->id,
        'role' => 'admin',
        'created_at' => now(),
    ]);

    $response = actingAs($user)->postJson('/api/projects', [
        'workspace_id' => $workspaceId,
        'key' => 'TECH',
        'name' => 'Backend Rewrite',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.key', 'TECH')
        ->assertJsonPath('data.name', 'Backend Rewrite')
        ->assertJsonPath('data.workspace_id', $workspaceId);

    $this->assertDatabaseHas('projects', [
        'key' => 'TECH',
        'name' => 'Backend Rewrite',
    ]);
});

it('can create a project with priority and response does not leak internal fields', function () {
    $user = UserModel::factory()->create();
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id]);
    $user->refresh();

    $workspaceId = (string) Str::uuid();
    WorkspaceModel::query()->create([
        'id' => $workspaceId,
        'owner_group_id' => (string) Str::uuid(),
        'name' => 'Tech Dept',
        'status' => 'ACTIVE',
    ]);

    \Illuminate\Support\Facades\DB::table('workspace_members')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $user->id,
        'role' => 'admin',
        'created_at' => now(),
    ]);

    $this->seed(WorkloadLookupSeeder::class);

    $priority = DB::table('priorities')->first();
    expect($priority)->not->toBeNull('Priority lookup table is empty, run seeders.');

    $response = actingAs($user)->postJson('/api/projects', [
        'workspace_id' => $workspaceId,
        'key' => 'PRIO',
        'name' => 'Priority Project',
        'priority_id' => $priority->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.key', 'PRIO')
        ->assertJsonPath('data.priority_id', $priority->id)
        ->assertJsonMissing(['data.deleted_at', 'data.created_at', 'data.id_auto_increment']); // Zero Data Breach

    $this->assertDatabaseHas('projects', [
        'key' => 'PRIO',
        'priority_id' => $priority->id,
    ]);
});

it('cannot create project with non-existent priority (Anti-IDOR)', function () {
    $user = UserModel::factory()->create();
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id]);
    $user->refresh();

    $workspaceId = (string) Str::uuid();
    WorkspaceModel::query()->create([
        'id' => $workspaceId,
        'owner_group_id' => (string) Str::uuid(),
        'name' => 'Tech Dept',
        'status' => 'ACTIVE',
    ]);

    \Illuminate\Support\Facades\DB::table('workspace_members')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $user->id,
        'role' => 'admin',
        'created_at' => now(),
    ]);

    $response = actingAs($user)->postJson('/api/projects', [
        'workspace_id' => $workspaceId,
        'key' => 'FAIL',
        'name' => 'Fail Project',
        'priority_id' => (string) Str::uuid(), // Not exist
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['priority_id']);
});
