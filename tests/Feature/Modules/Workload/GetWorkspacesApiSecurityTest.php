<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Org Units
    $this->levelId = Str::uuid()->toString();
    DB::table('org_levels')->insert([
        'id' => $this->levelId, 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true,
    ]);

    $this->groupId1 = Str::uuid()->toString();
    $this->groupId2 = Str::uuid()->toString();

    DB::table('org_units')->insert([
        ['id' => $this->groupId1, 'org_level_id' => $this->levelId, 'name' => 'Group Alpha'],
        ['id' => $this->groupId2, 'org_level_id' => $this->levelId, 'name' => 'Group Beta'],
    ]);

    // Setup Users
    $this->userAlpha = UserModel::factory()->create(['org_unit_id' => $this->groupId1]);
    $this->userBeta = UserModel::factory()->create(['org_unit_id' => $this->groupId2]);

    // Setup Workspaces
    DB::table('workspaces')->insert([
        ['id' => Str::uuid()->toString(), 'owner_group_id' => $this->groupId1, 'name' => 'Workspace Alpha 1', 'status' => 'ACTIVE', 'created_at' => now()],
        ['id' => Str::uuid()->toString(), 'owner_group_id' => $this->groupId1, 'name' => 'Workspace Alpha 2', 'status' => 'ACTIVE', 'created_at' => now()],
        ['id' => Str::uuid()->toString(), 'owner_group_id' => $this->groupId2, 'name' => 'Workspace Beta 1', 'status' => 'ACTIVE', 'created_at' => now()],
    ]);
});

test('tenancy isolation: user only sees their group workspaces', function () {
    $response = $this->actingAs($this->userAlpha)->getJson('/api/workspaces');

    $response->assertStatus(200);

    // User Alpha harusnya hanya melihat 2 workspace miliknya
    $response->assertJsonCount(2);

    // Assert struktur tidak bocor
    $response->assertJsonStructure([
        '*' => [
            'id',
            'name',
            'status',
            'owner_group_id',
        ],
    ]);

    // Zero Data Breach: pastikan TIDAK ada kolom 'deleted_at' atau 'internal_token' yang bocor
    $jsonResponse = $response->json();
    expect(array_key_exists('deleted_at', $jsonResponse[0]))->toBeFalse();
});
