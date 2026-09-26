<?php
declare(strict_types=1);
namespace Tests\Feature\Modules\Collaboration;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentManagementTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;
    private IssueModel $issue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserModel::factory()->create();
    $role = \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => \Illuminate\Support\Str::uuid(), 'scope' => 'GLOBAL']);
    \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel::create(['id' => \Illuminate\Support\Str::uuid(), 'user_id' => $this->user->id, 'role_id' => $role->id]);
    if (isset($this->user)) { $this->user->refresh(); } elseif (isset($user)) { $user->refresh(); }

        // Create requisite data for issue
        $workspace = WorkspaceModel::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => 'Workspace A',
            'owner_group_id' => \Illuminate\Support\Str::uuid()->toString(),
            'status' => 'ACTIVE',
        ]);
        
        $project = ProjectModel::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'workspace_id' => $workspace->id,
            'name' => 'Project A',
            'key' => 'PROJ',
        ]);
        
        $sprint = SprintModel::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
            'status' => 'PLANNED',
        ]);

        $typeId = \Illuminate\Support\Str::uuid()->toString();
        \Illuminate\Support\Facades\DB::table('issue_types')->insert([
            'id' => $typeId, 'name' => 'Task', 'slug' => 'task', 'icon' => 'task'
        ]);

        $statusId = \Illuminate\Support\Str::uuid()->toString();
        \Illuminate\Support\Facades\DB::table('statuses')->insert([
            'id' => $statusId, 'name' => 'To Do', 'slug' => 'todo', 'category' => 'TODO', 'color' => '#E2E8F0'
        ]);

        $priorityId = \Illuminate\Support\Str::uuid()->toString();
        \Illuminate\Support\Facades\DB::table('priorities')->insert([
            'id' => $priorityId, 'name' => 'High', 'slug' => 'high', 'level' => 1, 'color' => '#FF0000'
        ]);

        $this->issue = IssueModel::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'project_id' => $project->id,
            'sprint_id' => $sprint->id,
            'title' => 'Test Issue',
            'number' => 1,
            'reporter_id' => (string) $this->user->id,
            'issue_type_id' => $typeId,
            'status_id' => $statusId,
            'priority_id' => $priorityId,
        ]);
    }

    public function test_user_can_add_comment_to_issue()
    {
        $response = $this->actingAs($this->user)->postJson("/api/issues/{$this->issue->id}/comments", [
            'body' => 'This is a test comment.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.body', 'This is a test comment.');
        $response->assertJsonPath('data.author_id', (string) $this->user->id);

        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $this->issue->id,
            'body' => 'This is a test comment.',
            'author_id' => (string) $this->user->id,
        ]);
    }

    public function test_user_can_edit_own_comment()
    {
        $comment = CommentModel::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'issue_id' => $this->issue->id,
            'author_id' => (string) $this->user->id,
            'body' => 'Original comment',
            'is_edited' => false,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/issues/{$this->issue->id}/comments/{$comment->id}", [
            'body' => 'Edited comment',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.body', 'Edited comment');
        $response->assertJsonPath('data.is_edited', true);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Edited comment',
            'is_edited' => 1,
        ]);
    }
}
