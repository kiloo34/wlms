<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Infrastructure\Config\ReminderSettings;
use App\Modules\Workload\Infrastructure\Reminders\WorkReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Owner project/tugas, jadwal (mulai + durasi), tipe pekerjaan, laporan project,
 * rekap owner, pencarian global, dan pengingat per tipe pekerjaan.
 */
beforeEach(function () {
    $levelId = Str::uuid()->toString();
    DB::table('org_levels')->insert(['id' => $levelId, 'slug' => 'group', 'name' => 'Group', 'depth' => 1, 'can_own_workspace' => true]);

    $this->groupId = Str::uuid()->toString();
    DB::table('org_units')->insert(['id' => $this->groupId, 'org_level_id' => $levelId, 'name' => 'IT Group']);

    $this->workspaceId = Str::uuid()->toString();
    DB::table('workspaces')->insert([
        'id' => $this->workspaceId, 'owner_group_id' => $this->groupId, 'name' => 'Planning WS',
        'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->workflowId = Str::uuid()->toString();
    DB::table('workflows')->insert(['id' => $this->workflowId, 'name' => 'Default', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);

    $this->issueTypeId = Str::uuid()->toString();
    DB::table('issue_types')->insert([
        'id' => $this->issueTypeId, 'name' => 'Task', 'slug' => 'task', 'icon' => 'task', 'color' => '#fff',
        'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->priorityId = Str::uuid()->toString();
    DB::table('priorities')->insert([
        'id' => $this->priorityId, 'name' => 'Medium', 'slug' => 'medium', 'color' => '#ff0',
        'level' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->todoId = Str::uuid()->toString();
    $this->doneId = Str::uuid()->toString();
    DB::table('statuses')->insert([
        ['id' => $this->todoId, 'name' => 'To Do', 'slug' => 'todo', 'category' => 'TODO', 'color' => '#999', 'created_at' => now(), 'updated_at' => now()],
        ['id' => $this->doneId, 'name' => 'Done', 'slug' => 'done', 'category' => 'DONE', 'color' => '#0f0', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('workflow_transitions')->insert([
        ['id' => Str::uuid()->toString(), 'workflow_id' => $this->workflowId, 'from_status_id' => null, 'to_status_id' => $this->todoId, 'name' => 'Create', 'created_at' => now(), 'updated_at' => now()],
        ['id' => Str::uuid()->toString(), 'workflow_id' => $this->workflowId, 'from_status_id' => $this->todoId, 'to_status_id' => $this->doneId, 'name' => 'Done', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $role = RoleModel::create(['id' => Str::uuid()->toString(), 'name' => 'Superadmin', 'scope' => 'GLOBAL']);
    $this->admin = UserModel::factory()->create(['org_unit_id' => $this->groupId, 'name' => 'Admin Satu']);
    DB::table('user_roles')->insert(['id' => Str::uuid()->toString(), 'user_id' => $this->admin->id, 'role_id' => $role->id]);

    $this->owner = UserModel::factory()->create(['name' => 'Budi Owner']);
    $this->outsider = UserModel::factory()->create(['name' => 'Orang Luar']);

    $this->makeProject = function (array $attrs = []): string {
        $id = Str::uuid()->toString();
        DB::table('projects')->insert(array_merge([
            'id' => $id, 'workspace_id' => $this->workspaceId, 'workflow_id' => $this->workflowId,
            'key' => 'P'.strtoupper(Str::random(4)), 'name' => 'Project '.$id, 'status' => 'ACTIVE',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));

        return $id;
    };

    $this->makeIssue = function (string $projectId, array $attrs = []): string {
        static $number = 0;
        $number++;
        $id = Str::uuid()->toString();
        DB::table('issues')->insert(array_merge([
            'id' => $id, 'project_id' => $projectId, 'status_id' => $this->todoId, 'number' => $number,
            'title' => 'Issue '.$number, 'issue_type_id' => $this->issueTypeId, 'priority_id' => $this->priorityId,
            'reporter_id' => $this->admin->id, 'work_type' => 'project', 'created_at' => now(), 'updated_at' => now(),
        ], $attrs));

        return $id;
    };
});

test('project dibuat dengan owner, tanggal mulai dan durasi sehingga tanggal selesai terhitung', function () {
    $this->actingAs($this->admin);

    $response = $this->postJson('/api/projects', [
        'workspace_id' => $this->workspaceId,
        'key' => 'OWN',
        'name' => 'Project Dengan Owner',
        'description' => 'Catatan penjelasan project',
        'lead_id' => $this->owner->id,
        'start_date' => '2026-12-01',
        'duration_days' => 10,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.lead_id', $this->owner->id)
        ->assertJsonPath('data.start_date', '2026-12-01')
        ->assertJsonPath('data.end_date', '2026-12-10')
        ->assertJsonPath('data.duration_days', 10)
        ->assertJsonPath('data.description', 'Catatan penjelasan project');

    $list = $this->getJson("/api/workspaces/{$this->workspaceId}/projects")->assertOk();
    $project = collect($list->json())->firstWhere('key', 'OWN');

    expect($project['lead']['name'])->toBe('Budi Owner')
        ->and($project['duration_days'])->toBe(10)
        ->and($project['remaining_days'])->toBeInt();
});

test('update project tanpa lead_id mempertahankan owner, dan jadwal bisa diubah', function () {
    $this->actingAs($this->admin);
    $projectId = ($this->makeProject)(['lead_id' => $this->owner->id, 'key' => 'KEEP']);

    $this->putJson("/api/projects/{$projectId}", ['name' => 'Ganti Nama'])->assertOk();
    expect((int) DB::table('projects')->where('id', $projectId)->value('lead_id'))->toBe($this->owner->id);

    $this->putJson("/api/projects/{$projectId}", [
        'name' => 'Ganti Nama',
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-31',
    ])->assertOk();

    $row = DB::table('projects')->where('id', $projectId)->first();
    expect(substr((string) $row->start_date, 0, 10))->toBe('2027-01-01')
        ->and(substr((string) $row->end_date, 0, 10))->toBe('2027-01-31');

    $this->putJson("/api/projects/{$projectId}", ['name' => 'Ganti Nama', 'lead_id' => null])->assertOk();
    expect(DB::table('projects')->where('id', $projectId)->value('lead_id'))->toBeNull();
});

test('issue menyimpan tipe pekerjaan dan durasi menghasilkan tanggal selesai', function () {
    $this->actingAs($this->admin);
    $projectId = ($this->makeProject)();

    $response = $this->postJson("/api/projects/{$projectId}/issues", [
        'title' => 'Rekap harian',
        'description' => 'Penjelasan tugas',
        'issue_type_id' => $this->issueTypeId,
        'priority_id' => $this->priorityId,
        'assignee_id' => $this->owner->id,
        'work_type' => 'daily',
        'start_date' => '2026-12-01',
        'duration_days' => 3,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.work_type', 'daily')
        ->assertJsonPath('data.work_type_label', 'Harian')
        ->assertJsonPath('data.due_date', '2026-12-03')
        ->assertJsonPath('data.duration_days', 3);

    $issueId = $response->json('data.id');

    $this->putJson("/api/issues/{$issueId}", [
        'title' => 'Rekap harian',
        'issue_type_id' => $this->issueTypeId,
        'status_id' => $this->todoId,
        'priority_id' => $this->priorityId,
        'work_type' => 'incidental',
    ])->assertOk()->assertJsonPath('data.work_type', 'incidental');

    $this->postJson("/api/projects/{$projectId}/issues", [
        'title' => 'Tipe salah',
        'issue_type_id' => $this->issueTypeId,
        'priority_id' => $this->priorityId,
        'work_type' => 'hourly',
    ])->assertStatus(422)->assertJsonValidationErrors('work_type');
});

test('laporan project merangkum progres, kesehatan, dan rekap owner', function () {
    $this->actingAs($this->admin);
    $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

    $late = ($this->makeProject)([
        'name' => 'Terlambat', 'lead_id' => $this->owner->id,
        'start_date' => $today->subDays(20)->toDateString(), 'end_date' => $today->subDay()->toDateString(),
    ]);
    $onTrack = ($this->makeProject)([
        'name' => 'Aman', 'lead_id' => $this->admin->id,
        'start_date' => $today->toDateString(), 'end_date' => $today->addDays(30)->toDateString(),
    ]);
    ($this->makeProject)(['name' => 'Arsip', 'status' => 'ARCHIVED']);

    ($this->makeIssue)($late, ['assignee_id' => $this->owner->id, 'due_date' => $today->subDays(2)->toDateString(), 'work_type' => 'weekly']);
    ($this->makeIssue)($late, ['status_id' => $this->doneId]);
    ($this->makeIssue)($onTrack, ['assignee_id' => $this->owner->id, 'due_date' => $today->addDays(5)->toDateString()]);

    $response = $this->getJson('/api/reports/projects')->assertOk();

    $projects = collect($response->json('data.projects'))->keyBy('name');
    expect($projects->keys()->all())->not->toContain('Arsip')
        ->and($projects['Terlambat']['health'])->toBe('overdue')
        ->and($projects['Terlambat']['progress'])->toBe(50)
        ->and($projects['Terlambat']['owner']['name'])->toBe('Budi Owner')
        ->and($projects['Terlambat']['remaining_days'])->toBe(-1)
        ->and($projects['Aman']['health'])->toBe('on_track');

    expect($response->json('data.summary.total_projects'))->toBe(2)
        ->and($response->json('data.summary.overdue_issues'))->toBe(1);

    $owners = collect($response->json('data.owners'))->keyBy('name');
    expect($owners['Budi Owner']['projects_owned'])->toBe(1)
        ->and($owners['Budi Owner']['open_tasks'])->toBe(2)
        ->and($owners['Budi Owner']['overdue_tasks'])->toBe(1)
        ->and($owners['Budi Owner']['next_deadline'])->toBe($today->addDays(5)->toDateString())
        ->and($owners['Budi Owner']['remaining_days'])->toBe(5)
        ->and($response->json('data.owners.0.name'))->toBe('Budi Owner');

    $weekly = collect($response->json('data.work_types'))->firstWhere('type', 'weekly');
    expect($weekly['open'])->toBe(1)->and($weekly['overdue'])->toBe(1);

    $detail = $this->getJson("/api/reports/owners/{$this->owner->id}")->assertOk();
    expect($detail->json('data.summary.projects_owned'))->toBe(1)
        ->and($detail->json('data.summary.open_tasks'))->toBe(2)
        ->and($detail->json('data.tasks.0.is_overdue'))->toBeTrue()
        ->and($detail->json('data.tasks.0.work_type_label'))->toBe('Mingguan');
});

test('laporan dan detail owner hanya berisi project yang bisa diakses', function () {
    ($this->makeProject)(['name' => 'Rahasia', 'lead_id' => $this->owner->id]);

    $this->actingAs($this->outsider);

    $this->getJson('/api/reports/projects')
        ->assertOk()
        ->assertJsonPath('data.summary.total_projects', 0)
        ->assertJsonCount(0, 'data.owners');

    $this->getJson("/api/reports/owners/{$this->owner->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data.projects');

    $this->getJson('/api/reports/owners/999999')->assertNotFound();
});

test('pencarian global menemukan teks di project, issue, dan komentar', function () {
    $projectId = ($this->makeProject)(['name' => 'Migrasi Server', 'key' => 'MIG', 'description' => 'Pindah ke datacenter Surabaya tahun depan']);
    $issueId = ($this->makeIssue)($projectId, ['title' => 'Siapkan rack', 'description' => 'Koordinasi dengan vendor JARINGAN utama']);
    DB::table('comments')->insert([
        'id' => Str::uuid()->toString(), 'issue_id' => $issueId, 'author_id' => $this->admin->id,
        'body' => 'Sudah konfirmasi jadwal dengan tim jaringan.', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $hidden = ($this->makeProject)(['name' => 'Arsip Lama', 'key' => 'OLD']);
    ($this->makeIssue)($hidden, ['title' => 'Jaringan lama']);

    $this->actingAs($this->admin);

    $res = $this->getJson('/api/search?q=surabaya')->assertOk();
    expect($res->json('data.projects.0.key'))->toBe('MIG')
        ->and($res->json('data.projects.0.matched_in'))->toBe('description')
        ->and($res->json('data.projects.0.snippet'))->toContain('Surabaya');

    $res = $this->getJson('/api/search?q=jaringan')->assertOk();
    expect(collect($res->json('data.issues'))->pluck('title')->all())->toContain('Siapkan rack', 'Jaringan lama')
        ->and($res->json('data.comments.0.issue_key'))->toBe('MIG-'.DB::table('issues')->where('id', $issueId)->value('number'));

    $res = $this->getJson('/api/search?q=100%')->assertOk();
    expect($res->json('data.total'))->toBe(0);

    $this->getJson('/api/search?q=a')->assertOk()->assertJsonPath('data.total', 0);

    // User tanpa akses tidak melihat apa pun.
    $this->actingAs($this->outsider);
    $this->getJson('/api/search?q=jaringan')->assertOk()->assertJsonPath('data.total', 0);
});

test('jadwal default pengingat per tipe pekerjaan', function () {
    $config = ReminderSettings::global();
    $types = fn (string $date) => array_map(
        fn ($t) => $t->value,
        WorkReminderService::recurringTypesDueOn(CarbonImmutable::parse($date), $config)
    );

    expect($types('2026-11-24'))->toBe(['daily'])                        // Selasa biasa
        ->and($types('2026-11-23'))->toBe(['daily', 'weekly'])           // Senin
        ->and($types('2026-12-01'))->toBe(['daily', 'monthly'])          // tanggal 1 (Selasa)
        ->and($types('2026-12-15'))->toBe(['daily', 'monthly'])          // tanggal 15
        ->and($types('2026-11-01'))->toBe(['daily', 'monthly', 'annually']); // 2 bulan sebelum akhir tahun (juga tgl 1)

    $custom = ReminderSettings::saveGlobal(['monthly' => ['days_of_month' => [15], 'first_monday' => true]]);
    expect(array_map(fn ($t) => $t->value, WorkReminderService::recurringTypesDueOn(CarbonImmutable::parse('2026-12-07'), $custom)))
        ->toBe(['daily', 'weekly', 'monthly']);                          // Senin pertama Desember
});

test('pengingat dikirim setelah jam 08:00, sekali saja, dan sesuai tenggat', function () {
    $projectId = ($this->makeProject)(['lead_id' => $this->owner->id, 'end_date' => '2026-12-01']);
    ($this->makeIssue)($projectId, ['assignee_id' => $this->owner->id, 'work_type' => 'daily', 'title' => 'Cek email']);
    ($this->makeIssue)($projectId, ['assignee_id' => $this->owner->id, 'work_type' => 'daily', 'status_id' => $this->doneId]);
    ($this->makeIssue)($projectId, ['assignee_id' => $this->owner->id, 'work_type' => 'incidental', 'due_date' => '2026-11-27', 'title' => 'Audit dadakan']);
    ($this->makeIssue)($projectId, ['assignee_id' => $this->owner->id, 'work_type' => 'project', 'start_date' => '2026-11-24', 'title' => 'Mulai desain']);

    $service = app(WorkReminderService::class);

    // 07:30 WIB: belum waktunya.
    expect($service->runForUser($this->owner->id, CarbonImmutable::parse('2026-11-24 07:30', 'Asia/Jakarta')))->toBe(0);

    // 08:05 WIB: harian + insidentil H-3 + mulai hari ini + project owner H-7.
    expect($service->runForUser($this->owner->id, CarbonImmutable::parse('2026-11-24 08:05', 'Asia/Jakarta')))->toBe(4);

    $types = DB::table('notifications')->where('user_id', $this->owner->id)->pluck('type')->sort()->values()->all();
    expect($types)->toBe(['reminder.deadline', 'reminder.project_deadline', 'reminder.start', 'reminder.work_type']);

    $daily = DB::table('notifications')->where('type', 'reminder.work_type')->first();
    $data = json_decode((string) $daily->data, true);
    expect($data['count'])->toBe(1)->and($data['items'][0]['title'])->toBe('Cek email');

    // Dipanggil lagi (scheduler berikutnya / lazy trigger) tidak menggandakan.
    expect($service->runForUser($this->owner->id, CarbonImmutable::parse('2026-11-24 10:00', 'Asia/Jakarta')))->toBe(0);

    // User mematikan pengingat harian lewat pengaturannya sendiri.
    ReminderSettings::saveForUser($this->owner->id, ['daily' => ['enabled' => false]]);
    expect($service->runForUser($this->owner->id, CarbonImmutable::parse('2026-11-25 08:00', 'Asia/Jakarta')))->toBe(0);
});

test('pengaturan pengingat per user dan default organisasi lewat API', function () {
    $this->actingAs($this->owner);

    $this->getJson('/api/settings/reminders')
        ->assertOk()
        ->assertJsonPath('data.time', '08:00')
        ->assertJsonPath('data.weekly.weekdays', [1])
        ->assertJsonPath('data.monthly.days_of_month', [1, 15])
        ->assertJsonPath('data.annually.month', 11)
        ->assertJsonPath('has_override', false)
        ->assertJsonPath('can_manage_defaults', false);

    $this->putJson('/api/settings/reminders', ['time' => '07:15', 'weekly' => ['weekdays' => [1, 5]]])
        ->assertOk()
        ->assertJsonPath('data.time', '07:15')
        ->assertJsonPath('data.weekly.weekdays', [1, 5])
        ->assertJsonPath('defaults.time', '08:00')
        ->assertJsonPath('has_override', true);

    $this->putJson('/api/settings/reminders', ['time' => '25:00'])->assertStatus(422);
    $this->putJson('/api/settings/reminders/default', ['time' => '09:00'])->assertForbidden();

    $this->deleteJson('/api/settings/reminders')->assertOk()->assertJsonPath('data.time', '08:00');

    $this->actingAs($this->admin);
    $this->putJson('/api/settings/reminders/default', ['time' => '09:00'])
        ->assertOk()
        ->assertJsonPath('defaults.time', '09:00');

    $this->actingAs($this->owner);
    $this->getJson('/api/settings/reminders')->assertOk()->assertJsonPath('data.time', '09:00');
});

test('direktori orang hanya berisi user aktif', function () {
    UserModel::factory()->create(['name' => 'Nonaktif', 'status' => 'INACTIVE']);
    $this->actingAs($this->owner);

    $names = collect($this->getJson('/api/people')->assertOk()->json('data'))->pluck('name');
    expect($names)->toContain('Budi Owner', 'Orang Luar')->not->toContain('Nonaktif');
});
