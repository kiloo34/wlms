<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->levelId = Str::uuid()->toString();
    DB::table('org_levels')->insert([
        'id' => $this->levelId, 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true
    ]);

    $this->groupA = Str::uuid()->toString();
    $this->groupB = Str::uuid()->toString();
    $this->groupC = Str::uuid()->toString();

    DB::table('org_units')->insert([
        ['id' => $this->groupA, 'org_level_id' => $this->levelId, 'name' => 'Group A'],
        ['id' => $this->groupB, 'org_level_id' => $this->levelId, 'name' => 'Group B'],
        ['id' => $this->groupC, 'org_level_id' => $this->levelId, 'name' => 'Group C'],
    ]);

    $this->userA = UserModel::factory()->create(['org_unit_id' => $this->groupA]);
    $this->userB = UserModel::factory()->create(['org_unit_id' => $this->groupB]);
    $this->userC = UserModel::factory()->create(['org_unit_id' => $this->groupC]);

    $this->workspaceA_id = Str::uuid()->toString();
    DB::table('workspaces')->insert([
        'id' => $this->workspaceA_id,
        'owner_group_id' => $this->groupA,
        'name' => 'Workspace A',
        'status' => 'ACTIVE',
        'created_at' => now(),
    ]);
});

test('Scenario 1 (Success): User Owner can invite another user to their workspace', function () {
    $response = $this->actingAs($this->userA)->postJson("/api/workspaces/{$this->workspaceA_id}/members", [
        'user_id' => $this->userB->id,
        'role' => 'member'
    ]);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('workspace_members', [
        'workspace_id' => $this->workspaceA_id,
        'user_id' => $this->userB->id,
        'role' => 'member'
    ]);
});

test('Scenario 2 (Anti-IDOR): User B (not owner, not member) cannot add themselves to User A workspace', function () {
    $response = $this->actingAs($this->userB)->postJson("/api/workspaces/{$this->workspaceA_id}/members", [
        'user_id' => $this->userB->id,
        'role' => 'admin'
    ]);

    $response->assertStatus(403);
});

test('Scenario 3 (Visibility): After User C is invited to workspace A, they can see it in GET /api/workspaces', function () {
    $this->actingAs($this->userA)->postJson("/api/workspaces/{$this->workspaceA_id}/members", [
        'user_id' => $this->userC->id,
        'role' => 'viewer'
    ])->assertStatus(201);

    $response = $this->actingAs($this->userC)->getJson('/api/workspaces');

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'id' => $this->workspaceA_id,
        'name' => 'Workspace A'
    ]);
});

test('Scenario 4 (Success): User Owner can get members of their workspace', function () {
    $this->actingAs($this->userA)->postJson("/api/workspaces/{$this->workspaceA_id}/members", [
        'user_id' => $this->userB->id,
        'role' => 'viewer'
    ]);

    $response = $this->actingAs($this->userA)->getJson("/api/workspaces/{$this->workspaceA_id}/members");

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'id' => $this->userB->id,
        'role' => 'viewer'
    ]);
});

test('Scenario 5 (Success): User Owner can remove a member from their workspace', function () {
    $this->actingAs($this->userA)->postJson("/api/workspaces/{$this->workspaceA_id}/members", [
        'user_id' => $this->userB->id,
        'role' => 'viewer'
    ]);

    $response = $this->actingAs($this->userA)->deleteJson("/api/workspaces/{$this->workspaceA_id}/members/{$this->userB->id}");

    $response->assertStatus(200);
    
    $this->assertDatabaseMissing('workspace_members', [
        'workspace_id' => $this->workspaceA_id,
        'user_id' => $this->userB->id
    ]);
});

test('Scenario 6 (Anti-IDOR): User B cannot remove User C from User A workspace', function () {
    $this->actingAs($this->userA)->postJson("/api/workspaces/{$this->workspaceA_id}/members", [
        'user_id' => $this->userC->id,
        'role' => 'viewer'
    ]);

    $response = $this->actingAs($this->userB)->deleteJson("/api/workspaces/{$this->workspaceA_id}/members/{$this->userC->id}");

    $response->assertStatus(403);
});
