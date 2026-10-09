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
