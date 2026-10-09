<?php

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models\DocCategoryModel;
use App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models\DocPageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = UserModel::factory()->create();
    $this->adminRole = RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => Str::uuid(), 'scope' => 'GLOBAL']);

    $this->category = DocCategoryModel::create([
        'id' => Str::uuid()->toString(),
        'name' => 'General',
        'slug' => 'general',
        'description' => 'General docs',
        'order' => 1,
    ]);

    $this->page = DocPageModel::create([
        'id' => Str::uuid()->toString(),
        'category_id' => $this->category->id,
        'title' => 'First Doc',
        'slug' => 'first-doc',
        'content' => ['ops' => [['insert' => 'Hello World\n']]],
        'order' => 1,
        'is_published' => true,
    ]);
});

test('user can view doc page', function () {
    $response = $this->actingAs($this->user)->get("/docs/{$this->page->slug}");
    $response->assertStatus(200);
});

test('admin can access doc index', function () {
    // Assuming Superadmin has manage-rbac or similar to access /admin. Wait, let's see what middleware applies.
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $this->user->id, 'role_id' => $this->adminRole->id]);

    $response = $this->actingAs($this->user)->get('/admin/docs');
    $response->assertStatus(200);
});

test('admin can update doc page content', function () {
    UserRoleModel::create(['id' => Str::uuid(), 'user_id' => $this->user->id, 'role_id' => $this->adminRole->id]);

    $newContent = ['ops' => [['insert' => 'Updated content\n']]];

    $response = $this->actingAs($this->user)->put("/admin/docs/{$this->page->id}", [
        'content' => $newContent,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Tersimpan');

    $this->assertDatabaseHas('doc_pages', [
        'id' => $this->page->id,
    ]);

    $updatedPage = DocPageModel::find($this->page->id);
    $this->assertEquals($newContent, $updatedPage->content);
});

test('non-admin cannot update doc page content', function () {
    // Regular user without admin role
    $regularUser = UserModel::factory()->create();

    $newContent = ['ops' => [['insert' => 'Hacked content\n']]];

    $response = $this->actingAs($regularUser)->put("/admin/docs/{$this->page->id}", [
        'content' => $newContent,
    ]);

    $response->assertStatus(403);
});
