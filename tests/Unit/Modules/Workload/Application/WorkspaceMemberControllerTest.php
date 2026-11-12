<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Domain\Enums\WorkspaceMemberRole;
use App\Modules\Workload\Presentation\Http\Controllers\WorkspaceMemberController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

if (! function_exists('createWorkspaceWithAdmin')) {
    function createWorkspaceWithAdmin(): array
    {
        $unitId = (string) Str::uuid();
        $wsId = (string) Str::uuid();

        DB::table('workspaces')->insert([
            'id' => $wsId,
            'name' => 'Test Workspace',
            'owner_group_id' => $unitId,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = UserModel::factory()->create([
            'org_unit_id' => $unitId,
            'email' => 'superadmin@wlms.com',
        ]);

        DB::table('workspace_members')->insert([
            'workspace_id' => $wsId,
            'user_id' => $admin->id,
            'role' => 'admin',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$wsId, $admin];
    }
}

if (! function_exists('updateMemberRequest')) {
    function updateMemberRequest(UserModel $actor, string $wsId, int $userId, array $payload)
    {
        $request = Request::create("/api/workspaces/{$wsId}/members/{$userId}", 'PUT', $payload);
        $request->setUserResolver(fn () => $actor);

        return app(WorkspaceMemberController::class)->update($request, $wsId, $userId);
    }
}

if (! function_exists('destroyMemberRequest')) {
    function destroyMemberRequest(UserModel $actor, string $wsId, int $userId)
    {
        $request = Request::create("/api/workspaces/{$wsId}/members/{$userId}", 'DELETE');
        $request->setUserResolver(fn () => $actor);

        return app(WorkspaceMemberController::class)->destroy($request, $wsId, $userId);
    }
}

if (! function_exists('storeMemberRequest')) {
    function storeMemberRequest(UserModel $actor, string $wsId, array $payload)
    {
        $request = Request::create("/api/workspaces/{$wsId}/members", 'POST', $payload);
        $request->setUserResolver(fn () => $actor);

        return app(WorkspaceMemberController::class)->store($request, $wsId);
    }
}

test('R1: Member role update validation rejects invalid roles with ValidationException (422)', function () {
    [$wsId, $admin] = createWorkspaceWithAdmin();
    $targetUser = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        'workspace_id' => $wsId,
        'user_id' => $targetUser->id,
        'role' => 'member',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Test with completely invalid role
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['role' => 'invalid_role']))
        ->toThrow(ValidationException::class);

    // Test with another invalid role like 'superadmin'
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['role' => 'superadmin']))
        ->toThrow(ValidationException::class);

    // Test with empty string role
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['role' => '']))
        ->toThrow(ValidationException::class);

    // Test with null role
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['role' => null]))
        ->toThrow(ValidationException::class);
});

test('R1: Member capacity update validation rejects empty string, null, and out-of-range capacity (422)', function () {
    [$wsId, $admin] = createWorkspaceWithAdmin();
    $targetUser = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        'workspace_id' => $wsId,
        'user_id' => $targetUser->id,
        'role' => 'member',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Test with empty string capacity
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['daily_capacity_hours' => '']))
        ->toThrow(ValidationException::class);

    // Test with null capacity
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['daily_capacity_hours' => null]))
        ->toThrow(ValidationException::class);

    // Test with negative capacity
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['daily_capacity_hours' => -1]))
        ->toThrow(ValidationException::class);

    // Test with capacity > 24
    expect(fn () => updateMemberRequest($admin, $wsId, $targetUser->id, ['daily_capacity_hours' => 25]))
        ->toThrow(ValidationException::class);
});

test('R1: Adding member validates role properly and rejects empty string or invalid role', function () {
    [$wsId, $admin] = createWorkspaceWithAdmin();
    $newUser = UserModel::factory()->create();

    // Rejects empty string role
    expect(fn () => storeMemberRequest($admin, $wsId, ['user_id' => $newUser->id, 'role' => '']))
        ->toThrow(ValidationException::class);

    // Rejects null role
    expect(fn () => storeMemberRequest($admin, $wsId, ['user_id' => $newUser->id, 'role' => null]))
        ->toThrow(ValidationException::class);

    // Rejects invalid role
    expect(fn () => storeMemberRequest($admin, $wsId, ['user_id' => $newUser->id, 'role' => 'invalid_role']))
        ->toThrow(ValidationException::class);

    // Omitting role succeeds and defaults to member
    $response = storeMemberRequest($admin, $wsId, ['user_id' => $newUser->id]);
    expect($response->getStatusCode())->toBe(201);

    $savedRole = DB::table('workspace_members')
        ->where('workspace_id', $wsId)
        ->where('user_id', $newUser->id)
        ->value('role');
    expect($savedRole)->toBe('member');
});

