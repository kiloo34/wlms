<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup basic DB structure
    $this->levelId = Str::uuid()->toString();
    DB::table('org_levels')->insert([
        'id' => $this->levelId, 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true
    ]);

    $this->groupId = Str::uuid()->toString();
    DB::table('org_units')->insert([
        'id' => $this->groupId, 'org_level_id' => $this->levelId, 'name' => 'IT Group'
    ]);

    // Setup Workspace
    $this->workspaceId = Str::uuid()->toString();
    DB::table('workspaces')->insert([
        'id' => $this->workspaceId,
        'owner_group_id' => $this->groupId,
        'name' => 'Main Workspace',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Setup Project
    $this->projectId = Str::uuid()->toString();
    DB::table('projects')->insert([
        'id' => $this->projectId,
        'workspace_id' => $this->workspaceId,
        'key' => 'MAIN',
        'name' => 'Main Project',
        'description' => 'A project for testing',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Setup User with access (Superadmin for simplicity)
    $this->superAdminRole = RoleModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
    ]);

    $this->userWithAccess = UserModel::factory()->create([
        'org_unit_id' => $this->groupId,
    ]);

    DB::table('user_roles')->insert([
        'id' => Str::uuid()->toString(),
        'user_id' => $this->userWithAccess->id,
        'role_id' => $this->superAdminRole->id,
    ]);

    // Setup User without access (different org unit, no roles)
    $this->otherGroupId = Str::uuid()->toString();
    DB::table('org_units')->insert([
        'id' => $this->otherGroupId, 'org_level_id' => $this->levelId, 'name' => 'Other Group'
    ]);
    
    $this->userWithoutAccess = UserModel::factory()->create([
        'org_unit_id' => $this->otherGroupId,
    ]);
});

test('Anti-overlap: Gagal start sprint (400) jika ada sprint lain yang ACTIVE di project yang sama', function () {
    // Buat sprint active
    $activeSprintId = Str::uuid()->toString();
    DB::table('sprints')->insert([
        'id' => $activeSprintId,
        'project_id' => $this->projectId,
        'name' => 'Sprint 1',
        'state' => 'ACTIVE',
        'committed_points' => 0,
        'completed_points' => 0,
        'start_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Buat sprint draft/pending
    $pendingSprintId = Str::uuid()->toString();
    DB::table('sprints')->insert([
        'id' => $pendingSprintId,
        'project_id' => $this->projectId,
        'name' => 'Sprint 2',
        'state' => 'PENDING',
        'committed_points' => 0,
        'completed_points' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->userWithAccess);

    $response = $this->putJson("/api/sprints/{$pendingSprintId}/start");
    
    // Harus gagal karena sudah ada sprint active
    $response->assertStatus(400);
});

test('Complete sprint berhasil (200) mengubah state ke COMPLETED', function () {
    $activeSprintId = Str::uuid()->toString();
    DB::table('sprints')->insert([
        'id' => $activeSprintId,
        'project_id' => $this->projectId,
        'name' => 'Sprint 1',
        'state' => 'ACTIVE',
        'committed_points' => 0,
        'completed_points' => 0,
        'start_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->userWithAccess);

    $response = $this->putJson("/api/sprints/{$activeSprintId}/complete");
    $response->assertStatus(200);

    $this->assertDatabaseHas('sprints', [
        'id' => $activeSprintId,
        'state' => 'COMPLETED',
    ]);
});

test('IDOR: User tanpa akses tidak bisa hit endpoint sprint (403)', function () {
    $sprintId = Str::uuid()->toString();
    DB::table('sprints')->insert([
        'id' => $sprintId,
        'project_id' => $this->projectId,
        'name' => 'Sprint 1',
        'state' => 'PENDING',
        'committed_points' => 0,
        'completed_points' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->userWithoutAccess);

    // List sprints by project
    $this->getJson("/api/projects/{$this->projectId}/sprints")
        ->assertStatus(403);

    // Start sprint
    $this->putJson("/api/sprints/{$sprintId}/start")
        ->assertStatus(403);

    // Update the state to ACTIVE manually for complete testing
    DB::table('sprints')->where('id', $sprintId)->update(['state' => 'ACTIVE']);

    // Complete sprint
    $this->putJson("/api/sprints/{$sprintId}/complete")
        ->assertStatus(403);
});
