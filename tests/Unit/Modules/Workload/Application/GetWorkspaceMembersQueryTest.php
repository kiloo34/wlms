<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Application\Queries\GetWorkspaceMembersQuery;
use App\Modules\Workload\Presentation\Http\Controllers\WorkspaceMemberController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('R6: GetWorkspaceMembersQuery returns all workspace members (>50 items) without being capped at 50 when limit is null', function () {
    $unitId = (string) Str::uuid();
    $wsId = (string) Str::uuid();

    DB::table('workspaces')->insert([
        'id' => $wsId,
        'name' => 'Large Workspace',
        'owner_group_id' => $unitId,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create 55 users and attach them to workspace_members
    $totalMembers = 55;
    $membersData = [];

    for ($i = 1; $i <= $totalMembers; $i++) {
        $user = UserModel::factory()->create([
            'email' => "user{$i}@wlms.com",
            'org_unit_id' => $unitId,
        ]);

        $membersData[] = [
            'workspace_id' => $wsId,
            'user_id' => $user->id,
            'role' => $i === 1 ? 'admin' : 'member',
            'daily_capacity_hours' => 8,
            'created_at' => now()->subMinutes($totalMembers - $i),
            'updated_at' => now(),
        ];
    }

    DB::table('workspace_members')->insert($membersData);

    $query = app(GetWorkspaceMembersQuery::class);

    // Call without specifying limit (defaults to null)
    $results = $query->execute($wsId);

    expect(count($results))->toBe($totalMembers);

    // Call with explicit limit = 20
    $pagedResults = $query->execute($wsId, limit: 20, offset: 0);
    expect(count($pagedResults))->toBe(20);

    // Call controller index() without limit in query string
    $admin = UserModel::where('email', 'user1@wlms.com')->first();
    $request = Request::create("/api/workspaces/{$wsId}/members");
    $request->setUserResolver(fn () => $admin);

    $controller = app(WorkspaceMemberController::class);
    $response = $controller->index($request, $wsId);

    $data = $response->getData(true)['data'];
    expect(count($data))->toBe($totalMembers);
});
