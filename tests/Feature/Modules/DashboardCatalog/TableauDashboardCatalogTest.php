<?php

use App\Modules\DashboardCatalog\Infrastructure\Persistence\Eloquent\Models\TableauDashboardModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Database\Seeders\TableauDashboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = UserModel::factory()->create();
    $superadminRole = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $this->superadmin->id, 'role_id' => $superadminRole->id]);

    $this->dashboard = TableauDashboardModel::create([
        'id' => Str::uuid()->toString(),
        'number' => 1,
        'name' => 'Dash DPK Kantor Fungsional',
        'definition' => 'Rincian posisi DPK per kantor fungsional (KF).',
        'contents' => 'nama kantor, jenis dan tipe rekening.',
        'purpose' => 'View Data & Download Data',
        'tableau_project' => 'Dashboard DPK',
        'mapping' => 'Business Funding',
        'user_permission' => 'KC',
        'user_mapping' => 'Cabang',
        'owner' => 'Dana & Jasa',
    ]);
});

function userWithDashboardPermission(string ...$permissions): UserModel
{
    $user = UserModel::factory()->create();
    $role = RoleModel::create(['id' => Str::uuid(), 'name' => 'Catalog '.Str::random(6), 'scope' => 'GLOBAL']);

    foreach ($permissions as $permissionName) {
        $permission = PermissionModel::firstOrCreate(['name' => $permissionName], ['id' => Str::uuid()]);
        $role->permissions()->attach($permission->id);
    }

    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id]);

    return $user;
}

test('guest is redirected to login from the dashboard catalog', function () {
    $this->get('/dashboards')->assertRedirect('/login');
});

test('superadmin can open the dashboard catalog page with data and options', function () {
    $this->actingAs($this->superadmin)
        ->get('/dashboards')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboards/Index')
            ->has('dashboards', 1)
            ->where('dashboards.0.name', 'Dash DPK Kantor Fungsional')
            ->where('options.mappings', ['Business Funding'])
            ->where('nextNumber', 2)
            ->where('can.create', true)
            ->where('can.edit', true)
            ->where('can.delete', true)
        );
});

test('user with dashboards:view can only read the catalog', function () {
    $viewer = userWithDashboardPermission('dashboards:view');

    $this->actingAs($viewer)
        ->get('/dashboards')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can.create', false)
            ->where('can.edit', false)
            ->where('can.delete', false)
        );

    $this->actingAs($viewer)
        ->post('/dashboards', ['name' => 'Dashboard Baru'])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->delete("/dashboards/{$this->dashboard->id}")
        ->assertForbidden();
});

test('user without any dashboard permission is forbidden', function () {
    $outsider = UserModel::factory()->create();

    $this->actingAs($outsider)->get('/dashboards')->assertForbidden();
});

test('dashboards:manage grants full access through the gates', function () {
    $manager = userWithDashboardPermission('dashboards:manage');

    $this->actingAs($manager)
        ->get('/dashboards')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can.create', true)
            ->where('can.edit', true)
            ->where('can.delete', true)
        );
});

test('superadmin can register a new dashboard and the number auto increments', function () {
    $response = $this->actingAs($this->superadmin)->post('/dashboards', [
        'name' => '  Dashboard QRIS Baru  ',
        'definition' => 'Monitoring QRIS.',
        'contents' => 'jumlah transaksi per cabang.',
        'purpose' => 'View Data Only',
        'tableau_project' => '# DEV',
        'mapping' => 'Digital Services',
        'user_permission' => 'Divisi Digital Banking',
        'user_mapping' => 'Divisi',
        'owner' => 'Digital Banking',
        'notes' => '',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('tableau_dashboards', [
        'name' => 'Dashboard QRIS Baru',
        'number' => 2,
        'mapping' => 'Digital Services',
        'notes' => null,
    ]);
});

test('creating a dashboard requires a name', function () {
    $this->actingAs($this->superadmin)
        ->from('/dashboards')
        ->post('/dashboards', ['name' => ''])
        ->assertRedirect('/dashboards')
        ->assertSessionHasErrors('name');
});

test('superadmin can update a dashboard', function () {
    $this->actingAs($this->superadmin)
        ->put("/dashboards/{$this->dashboard->id}", [
            'number' => 7,
            'name' => 'Dash DPK Kantor Fungsional v2',
            'mapping' => 'Business Funding',
            'user_mapping' => 'Divisi & Cabang',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tableau_dashboards', [
        'id' => $this->dashboard->id,
        'number' => 7,
        'name' => 'Dash DPK Kantor Fungsional v2',
        'user_mapping' => 'Divisi & Cabang',
    ]);
});

test('deleting a dashboard is a soft delete', function () {
    $this->actingAs($this->superadmin)
        ->delete("/dashboards/{$this->dashboard->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertSoftDeleted('tableau_dashboards', ['id' => $this->dashboard->id]);

    $this->actingAs($this->superadmin)
        ->get('/dashboards')
        ->assertInertia(fn ($page) => $page->has('dashboards', 0));
});

test('updating an unknown dashboard returns 404', function () {
    $this->actingAs($this->superadmin)
        ->put('/dashboards/'.Str::uuid(), ['name' => 'X'])
        ->assertNotFound();
});

test('catalog seeder loads the 159 dashboards from the sheet and is idempotent', function () {
    $this->dashboard->forceDelete();

    expect(TableauDashboardSeeder::seedFromCatalog())->toBe(159);
    expect(TableauDashboardSeeder::seedFromCatalog())->toBe(0);
    expect(TableauDashboardModel::count())->toBe(159);

    $this->assertDatabaseHas('tableau_dashboards', [
        'number' => 159,
        'name' => '7. Dashboard User Overide',
        'tableau_project' => 'Early Warning Dashboard',
        'mapping' => 'Risk',
    ]);
});
