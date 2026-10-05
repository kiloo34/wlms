<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup dependency: Buat org level dan unit agar referensi table valid
    DB::table('org_levels')->insert([
        'id' => '01923abc-level-1234-1234-123456789abc', 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true,
    ]);

    DB::table('org_units')->insert([
        'id' => '01923abc-org-1234-1234-123456789abc',
        'org_level_id' => '01923abc-level-1234-1234-123456789abc',
        'name' => 'IT Group',
    ]);

    // Setup Dummy User dengan ID khusus
    $this->user = UserModel::factory()->create([
        'org_unit_id' => '01923abc-org-1234-1234-123456789abc',
    ]);
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $this->user->id, 'role_id' => $role->id]);
    if (isset($this->user)) {
        $this->user->refresh();
    } elseif (isset($user)) {
        $user->refresh();
    }
});

test('api rejects unauthenticated request', function () {
    $this->postJson('/api/workspaces', [
        'name' => 'Should Fail',
    ])->assertStatus(401);
});

test('anti-idor: owner_group_id is taken from auth token, payload override attempt is ignored', function () {
    $idempotencyKey = Str::uuid()->toString();

    $response = $this->actingAs($this->user)->postJson(
        '/api/workspaces',
        [
            'name' => 'Valid Workspace',
            'owner_group_id' => 'hacked-group-id', // Attacker mencoba menginjeksi grup orang lain
            'status' => 'ARCHIVED',               // Mass assignment injection attempt
        ],
        [
            'X-Idempotency-Key' => $idempotencyKey,
        ]
    );

    $response->assertStatus(201);

    // Assert: group ID yang tersimpan ADALAH milik user, BUKAN dari payload
    $this->assertDatabaseHas('workspaces', [
        'id' => $idempotencyKey,
        'name' => 'Valid Workspace',
        'owner_group_id' => '01923abc-org-1234-1234-123456789abc',
        'status' => 'ACTIVE', // Mass assignment status ditolak
    ]);
});

test('api prevents duplicate submission via idempotency key', function () {
    $idempotencyKey = Str::uuid()->toString();

    $payload = ['name' => 'Test Idempotency'];
    $headers = ['X-Idempotency-Key' => $idempotencyKey];

    // First Request
    $this->actingAs($this->user)->postJson('/api/workspaces', $payload, $headers)->assertStatus(201);

    // Second Request (Identical) - Seharusnya tidak error, melainkan updateOrCreate melakukan UPDATE
    // dan mengembalikan HTTP 201. Ini membuktikan idempotency yang baik tanpa exception 500.
    $this->actingAs($this->user)->postJson('/api/workspaces', $payload, $headers)->assertStatus(201);

    // Database tetap 1
    $this->assertDatabaseCount('workspaces', 1);
});
