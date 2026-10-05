<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup regular user
    $this->regularUser = UserModel::factory()->create([
        'uuid' => Str::uuid()->toString(),
        'password' => Hash::make('password123'),
    ]);

    // Setup superadmin user
    $this->superAdminUser = UserModel::factory()->create([
        'uuid' => Str::uuid()->toString(),
        'password' => Hash::make('password123'),
    ]);

    $role = RoleModel::create([
        'name' => 'Superadmin',
        'scope' => 'GLOBAL',
    ]);

    UserRoleModel::create([
        'user_id' => $this->superAdminUser->id,
        'role_id' => $role->id,
    ]);
});

it('prevents non-superadmin from accessing user list (Edge Case / Anti-IDOR)', function () {
    $this->actingAs($this->regularUser)
        ->getJson('/api/rbac/users')
        ->assertStatus(403);
});

it('prevents non-superadmin from creating a new user (Edge Case / Anti-IDOR)', function () {
    $this->actingAs($this->regularUser)
        ->postJson('/api/rbac/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ])
        ->assertStatus(403);
});

it('allows superadmin to view user list (Normal Case)', function () {
    $this->actingAs($this->superAdminUser)
        ->getJson('/api/rbac/users')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'uuid',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('allows superadmin to create a new user and ensures password is not exposed in plaintext (Normal Case)', function () {
    $payload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'SecretPassword123!',
    ];

    $response = $this->actingAs($this->superAdminUser)
        ->postJson('/api/rbac/users', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New User')
        ->assertJsonPath('data.email', 'newuser@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);

    $createdUser = UserModel::where('email', 'newuser@example.com')->first();

    expect($createdUser->password)->not->toBe('SecretPassword123!');
    expect(Hash::check('SecretPassword123!', $createdUser->password))->toBeTrue();
});
