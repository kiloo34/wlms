<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Workload\Application\Queries\GetWorkspacesByGroupQuery;
use App\Modules\Workload\Application\UseCases\GetProjectsUseCase;
use App\Modules\Workload\Application\DTOs\GetProjectsInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::dropIfExists('org_levels');
    Schema::dropIfExists('org_units');
    
    Schema::create('org_levels', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('slug')->nullable();
        $table->integer('order')->default(1);
    });
    
    Schema::create('org_units', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('org_level_id')->nullable();
        $table->string('name');
        $table->string('code')->nullable();
        $table->string('slug')->nullable();
    });

    if (!Schema::hasColumn('users', 'org_unit_id')) {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('org_unit_id')->nullable();
            $table->uuid('uuid')->nullable();
        });
    }

    // Bangun Matriks Data (Seed) yang lengkap
    $this->orgLevelId = Str::uuid()->toString();
    DB::table('org_levels')->insert(['id' => $this->orgLevelId, 'name' => 'Divisi', 'slug' => 'divisi', 'order' => 1]);

    $this->internalOrgId = Str::uuid()->toString();
    $this->externalOrgId = Str::uuid()->toString();
    $this->superadminOrgId = Str::uuid()->toString();
    
    DB::table('org_units')->insert([
        ['id' => $this->internalOrgId, 'name' => 'Divisi Internal', 'org_level_id' => $this->orgLevelId, 'code' => 'INT', 'slug' => 'int'],
        ['id' => $this->externalOrgId, 'name' => 'Divisi Eksternal', 'org_level_id' => $this->orgLevelId, 'code' => 'EXT', 'slug' => 'ext'],
        ['id' => $this->superadminOrgId, 'name' => 'Divisi Superadmin', 'org_level_id' => $this->orgLevelId, 'code' => 'SA', 'slug' => 'sa'],
    ]);

    // Role Superadmin
    $this->roleSuperadminId = Str::uuid()->toString();
    DB::table('roles')->insert(['id' => $this->roleSuperadminId, 'name' => 'Superadmin']);

    // Users
    $this->superadminId = DB::table('users')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'Superadmin', 'email' => 'sa@test.com', 'password' => 'sec', 'org_unit_id' => $this->superadminOrgId]);
    DB::table('user_roles')->insert(['id' => Str::uuid()->toString(), 'user_id' => $this->superadminId, 'role_id' => $this->roleSuperadminId, 'context_type' => 'GLOBAL', 'context_id' => 'GLOBAL']);

    $this->workspaceAdminId = DB::table('users')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'Workspace Admin', 'email' => 'wa@test.com', 'password' => 'sec', 'org_unit_id' => $this->externalOrgId]);
    
    $this->internalMemberId = DB::table('users')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'Internal Member', 'email' => 'im@test.com', 'password' => 'sec', 'org_unit_id' => $this->internalOrgId]);
    
    $this->externalLeadId = DB::table('users')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'External Lead', 'email' => 'el@test.com', 'password' => 'sec', 'org_unit_id' => $this->externalOrgId]);
    
    $this->externalAssigneeId = DB::table('users')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'External Assignee', 'email' => 'ea@test.com', 'password' => 'sec', 'org_unit_id' => $this->externalOrgId]);
    
    $this->externalPlainId = DB::table('users')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'External Plain', 'email' => 'ep@test.com', 'password' => 'sec', 'org_unit_id' => $this->externalOrgId]);

    // Workspace
    $this->workspaceId = Str::uuid()->toString();
    DB::table('workspaces')->insert([
        'id' => $this->workspaceId,
        'name' => 'Internal Workspace',
        'owner_group_id' => $this->internalOrgId,
        'status' => 'ACTIVE'
    ]);

    // Workspace Admin
    DB::table('workspace_members')->insert([
        'workspace_id' => $this->workspaceId,
        'user_id' => $this->workspaceAdminId,
        'role' => 'admin'
    ]);

    // Projects
    $this->projectAId = Str::uuid()->toString();
    $this->projectBId = Str::uuid()->toString();
    $this->projectCId = Str::uuid()->toString();

    DB::table('projects')->insert([
        ['id' => $this->projectAId, 'workspace_id' => $this->workspaceId, 'name' => 'Project A', 'key' => 'PA', 'status' => 'ACTIVE', 'lead_id' => $this->externalLeadId, 'deleted_at' => null],
        ['id' => $this->projectBId, 'workspace_id' => $this->workspaceId, 'name' => 'Project B', 'key' => 'PB', 'status' => 'ACTIVE', 'lead_id' => null, 'deleted_at' => null],
        ['id' => $this->projectCId, 'workspace_id' => $this->workspaceId, 'name' => 'Project C', 'key' => 'PC', 'status' => 'ACTIVE', 'lead_id' => null, 'deleted_at' => now()],
    ]);

    // Issues
    $this->statusId = Str::uuid()->toString();
    DB::table('statuses')->insert([
        'id' => $this->statusId,
        'name' => 'To Do',
        'slug' => 'to-do',
        'category' => 'TODO'
    ]);

    $this->priorityId = Str::uuid()->toString();
    DB::table('priorities')->insert([
        'id' => $this->priorityId,
        'name' => 'High',
        'slug' => 'high',
        'color' => '#ff0000',
        'level' => 1
    ]);

    $this->issueTypeId = Str::uuid()->toString();
    DB::table('issue_types')->insert([
        'id' => $this->issueTypeId,
        'name' => 'Task',
        'slug' => 'task',
        'color' => '#0000ff',
        'icon' => 'task-icon'
    ]);

    $this->issueId = Str::uuid()->toString();
    DB::table('issues')->insert([
        'id' => $this->issueId,
        'project_id' => $this->projectBId,
        'title' => 'Issue in B',
        'status_id' => $this->statusId,
        'priority_id' => $this->priorityId,
        'assignee_id' => $this->externalAssigneeId,
        'reporter_id' => $this->internalMemberId,
        'issue_type_id' => $this->issueTypeId,
        'number' => 1,
        'deleted_at' => null
    ]);
});

