<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createRegularUser()
{
    return UserModel::factory()->create();
}

function createSuperAdmin()
{
    $user = UserModel::factory()->create();
    $role = RoleModel::create([
        'name' => 'superadmin',
    ]);
    UserRoleModel::create([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    return $user;
}

it('denies normal user from creating a status', function () {
    $user = createRegularUser();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/statuses', [
        'name' => 'Test Status',
        'slug' => 'test-status',
        'category' => 'TODO',
        'color' => '#fff',
    ]);

    $response->assertStatus(403);
});

it('allows superadmin to create a status', function () {
    $user = createSuperAdmin();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/statuses', [
        'name' => 'Test Status',
        'slug' => 'test-status',
        'category' => 'TODO',
        'color' => '#fff',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('statuses', ['name' => 'Test Status']);
});

it('denies normal user from updating a status', function () {
    $user = createRegularUser();
    $status = StatusModel::create([
        'name' => 'Old',
        'slug' => 'old',
        'category' => 'TODO',
    ]);

    $response = $this->actingAs($user, 'sanctum')->putJson('/api/statuses/'.$status->id, [
        'name' => 'New Status',
        'slug' => 'new-status',
        'category' => 'IN_PROGRESS',
        'color' => '#000',
    ]);

    $response->assertStatus(403);
});

it('allows superadmin to update a status', function () {
    $user = createSuperAdmin();
    $status = StatusModel::create([
        'name' => 'Old',
        'slug' => 'old',
        'category' => 'TODO',
    ]);

    $response = $this->actingAs($user, 'sanctum')->putJson('/api/statuses/'.$status->id, [
        'name' => 'New Status',
        'slug' => 'new-status',
        'category' => 'IN_PROGRESS',
        'color' => '#000',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('statuses', ['name' => 'New Status']);
});

it('denies normal user from deleting a status', function () {
    $user = createRegularUser();
    $status = StatusModel::create([
        'name' => 'To Delete',
        'slug' => 'to-delete',
        'category' => 'DONE',
    ]);

    $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/statuses/'.$status->id);

    $response->assertStatus(403);
});

it('allows superadmin to delete a status', function () {
    $user = createSuperAdmin();
    $status = StatusModel::create([
        'name' => 'To Delete',
        'slug' => 'to-delete',
        'category' => 'DONE',
    ]);

    $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/statuses/'.$status->id);

    $response->assertStatus(204);
    $this->assertDatabaseMissing('statuses', ['id' => $status->id]);
});

it('denies normal user from creating a workflow', function () {
    $user = createRegularUser();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/workflows', [
        'name' => 'Test Workflow',
        'description' => 'Test Description',
        'is_default' => true,
    ]);

    $response->assertStatus(403);
});

it('allows superadmin to create a workflow', function () {
    $user = createSuperAdmin();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/workflows', [
        'name' => 'Test Workflow',
        'description' => 'Test Description',
        'is_default' => true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('workflows', ['name' => 'Test Workflow']);
});
