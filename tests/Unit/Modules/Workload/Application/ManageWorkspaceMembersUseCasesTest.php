<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Application\UseCases\AddWorkspaceMemberUseCase;
use App\Modules\Workload\Application\UseCases\RemoveWorkspaceMemberUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

if (! function_exists('createTestWorkspace')) {
    function createTestWorkspace(string $name = 'Test Workspace'): string
    {
        $workspaceId = (string) Str::uuid();
        $groupId = (string) Str::uuid();

        DB::table('workspaces')->insert([
            'id' => $workspaceId,
            'name' => $name,
            'owner_group_id' => $groupId,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $workspaceId;
    }
}

test('R2: Re-adding an existing member preserves their existing role and created_at timestamp', function () {
    $workspaceId = createTestWorkspace();
    $user = UserModel::factory()->create();

    $originalCreatedAt = Carbon::now()->subDays(10)->startOfSecond();
    $originalUpdatedAt = Carbon::now()->subDays(10)->startOfSecond();

    DB::table('workspace_members')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $user->id,
        'role' => 'admin',
        'daily_capacity_hours' => 8,
        'created_at' => $originalCreatedAt,
        'updated_at' => $originalUpdatedAt,
    ]);

    $useCase = app(AddWorkspaceMemberUseCase::class);

    // Attempt to re-add the user as 'member'
    $useCase->execute($workspaceId, $user->id, 'member');

    $member = DB::table('workspace_members')
        ->where('workspace_id', $workspaceId)
        ->where('user_id', $user->id)
        ->first();

    expect($member)->not->toBeNull();
    // Role must be preserved as 'admin' (not demoted to 'member')
    expect($member->role)->toBe('admin');
    // Original created_at must remain untouched
    expect(Carbon::parse($member->created_at)->toIso8601String())
        ->toBe($originalCreatedAt->toIso8601String());
});

test('R3: Inserting a new member explicitly sets daily_capacity_hours to 8 by default', function () {
    $workspaceId = createTestWorkspace();
    $user = UserModel::factory()->create();

    $useCase = app(AddWorkspaceMemberUseCase::class);
    $useCase->execute($workspaceId, $user->id, 'member');

    $member = DB::table('workspace_members')
        ->where('workspace_id', $workspaceId)
        ->where('user_id', $user->id)
        ->first();

    expect($member)->not->toBeNull()
        ->and($member->role)->toBe('member')
        ->and((int) $member->daily_capacity_hours)->toBe(8);
});

test('R3: Inserting a new member with custom daily_capacity_hours sets it explicitly', function () {
    $workspaceId = createTestWorkspace();
    $user = UserModel::factory()->create();

    $useCase = app(AddWorkspaceMemberUseCase::class);
    $useCase->execute($workspaceId, $user->id, 'viewer', 6);

    $member = DB::table('workspace_members')
        ->where('workspace_id', $workspaceId)
        ->where('user_id', $user->id)
        ->first();

    expect($member)->not->toBeNull()
        ->and($member->role)->toBe('viewer')
        ->and((int) $member->daily_capacity_hours)->toBe(6);
});

test('R4: Removing sole admin in workspace fails with ValidationException', function () {
    $workspaceId = createTestWorkspace();
    $admin = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $admin->id,
        'role' => 'admin',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $useCase = app(RemoveWorkspaceMemberUseCase::class);

    expect(fn () => $useCase->execute($workspaceId, $admin->id))
        ->toThrow(ValidationException::class);

    // Verify member still exists
    expect(DB::table('workspace_members')->where('workspace_id', $workspaceId)->where('user_id', $admin->id)->exists())
        ->toBeTrue();
});

test('R4: Removing sole owner in workspace fails with ValidationException', function () {
    $workspaceId = createTestWorkspace();
    $owner = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'daily_capacity_hours' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $useCase = app(RemoveWorkspaceMemberUseCase::class);

    expect(fn () => $useCase->execute($workspaceId, $owner->id))
        ->toThrow(ValidationException::class);
});