it('allows Superadmin to see all projects', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->superadminId,
        workspaceId: $this->workspaceId
    ));
    
    // Harus melihat A dan B (C deleted)
    expect($projects)->toHaveCount(2);
    $projectNames = array_map(fn($p) => $p->name, $projects);
    expect($projectNames)->toContain('Project A', 'Project B');
    expect($projectNames)->not->toContain('Project C');
});

it('allows Workspace Admin to see all projects in workspace', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->workspaceAdminId,
        workspaceId: $this->workspaceId
    ));
    
    expect($projects)->toHaveCount(2);
    $projectNames = array_map(fn($p) => $p->name, $projects);
    expect($projectNames)->toContain('Project A', 'Project B');
});

it('allows Internal Member to see all projects', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->internalMemberId,
        workspaceId: $this->workspaceId
    ));
    
    expect($projects)->toHaveCount(2);
    $projectNames = array_map(fn($p) => $p->name, $projects);
    expect($projectNames)->toContain('Project A', 'Project B');
});

it('allows External Lead to see workspace and only Project A', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->externalLeadId,
        workspaceId: $this->workspaceId
    ));
    
    expect($projects)->toHaveCount(1);
    expect($projects[0]->name)->toBe('Project A');

    $getWorkspacesQuery = new GetWorkspacesByGroupQuery();
    $workspaces = $getWorkspacesQuery->execute($this->externalOrgId, $this->externalLeadId);
    expect($workspaces)->toHaveCount(1);
    expect($workspaces[0]->id)->toBe($this->workspaceId);
});

it('allows External Assignee to see workspace and only Project B', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->externalAssigneeId,
        workspaceId: $this->workspaceId
    ));
    
    expect($projects)->toHaveCount(1);
    expect($projects[0]->name)->toBe('Project B');

    $getWorkspacesQuery = new GetWorkspacesByGroupQuery();
    $workspaces = $getWorkspacesQuery->execute($this->externalOrgId, $this->externalAssigneeId);
    expect($workspaces)->toHaveCount(1);
    expect($workspaces[0]->id)->toBe($this->workspaceId);
});

it('prevents External Plain from seeing workspace and any project', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->externalPlainId,
        workspaceId: $this->workspaceId
    ));
    
    expect($projects)->toHaveCount(0);

    $getWorkspacesQuery = new GetWorkspacesByGroupQuery();
    $workspaces = $getWorkspacesQuery->execute($this->externalOrgId, $this->externalPlainId);
    expect($workspaces)->toHaveCount(0);
});

it('ensures Deleted Project C does not appear for anyone', function () {
    $getProjectsUseCase = new GetProjectsUseCase();
    
    // Cek untuk Superadmin
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->superadminId,
        workspaceId: $this->workspaceId
    ));
    $projectNames = array_map(fn($p) => $p->name, $projects);
    expect($projectNames)->not->toContain('Project C');

    // Cek untuk Internal Member
    $projects = $getProjectsUseCase->execute(new GetProjectsInput(
        actorUserId: (string) $this->internalMemberId,
        workspaceId: $this->workspaceId
    ));
    $projectNames = array_map(fn($p) => $p->name, $projects);
    expect($projectNames)->not->toContain('Project C');
});