test('R1: Member role update accepts valid domain roles (admin, member, viewer, owner)', function () {
    [$wsId, $admin] = createWorkspaceWithAdmin();
    $targetUser = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        'workspace_id' => $wsId,
        'user_id' => $targetUser->id,
        'role' => 'member',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach (WorkspaceMemberRole::values() as $validRole) {
        $response = updateMemberRequest($admin, $wsId, $targetUser->id, ['role' => $validRole]);
        expect($response->getStatusCode())->toBe(200);

        $memberRole = DB::table('workspace_members')
            ->where('workspace_id', $wsId)
            ->where('user_id', $targetUser->id)
            ->value('role');

        expect($memberRole)->toBe($validRole);
    }
});

test('R4: Demoting sole admin in workspace returns 422 ValidationException', function () {
    [$wsId, $admin] = createWorkspaceWithAdmin();

    // $admin is the sole admin in $wsId
    expect(fn () => updateMemberRequest($admin, $wsId, $admin->id, ['role' => 'member']))
        ->toThrow(ValidationException::class);

    try {
        updateMemberRequest($admin, $wsId, $admin->id, ['role' => 'viewer']);
        $this->fail('Expected ValidationException was not thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('role');
        expect($e->errors()['role'][0])->toContain('Cannot demote the last remaining admin or owner in the workspace.');
    }

    // Verify role remained admin
    $role = DB::table('workspace_members')
        ->where('workspace_id', $wsId)
        ->where('user_id', $admin->id)
        ->value('role');
    expect($role)->toBe('admin');
});

test('R4: Demoting sole owner in workspace returns 422 ValidationException', function () {
    $unitId = (string) Str::uuid();
    $wsId = (string) Str::uuid();

    DB::table('workspaces')->insert([
        'id' => $wsId,
        'name' => 'Owner Workspace',
        'owner_group_id' => $unitId,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $owner = UserModel::factory()->create([
        'org_unit_id' => $unitId,
        'email' => 'superadmin@wlms.com',
    ]);

    DB::table('workspace_members')->insert([
        'workspace_id' => $wsId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => updateMemberRequest($owner, $wsId, $owner->id, ['role' => 'viewer']))
        ->toThrow(ValidationException::class);
});

test('R4: Demoting admin when another admin exists succeeds', function () {
    [$wsId, $admin1] = createWorkspaceWithAdmin();
    $admin2 = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        'workspace_id' => $wsId,
        'user_id' => $admin2->id,
        'role' => 'admin',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = updateMemberRequest($admin1, $wsId, $admin1->id, ['role' => 'member']);
    expect($response->getStatusCode())->toBe(200);

    $role1 = DB::table('workspace_members')->where('workspace_id', $wsId)->where('user_id', $admin1->id)->value('role');
    $role2 = DB::table('workspace_members')->where('workspace_id', $wsId)->where('user_id', $admin2->id)->value('role');

    expect($role1)->toBe('member')
        ->and($role2)->toBe('admin');
});

test('R4: Workspace with 1 owner, 0 admin, but multiple members/viewers strictly protects sole owner from demotion', function () {
    $unitId = (string) Str::uuid();
    $wsId = (string) Str::uuid();

    DB::table('workspaces')->insert([
        'id' => $wsId,
        'name' => 'Owner with Many Members WS',
        'owner_group_id' => $unitId,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $owner = UserModel::factory()->create(['org_unit_id' => $unitId]);

    $members = [
        [
            'workspace_id' => $wsId,
            'user_id' => $owner->id,
            'role' => 'owner',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];

    for ($i = 1; $i <= 5; $i++) {
        $uMember = UserModel::factory()->create(['org_unit_id' => $unitId]);
        $uViewer = UserModel::factory()->create(['org_unit_id' => $unitId]);

        $members[] = [
            'workspace_id' => $wsId,
            'user_id' => $uMember->id,
            'role' => 'member',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $members[] = [
            'workspace_id' => $wsId,
            'user_id' => $uViewer->id,
            'role' => 'viewer',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    DB::table('workspace_members')->insert($members);

    // Despite 10 regular members/viewers, owner CANNOT be demoted
    expect(fn () => updateMemberRequest($owner, $wsId, $owner->id, ['role' => 'member']))
        ->toThrow(ValidationException::class);
});

test('R4: In 1 admin and 1 owner workspace, demoting admin succeeds, but subsequent demotion or removal of owner is rejected', function () {
    $unitId = (string) Str::uuid();
    $wsId = (string) Str::uuid();

    DB::table('workspaces')->insert([
        'id' => $wsId,
        'name' => 'Admin and Owner WS',
        'owner_group_id' => $unitId,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = UserModel::factory()->create(['org_unit_id' => $unitId]);
    $owner = UserModel::factory()->create(['org_unit_id' => $unitId]);

    DB::table('workspace_members')->insert([
        [
            'workspace_id' => $wsId,
            'user_id' => $admin->id,
            'role' => 'admin',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'workspace_id' => $wsId,
            'user_id' => $owner->id,
            'role' => 'owner',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Demote admin to member -> succeeds because owner remains
    $response = updateMemberRequest($owner, $wsId, $admin->id, ['role' => 'member']);
    expect($response->getStatusCode())->toBe(200);

    // CASCADE CHECK: Now only owner remains as administrative role. Demoting owner must fail!
    expect(fn () => updateMemberRequest($owner, $wsId, $owner->id, ['role' => 'member']))
        ->toThrow(ValidationException::class);

    // CASCADE CHECK: Removing owner via destroy must fail!
    expect(fn () => destroyMemberRequest($owner, $wsId, $owner->id))
        ->toThrow(ValidationException::class);
});

test('R4: In 1 admin and 1 owner workspace, demoting owner succeeds, but subsequent demotion or removal of admin is rejected', function () {
    $unitId = (string) Str::uuid();
    $wsId = (string) Str::uuid();

    DB::table('workspaces')->insert([
        'id' => $wsId,
        'name' => 'Admin and Owner WS 2',
        'owner_group_id' => $unitId,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = UserModel::factory()->create(['org_unit_id' => $unitId]);
    $owner = UserModel::factory()->create(['org_unit_id' => $unitId]);

    DB::table('workspace_members')->insert([
        [
            'workspace_id' => $wsId,
            'user_id' => $admin->id,
            'role' => 'admin',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'workspace_id' => $wsId,
            'user_id' => $owner->id,
            'role' => 'owner',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Demote owner to member -> succeeds because admin remains
    $response = updateMemberRequest($admin, $wsId, $owner->id, ['role' => 'member']);
    expect($response->getStatusCode())->toBe(200);

    // CASCADE CHECK: Now only admin remains. Demoting admin must fail!
    expect(fn () => updateMemberRequest($admin, $wsId, $admin->id, ['role' => 'member']))
        ->toThrow(ValidationException::class);

    // CASCADE CHECK: Removing admin via destroy must fail!
    expect(fn () => destroyMemberRequest($admin, $wsId, $admin->id))
        ->toThrow(ValidationException::class);
});

test('R4: Removing sole owner in workspace via controller destroy() returns 422 ValidationException', function () {
    $unitId = (string) Str::uuid();
    $wsId = (string) Str::uuid();

    DB::table('workspaces')->insert([
        'id' => $wsId,
        'name' => 'Owner Only WS',
        'owner_group_id' => $unitId,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $owner = UserModel::factory()->create(['org_unit_id' => $unitId]);

    DB::table('workspace_members')->insert([
        'workspace_id' => $wsId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => destroyMemberRequest($owner, $wsId, $owner->id))
        ->toThrow(ValidationException::class);
});

test('R4: Removing sole admin in workspace via controller destroy() returns 422 ValidationException', function () {
    [$wsId, $admin] = createWorkspaceWithAdmin();

    expect(fn () => destroyMemberRequest($admin, $wsId, $admin->id))
        ->toThrow(ValidationException::class);
});
