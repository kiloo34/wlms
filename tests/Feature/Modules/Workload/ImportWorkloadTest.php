<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Application\Jobs\ImportIssuesJob;
use App\Modules\Workload\Application\Jobs\ImportProjectsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = UserModel::factory()->create();

    // Create Superadmin role to bypass 'manage-rbac' gate
    $this->adminRole = RoleModel::firstOrCreate(
        ['name' => 'Superadmin'],
        ['id' => Str::uuid(), 'scope' => 'GLOBAL']
    );
});

test('normal user cannot access import-workload page', function () {
    $response = $this->actingAs($this->user)->get('/admin/import-workload');
    $response->assertStatus(403);
});

test('normal user cannot submit import-workload form', function () {
    $response = $this->actingAs($this->user)->post('/admin/import-workload', []);
    $response->assertStatus(403);
});

test('superadmin can access import-workload page', function () {
    UserRoleModel::create([
        'id' => Str::uuid(),
        'user_id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);

    $response = $this->actingAs($this->user)->get('/admin/import-workload');
    $response->assertStatus(200);
});

test('validates required fields', function () {
    UserRoleModel::create([
        'id' => Str::uuid(),
        'user_id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);

    $response = $this->actingAs($this->user)->post('/admin/import-workload', []);
    $response->assertSessionHasErrors(['type', 'file']);
});

test('validates file type', function () {
    UserRoleModel::create([
        'id' => Str::uuid(),
        'user_id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);

    $file = UploadedFile::fake()->create('image.png', 100, 'image/png');

    $response = $this->actingAs($this->user)->post('/admin/import-workload', [
        'type' => 'issues',
        'file' => $file,
    ]);

    $response->assertSessionHasErrors(['file']);
});

test('validates workspace_id is required for projects', function () {
    UserRoleModel::create([
        'id' => Str::uuid(),
        'user_id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);

    $file = UploadedFile::fake()->create('projects.csv', 100, 'text/csv');

    $response = $this->actingAs($this->user)->post('/admin/import-workload', [
        'type' => 'projects',
        'file' => $file,
    ]);

    $response->assertSessionHasErrors(['workspace_id']);
});

test('dispatches ImportProjectsJob for projects', function () {
    Bus::fake();

    UserRoleModel::create([
        'id' => Str::uuid(),
        'user_id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);

    $file = UploadedFile::fake()->create('projects.csv', 100, 'text/csv');
    $workspaceId = Str::uuid()->toString();

    $response = $this->actingAs($this->user)->post('/admin/import-workload', [
        'type' => 'projects',
        'file' => $file,
        'workspace_id' => $workspaceId,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    Bus::assertDispatched(ImportProjectsJob::class, function ($job) use ($workspaceId) {
        return (string) $job->workspaceId === (string) $workspaceId;
    });
});

test('dispatches ImportIssuesJob for issues', function () {
    Bus::fake();

    UserRoleModel::create([
        'id' => Str::uuid(),
        'user_id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);

    $file = UploadedFile::fake()->create('issues.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $response = $this->actingAs($this->user)->post('/admin/import-workload', [
        'type' => 'issues',
        'file' => $file,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $userId = $this->user->id;
    Bus::assertDispatched(ImportIssuesJob::class, function ($job) use ($userId) {
        return (string) $job->reporterId === (string) $userId;
    });
});