test('R4: Removing sole owner in workspace with multiple regular members and viewers fails with ValidationException', function () {
    $workspaceId = createTestWorkspace();
    $owner = UserModel::factory()->create();

    $members = [
        [
            'workspace_id' => $workspaceId,
            'user_id' => $owner->id,
            'role' => 'owner',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];

    for ($i = 1; $i <= 3; $i++) {
        $uMember = UserModel::factory()->create();
        $uViewer = UserModel::factory()->create();

        $members[] = [
            'workspace_id' => $workspaceId,
            'user_id' => $uMember->id,
            'role' => 'member',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $members[] = [
            'workspace_id' => $workspaceId,
            'user_id' => $uViewer->id,
            'role' => 'viewer',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    DB::table('workspace_members')->insert($members);

    $useCase = app(RemoveWorkspaceMemberUseCase::class);

    expect(fn () => $useCase->execute($workspaceId, $owner->id))
        ->toThrow(ValidationException::class);

    expect(DB::table('workspace_members')->where('workspace_id', $workspaceId)->where('user_id', $owner->id)->exists())
        ->toBeTrue();
});

test('R4: In workspace with 1 admin and 1 owner, removing owner succeeds and leaves remaining admin strictly protected', function () {
    $workspaceId = createTestWorkspace();
    $admin = UserModel::factory()->create();
    $owner = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        [
            'workspace_id' => $workspaceId,
            'user_id' => $admin->id,
            'role' => 'admin',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'workspace_id' => $workspaceId,
            'user_id' => $owner->id,
            'role' => 'owner',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $useCase = app(RemoveWorkspaceMemberUseCase::class);

    // Removing owner succeeds because admin remains
    $useCase->execute($workspaceId, $owner->id);

    expect(DB::table('workspace_members')->where('workspace_id', $workspaceId)->where('user_id', $owner->id)->exists())
        ->toBeFalse();
    expect(DB::table('workspace_members')->where('workspace_id', $workspaceId)->where('user_id', $admin->id)->exists())
        ->toBeTrue();

    // Now removing remaining admin must fail!
    expect(fn () => $useCase->execute($workspaceId, $admin->id))
        ->toThrow(ValidationException::class);
});

test('R4: Removing admin when another admin exists succeeds', function () {
    $workspaceId = createTestWorkspace();
    $admin1 = UserModel::factory()->create();
    $admin2 = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        [
            'workspace_id' => $workspaceId,
            'user_id' => $admin1->id,
            'role' => 'admin',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'workspace_id' => $workspaceId,
            'user_id' => $admin2->id,
            'role' => 'admin',
            'daily_capacity_hours' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $useCase = app(RemoveWorkspaceMemberUseCase::class);
    $useCase->execute($workspaceId, $admin1->id);

    expect(DB::table('workspace_members')->where('workspace_id', $workspaceId)->where('user_id', $admin1->id)->exists())
        ->toBeFalse();
    expect(DB::table('workspace_members')->where('workspace_id', $workspaceId)->where('user_id', $admin2->id)->exists())
        ->toBeTrue();
});

test('R5: Removing member unassigns workspace issues while leaving issues in other workspaces unchanged', function () {
    $workspace1 = createTestWorkspace('Workspace 1');
    $workspace2 = createTestWorkspace('Workspace 2');

    $admin = UserModel::factory()->create();
    $member = UserModel::factory()->create();

    DB::table('workspace_members')->insert([
        ['workspace_id' => $workspace1, 'user_id' => $admin->id, 'role' => 'admin', 'daily_capacity_hours' => 8, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => $workspace1, 'user_id' => $member->id, 'role' => 'member', 'daily_capacity_hours' => 8, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => $workspace2, 'user_id' => $admin->id, 'role' => 'admin', 'daily_capacity_hours' => 8, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => $workspace2, 'user_id' => $member->id, 'role' => 'member', 'daily_capacity_hours' => 8, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $project1Id = (string) Str::uuid();
    $project2Id = (string) Str::uuid();

    DB::table('projects')->insert([
        ['id' => $project1Id, 'workspace_id' => $workspace1, 'key' => 'P1', 'name' => 'Project 1', 'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
        ['id' => $project2Id, 'workspace_id' => $workspace2, 'key' => 'P2', 'name' => 'Project 2', 'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $typeId = (string) Str::uuid();
    $priorityId = (string) Str::uuid();
    $statusId = (string) Str::uuid();

    DB::table('issue_types')->insert(['id' => $typeId, 'name' => 'Task', 'slug' => 'task', 'icon' => 'task']);
    DB::table('priorities')->insert(['id' => $priorityId, 'name' => 'Medium', 'slug' => 'medium', 'level' => 2, 'color' => '#f59e0b']);
    DB::table('statuses')->insert(['id' => $statusId, 'name' => 'Todo', 'slug' => 'todo', 'category' => 'TODO', 'color' => '#3b82f6']);

    $issue1Id = (string) Str::uuid();
    $issue2Id = (string) Str::uuid();

    DB::table('issues')->insert([
        [
            'id' => $issue1Id,
            'project_id' => $project1Id,
            'status_id' => $statusId,
            'issue_type_id' => $typeId,
            'priority_id' => $priorityId,
            'number' => 1,
            'title' => 'Issue in Workspace 1',
            'reporter_id' => $admin->id,
            'assignee_id' => $member->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $issue2Id,
            'project_id' => $project2Id,
            'status_id' => $statusId,
            'issue_type_id' => $typeId,
            'priority_id' => $priorityId,
            'number' => 2,
            'title' => 'Issue in Workspace 2',
            'reporter_id' => $admin->id,
            'assignee_id' => $member->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $useCase = app(RemoveWorkspaceMemberUseCase::class);
    $useCase->execute($workspace1, $member->id);

    // Issue in workspace 1 must be unassigned (null)
    $issue1 = DB::table('issues')->where('id', $issue1Id)->first();
    expect($issue1->assignee_id)->toBeNull();

    // Issue in workspace 2 must remain assigned to the member
    $issue2 = DB::table('issues')->where('id', $issue2Id)->first();
    expect((int) $issue2->assignee_id)->toBe($member->id);

    // Member must be removed from workspace 1, but remain in workspace 2
    expect(DB::table('workspace_members')->where('workspace_id', $workspace1)->where('user_id', $member->id)->exists())
        ->toBeFalse();
    expect(DB::table('workspace_members')->where('workspace_id', $workspace2)->where('user_id', $member->id)->exists())
        ->toBeTrue();
});
