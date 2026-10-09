<?php

use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\Workload\Domain\Events\IssueCreated;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = UserModel::factory()->create();
    $role = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $this->user->id, 'role_id' => $role->id]);

    $workspace = WorkspaceModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'Workspace A',
        'owner_group_id' => Str::uuid()->toString(),
        'status' => 'ACTIVE',
    ]);

    $project = ProjectModel::create([
        'id' => Str::uuid()->toString(),
        'workspace_id' => $workspace->id,
        'name' => 'Project A',
        'key' => 'PROJ',
    ]);

    $sprint = SprintModel::create([
        'id' => Str::uuid()->toString(),
        'project_id' => $project->id,
        'name' => 'Sprint 1',
        'start_date' => now(),
        'end_date' => now()->addDays(14),
        'status' => 'PLANNED',
    ]);

    $typeId = Str::uuid()->toString();
    DB::table('issue_types')->insert([
        'id' => $typeId, 'name' => 'Task', 'slug' => 'task', 'icon' => 'task',
    ]);

    $statusId = Str::uuid()->toString();
    DB::table('statuses')->insert([
        'id' => $statusId, 'name' => 'To Do', 'slug' => 'todo', 'category' => 'TODO', 'color' => '#E2E8F0',
    ]);

    $priorityId = Str::uuid()->toString();
    DB::table('priorities')->insert([
        'id' => $priorityId, 'name' => 'High', 'slug' => 'high', 'level' => 1, 'color' => '#FF0000',
    ]);

    $this->issue = IssueModel::create([
        'id' => Str::uuid()->toString(),
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'title' => 'Test Issue',
        'number' => 1,
        'reporter_id' => (string) $this->user->id,
        'issue_type_id' => $typeId,
        'status_id' => $statusId,
        'priority_id' => $priorityId,
    ]);
});

test('user can add comment to issue', function () {
    Event::fake();
    $response = $this->actingAs($this->user)->postJson(route('collaboration.issues.comments.add', ['issueId' => $this->issue->id]), [
        'body' => 'This is a test comment.',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.body', 'This is a test comment.')
        ->assertJsonPath('data.author_id', (string) $this->user->id);

    $this->assertDatabaseHas('comments', [
        'issue_id' => $this->issue->id,
        'body' => 'This is a test comment.',
    ]);
});

test('user can edit own comment', function () {
    $comment = CommentModel::create([
        'id' => Str::uuid()->toString(),
        'issue_id' => $this->issue->id,
        'author_id' => (string) $this->user->id,
        'body' => 'Original comment',
        'is_edited' => false,
    ]);

    $response = $this->actingAs($this->user)->putJson("/api/issues/{$this->issue->id}/comments/{$comment->id}", [
        'body' => 'Edited comment',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.body', 'Edited comment')
        ->assertJsonPath('data.is_edited', true);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'body' => 'Edited comment',
        'is_edited' => 1,
    ]);
});

test('prevents IDOR when editing another users comment', function () {
    $otherUser = UserModel::factory()->create();

    $comment = CommentModel::create([
        'id' => Str::uuid()->toString(),
        'issue_id' => $this->issue->id,
        'author_id' => (string) $otherUser->id,
        'body' => 'Other users comment',
        'is_edited' => false,
    ]);

    $response = $this->actingAs($this->user)->putJson("/api/issues/{$this->issue->id}/comments/{$comment->id}", [
        'body' => 'Malicious edit',
    ]);

    $response->assertStatus(403);
});

test('cannot edit soft deleted comment', function () {
    $comment = CommentModel::create([
        'id' => Str::uuid()->toString(),
        'issue_id' => $this->issue->id,
        'author_id' => (string) $this->user->id,
        'body' => 'Deleted comment',
        'is_edited' => false,
    ]);
    $comment->delete();

    $response = $this->actingAs($this->user)->putJson("/api/issues/{$this->issue->id}/comments/{$comment->id}", [
        'body' => 'Edit deleted',
    ]);

    $response->assertStatus(404);
});

test('audit log created when issue created event dispatched', function () {
    $issueId = Str::uuid()->toString();
    $projectId = Str::uuid()->toString();

    $event = new IssueCreated(
        $issueId,
        $projectId,
        '1',
        'New Issue',
        (string) $this->user->id,
        new DateTimeImmutable
    );

    event($event);

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => 'issue',
        'auditable_id' => $issueId,
        'event' => 'created',
        'actor_id' => (string) $this->user->id,
    ]);

    $log = AuditLogModel::where('auditable_id', $issueId)->first();
    $this->assertEquals('New Issue', $log->new_values['title']);
});
